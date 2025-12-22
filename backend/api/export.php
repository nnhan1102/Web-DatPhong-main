<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';

$db = (new Database())->getConnection();
$type = $_GET['type'] ?? '';

// Helper function to output CSV
function outputCSV($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

try {
    if ($type === 'bookings') {
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT b.booking_code, u.full_name, r.room_number, b.check_in, b.check_out, 
                         b.num_guests, b.total_price, b.status, b.payment_status, b.created_at
                  FROM bookings b
                  LEFT JOIN users u ON b.customer_id = u.id
                  LEFT JOIN rooms r ON b.room_id = r.id
                  WHERE 1=1";
                  
        $params = [];
        
        if ($start_date) {
            $query .= " AND b.check_in >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND b.check_in <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        if ($status) {
            $query .= " AND b.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY b.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $headers = ['Mã đặt phòng', 'Khách hàng', 'Phòng', 'Ngày nhận', 'Ngày trả', 'Số khách', 'Tổng tiền', 'Trạng thái', 'Thanh toán', 'Ngày tạo'];
        
        outputCSV('bookings_' . date('Y-m-d') . '.csv', $headers, $bookings);
        
    } elseif ($type === 'reports') {
        $period = $_GET['period'] ?? 'month';
        
        // Helper to get date range
        $today = new DateTime();
        switch ($period) {
            case 'today':
                $start = $today->format('Y-m-d');
                $end = $today->format('Y-m-d');
                break;
            case 'yesterday':
                $y = (clone $today)->modify('-1 day');
                $start = $y->format('Y-m-d');
                $end = $y->format('Y-m-d');
                break;
            case 'week':
                $s = (clone $today)->modify('monday this week');
                $start = $s->format('Y-m-d');
                $end = $today->format('Y-m-d');
                break;
            case 'year':
                 $start = $today->format('Y-01-01');
                 $end = $today->format('Y-12-31');
                 break;
            case 'custom':
                 $start = $_GET['start_date'] ?? $today->format('Y-m-01');
                 $end = $_GET['end_date'] ?? $today->format('Y-m-t');
                 break;
            case 'month':
            default:
                $start = $today->format('Y-m-01');
                $end = $today->format('Y-m-t');
                break;
        }

        // Get daily revenue stats for the period
        $query = "SELECT DATE(created_at) as date, COUNT(*) as total_bookings, 
                         SUM(total_price) as revenue
                  FROM bookings
                  WHERE DATE(created_at) BETWEEN :start AND :end
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC";
                  
        $stmt = $db->prepare($query);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=report_' . $period . '_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        // Summary Header
        fputcsv($output, ['BÁO CÁO THỐNG KÊ (' . $start . ' - ' . $end . ')']);
        fputcsv($output, []);
        
        // Daily breakdown
        fputcsv($output, ['CHI TIẾT THEO NGÀY']);
        fputcsv($output, ['Ngày', 'Tổng đặt phòng', 'Doanh thu']);
        
        $totalRevenue = 0;
        $totalBookings = 0;
        
        foreach ($dailyStats as $row) {
            fputcsv($output, [
                $row['date'],
                $row['total_bookings'],
                $row['revenue']
            ]);
            $totalRevenue += $row['revenue'];
            $totalBookings += $row['total_bookings'];
        }
        
        fputcsv($output, []);
        fputcsv($output, ['TỔNG CỘNG', $totalBookings, $totalRevenue]);
        
        fclose($output);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid export type']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
