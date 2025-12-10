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
$period = $_GET['period'] ?? 'month';

// Helper: date range by period
function getDateRange($period) {
    $today = new DateTime();
    switch ($period) {
        case 'today':
            return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        case 'yesterday':
            $y = (clone $today)->modify('-1 day');
            return [$y->format('Y-m-d'), $y->format('Y-m-d')];
        case 'week':
            $start = (clone $today)->modify('monday this week');
            return [$start->format('Y-m-d'), $today->format('Y-m-d')];
        case 'month':
        default:
            $start = $today->format('Y-m-01');
            $end = $today->format('Y-m-t');
            return [$start, $end];
    }
}

try {
    [$from, $to] = getDateRange($period);

    // Revenue, bookings, new customers, occupancy rate
    $revenueStmt = $db->prepare("SELECT IFNULL(SUM(total_price),0) AS revenue, COUNT(*) AS bookings
                                 FROM bookings
                                 WHERE DATE(created_at) BETWEEN :from AND :to");
    $revenueStmt->execute([':from' => $from, ':to' => $to]);
    $revRow = $revenueStmt->fetch(PDO::FETCH_ASSOC);

    $custStmt = $db->prepare("SELECT COUNT(*) AS new_customers
                              FROM users
                              WHERE user_type='customer' AND DATE(created_at) BETWEEN :from AND :to");
    $custStmt->execute([':from' => $from, ':to' => $to]);
    $custRow = $custStmt->fetch(PDO::FETCH_ASSOC);

    // Occupancy: occupied rooms / total rooms
    $totalRooms = (int)$db->query("SELECT COUNT(*) AS c FROM rooms")->fetch(PDO::FETCH_ASSOC)['c'];
    $occStmt = $db->prepare("SELECT COUNT(*) AS occupied
                             FROM rooms
                             WHERE status = 'occupied'");
    $occStmt->execute();
    $occRow = $occStmt->fetch(PDO::FETCH_ASSOC);
    $occupancyRate = $totalRooms ? round($occRow['occupied'] / $totalRooms * 100) : 0;

    // Revenue by room type
    $revTypeStmt = $db->prepare("SELECT rt.type_name, SUM(b.total_price) AS revenue
                                 FROM bookings b
                                 LEFT JOIN rooms r ON r.id = b.room_id
                                 LEFT JOIN room_types rt ON rt.id = r.room_type_id
                                 WHERE DATE(b.created_at) BETWEEN :from AND :to
                                 GROUP BY rt.type_name");
    $revTypeStmt->execute([':from' => $from, ':to' => $to]);
    $revenueByType = $revTypeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Payment methods
    $payStmt = $db->prepare("SELECT payment_method as method, COUNT(*) as count
                             FROM bookings
                             WHERE payment_method IS NOT NULL AND DATE(created_at) BETWEEN :from AND :to
                             GROUP BY payment_method");
    $payStmt->execute([':from' => $from, ':to' => $to]);
    $paymentMethods = $payStmt->fetchAll(PDO::FETCH_ASSOC);

    // Yearly revenue (current year)
    $yearStmt = $db->query("SELECT DATE_FORMAT(created_at,'%m') AS month, SUM(total_price) AS revenue
                            FROM bookings
                            WHERE YEAR(created_at)=YEAR(CURDATE())
                            GROUP BY DATE_FORMAT(created_at,'%m')
                            ORDER BY month");
    $yearlyRevenue = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'data' => [
            'revenue' => (float)$revRow['revenue'],
            'bookings' => (int)$revRow['bookings'],
            'new_customers' => (int)$custRow['new_customers'],
            'occupancy_rate' => $occupancyRate,
            'revenue_change' => 0,
            'bookings_change' => 0,
            'customers_change' => 0,
            'occupancy_change' => 0,
            'revenue_by_type' => $revenueByType,
            'payment_methods' => $paymentMethods,
            'yearly_revenue' => $yearlyRevenue,
        ],
    ];

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

