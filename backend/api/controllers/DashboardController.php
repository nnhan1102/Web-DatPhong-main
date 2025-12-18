<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Room.php';
require_once 'models/Booking.php';

class DashboardController {
    private $user;
    private $room;
    private $booking;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->user = new User($db);
        $this->room = new Room($db);
        $this->booking = new Booking($db);
    }
    
    public function getDashboardData() {
        try {
            // Lấy thống kê users
            $userStats = $this->user->getStats();
            
            // Lấy thống kê bookings
            $bookingStats = $this->getBookingStats();
            
            // Lấy thống kê rooms
            $roomStats = $this->room->getStats();
            
            // Lấy recent bookings
            $recentBookings = $this->getRecentBookings(10);
            
            // Lấy chart data
            $chartData = $this->getRevenueChartData();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'user_stats' => $userStats,
                    'booking_stats' => $bookingStats,
                    'room_stats' => $roomStats,
                    'recent_bookings' => $recentBookings,
                    'chart_data' => $chartData
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching dashboard data: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getBookingStats() {
        $db = $this->booking->conn;
        
        // Tổng số bookings
        $query = "SELECT COUNT(*) as total FROM bookings WHERE status != 'cancelled'";
        $stmt = $db->query($query);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Bookings hôm nay
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) as today_count FROM bookings 
                  WHERE DATE(created_at) = :today AND status != 'cancelled'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $todayCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['today_count'];
        
        // Doanh thu hôm nay
        $query = "SELECT SUM(total_price) as today_revenue FROM bookings 
                  WHERE DATE(created_at) = :today AND status = 'confirmed'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $todayRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['today_revenue'] ?? 0;
        
        // Tổng doanh thu
        $query = "SELECT SUM(total_price) as total_revenue FROM bookings 
                  WHERE status = 'confirmed'";
        $stmt = $db->query($query);
        $totalRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
        
        // Bookings đang pending
        $query = "SELECT COUNT(*) as pending_count FROM bookings WHERE status = 'pending'";
        $stmt = $db->query($query);
        $pendingCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
        
        return [
            'total_bookings' => $total,
            'today_bookings' => $todayCount,
            'today_revenue' => $todayRevenue,
            'total_revenue' => $totalRevenue,
            'pending_bookings' => $pendingCount
        ];
    }
    
    private function getRecentBookings($limit = 10) {
        $db = $this->booking->conn;
        
        $query = "SELECT 
                    b.id, b.booking_code, b.customer_name, b.customer_email,
                    b.check_in_date, b.check_out_date, b.total_price, b.status,
                    b.created_at,
                    GROUP_CONCAT(r.room_number) as rooms
                  FROM bookings b
                  LEFT JOIN booking_rooms br ON b.id = br.booking_id
                  LEFT JOIN rooms r ON br.room_id = r.id
                  WHERE b.status != 'cancelled'
                  GROUP BY b.id
                  ORDER BY b.created_at DESC
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format lại dữ liệu
        foreach ($bookings as &$booking) {
            $booking['total_price'] = (float)$booking['total_price'];
            $booking['rooms'] = $booking['rooms'] ? explode(',', $booking['rooms']) : [];
            $booking['created_at'] = date('d/m/Y H:i', strtotime($booking['created_at']));
        }
        
        return $bookings;
    }
    
    private function getRevenueChartData() {
        $db = $this->booking->conn;
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                      FROM bookings 
                      WHERE DATE(created_at) = :date AND status = 'confirmed'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
            
            $data[] = [
                'date' => date('d/m', strtotime($date)),
                'revenue' => $revenue
            ];
        }
        
        return $data;
    }
    
    public function getChartData() {
        $period = $_GET['period'] ?? 'week';
        $db = $this->booking->conn;
        $data = [];
        
        if ($period === 'month') {
            // 30 ngày
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                          FROM bookings 
                          WHERE DATE(created_at) = :date AND status = 'confirmed'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                $revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
                
                $data[] = [
                    'date' => date('d/m', strtotime($date)),
                    'revenue' => $revenue
                ];
            }
        } else {
            // 7 ngày mặc định
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $query = "SELECT COALESCE(SUM(total_price), 0) as revenue 
                          FROM bookings 
                          WHERE DATE(created_at) = :date AND status = 'confirmed'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                $revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
                
                $data[] = [
                    'date' => date('d/m', strtotime($date)),
                    'revenue' => $revenue
                ];
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
    
    public function getQuickStats() {
        try {
            $userStats = $this->user->getStats();
            $bookingStats = $this->getBookingStats();
            $roomStats = $this->room->getStats();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_users' => $userStats['total_users'] ?? 0,
                    'new_users_today' => $userStats['new_users_today'] ?? 0,
                    'total_bookings' => $bookingStats['total_bookings'] ?? 0,
                    'today_bookings' => $bookingStats['today_bookings'] ?? 0,
                    'today_revenue' => $bookingStats['today_revenue'] ?? 0,
                    'total_rooms' => $roomStats['total_rooms'] ?? 0,
                    'occupied_rooms' => $roomStats['occupied_rooms'] ?? 0,
                    'occupancy_rate' => $roomStats['occupancy_rate'] ?? 0,
                    'pending_bookings' => $bookingStats['pending_bookings'] ?? 0
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching quick stats: ' . $e->getMessage()
            ]);
        }
    }
    
    public function exportData() {
        try {
            $type = $_GET['type'] ?? 'all';
            $format = $_GET['format'] ?? 'json';
            
            $data = [];
            
            switch ($type) {
                case 'bookings':
                    $data = $this->getRecentBookings(1000);
                    break;
                case 'revenue':
                    $data = $this->getRevenueChartData();
                    break;
                case 'all':
                default:
                    $data = [
                        'user_stats' => $this->user->getStats(),
                        'booking_stats' => $this->getBookingStats(),
                        'room_stats' => $this->room->getStats(),
                        'recent_bookings' => $this->getRecentBookings(100),
                        'chart_data' => $this->getRevenueChartData()
                    ];
                    break;
            }
            
            if ($format === 'csv') {
                $this->exportCSV($data, $type);
            } else {
                http_response_code(200);
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="dashboard_data_' . date('Y-m-d') . '.json"');
                echo json_encode($data);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error exporting data: ' . $e->getMessage()
            ]);
        }
    }
    
    private function exportCSV($data, $type) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if ($type === 'bookings' && is_array($data) && count($data) > 0) {
            // Header
            fputcsv($output, array_keys($data[0]));
            
            // Data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } elseif ($type === 'revenue' && is_array($data)) {
            fputcsv($output, ['Date', 'Revenue']);
            foreach ($data as $row) {
                fputcsv($output, [$row['date'], $row['revenue']]);
            }
        }
        
        fclose($output);
    }
    
    public function refreshDashboard() {
        try {
            // Lấy thống kê real-time
            $bookingStats = $this->getBookingStats();
            $roomStats = $this->room->getStats();
            $recentBookings = $this->getRecentBookings(5);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'booking_stats' => $bookingStats,
                    'room_stats' => $roomStats,
                    'recent_bookings' => $recentBookings,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error refreshing dashboard: ' . $e->getMessage()
            ]);
        }
    }
}
?>