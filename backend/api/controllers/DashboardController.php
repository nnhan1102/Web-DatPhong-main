<?php
require_once '../config/database.php';

class DashboardController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy dữ liệu dashboard
    public function getDashboardData() {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            // Lấy khoảng thời gian từ query string (mặc định 30 ngày)
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
            
            // Tính toán ngày bắt đầu
            $startDate = date('Y-m-d', strtotime("-$days days"));
            
            // Tổng quan
            $overview = $this->getOverviewStats();
            
            // Thống kê theo thời gian
            $timeStats = $this->getTimeBasedStats($startDate);
            
            // Top tours
            $topTours = $this->getTopTours($startDate);
            
            // Revenue analysis
            $revenueAnalysis = $this->getRevenueAnalysis($year, $month);
            
            // Recent activities
            $recentActivities = $this->getRecentActivities();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'overview' => $overview,
                    'time_stats' => $timeStats,
                    'top_tours' => $topTours,
                    'revenue_analysis' => $revenueAnalysis,
                    'recent_activities' => $recentActivities,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy thống kê tổng quan
    private function getOverviewStats() {
        $stats = [];
        
        // Tổng số tour
        $query = "SELECT COUNT(*) as total FROM tours WHERE status = 'active' AND deleted_at IS NULL";
        $stmt = $this->db->query($query);
        $stats['total_tours'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tour hôm nay
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) as total FROM tours WHERE DATE(created_at) = :today AND status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $stats['today_tours'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tổng số booking
        $query = "SELECT COUNT(*) as total FROM bookings WHERE booking_status != 'cancelled'";
        $stmt = $this->db->query($query);
        $stats['total_bookings'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Booking hôm nay
        $query = "SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = :today AND booking_status != 'cancelled'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $stats['today_bookings'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tổng số khách hàng
        $query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
        $stmt = $this->db->query($query);
        $stats['total_customers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Khách hàng mới hôm nay
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = :today AND status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $stats['today_customers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Doanh thu tháng này
        $currentMonth = date('Y-m');
        $query = "SELECT SUM(total_price) as revenue FROM bookings 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = :month 
                  AND payment_status = 'paid' 
                  AND booking_status != 'cancelled'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':month', $currentMonth);
        $stmt->execute();
        $stats['monthly_revenue'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        
        // Doanh thu hôm nay
        $query = "SELECT SUM(total_price) as revenue FROM bookings 
                  WHERE DATE(created_at) = :today 
                  AND payment_status = 'paid' 
                  AND booking_status != 'cancelled'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $stats['today_revenue'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        
        return $stats;
    }

    // Thống kê theo thời gian
    private function getTimeBasedStats($startDate) {
        $stats = [];
        
        // Booking trend (7 ngày gần nhất)
        $query = "SELECT DATE(created_at) as date, 
                         COUNT(*) as booking_count,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue
                  FROM bookings
                  WHERE created_at >= :start_date 
                    AND booking_status != 'cancelled'
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC
                  LIMIT 7";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->execute();
        $stats['booking_trend'] = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Revenue by month (6 tháng gần nhất)
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue,
                         COUNT(*) as booking_count
                  FROM bookings
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    AND booking_status != 'cancelled'
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 6";
        
        $stmt = $this->db->query($query);
        $stats['monthly_revenue'] = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        // Customer growth (30 ngày gần nhất)
        $query = "SELECT DATE(created_at) as date, COUNT(*) as new_customers
                  FROM users
                  WHERE created_at >= :start_date 
                    AND status = 'active'
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC
                  LIMIT 30";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->execute();
        $stats['customer_growth'] = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        return $stats;
    }

    // Top tours
    private function getTopTours($startDate) {
        $tours = [];
        
        // Top tours by bookings
        $query = "SELECT t.id, t.name, t.destination, 
                         COUNT(b.id) as booking_count,
                         SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue
                  FROM tours t
                  LEFT JOIN bookings b ON t.id = b.tour_id 
                    AND b.created_at >= :start_date 
                    AND b.booking_status != 'cancelled'
                  WHERE t.status = 'active' AND t.deleted_at IS NULL
                  GROUP BY t.id
                  ORDER BY booking_count DESC, revenue DESC
                  LIMIT 5";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->execute();
        $tours['by_bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top tours by revenue
        $query = "SELECT t.id, t.name, t.destination,
                         SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue,
                         COUNT(b.id) as booking_count
                  FROM tours t
                  LEFT JOIN bookings b ON t.id = b.tour_id 
                    AND b.created_at >= :start_date 
                    AND b.booking_status != 'cancelled'
                  WHERE t.status = 'active' AND t.deleted_at IS NULL
                  GROUP BY t.id
                  ORDER BY revenue DESC
                  LIMIT 5";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->execute();
        $tours['by_revenue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $tours;
    }

    // Phân tích doanh thu
    private function getRevenueAnalysis($year, $month) {
        $analysis = [];
        
        // Monthly revenue breakdown
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue,
                         COUNT(*) as booking_count,
                         AVG(CASE WHEN payment_status = 'paid' THEN total_price END) as avg_booking_value
                  FROM bookings
                  WHERE YEAR(created_at) = :year
                    AND booking_status != 'cancelled'
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        $analysis['monthly_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Revenue by payment method
        $query = "SELECT payment_method,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue,
                         COUNT(*) as booking_count
                  FROM bookings
                  WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
                    AND booking_status != 'cancelled'
                  GROUP BY payment_method
                  ORDER BY revenue DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        $analysis['by_payment_method'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Revenue by booking status
        $query = "SELECT booking_status,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue,
                         COUNT(*) as booking_count
                  FROM bookings
                  WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
                  GROUP BY booking_status
                  ORDER BY revenue DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        $analysis['by_booking_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $analysis;
    }

    // Hoạt động gần đây
    private function getRecentActivities() {
        $activities = [];
        
        // Recent bookings
        $query = "SELECT b.id, b.booking_code, b.total_price, b.booking_status,
                         t.name as tour_name,
                         u.full_name as customer_name,
                         b.created_at
                  FROM bookings b
                  LEFT JOIN tours t ON b.tour_id = t.id
                  LEFT JOIN users u ON b.user_id = u.id
                  ORDER BY b.created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->query($query);
        $activities['recent_bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent customers
        $query = "SELECT id, full_name, email, phone, created_at
                  FROM users
                  WHERE status = 'active'
                  ORDER BY created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->query($query);
        $activities['recent_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent tours
        $query = "SELECT id, name, destination, price_adult, status, created_at
                  FROM tours
                  WHERE status = 'active' AND deleted_at IS NULL
                  ORDER BY created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->query($query);
        $activities['recent_tours'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $activities;
    }

    // Kiểm tra authentication
    private function checkAdminAuth() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: No token provided'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Nên sử dụng JWT hoặc token từ database trong thực tế
        if ($token !== 'admin-secret-token') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden: Invalid token'
            ]);
            exit;
        }
    }

    // Export dữ liệu (Excel/CSV)
    public function exportData() {
        try {
            $this->checkAdminAuth();
            
            $type = $_GET['type'] ?? 'bookings';
            $format = $_GET['format'] ?? 'csv';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            switch ($type) {
                case 'bookings':
                    $data = $this->exportBookings($startDate, $endDate);
                    $filename = "bookings_export_" . date('Ymd') . ".$format";
                    break;
                    
                case 'customers':
                    $data = $this->exportCustomers($startDate, $endDate);
                    $filename = "customers_export_" . date('Ymd') . ".$format";
                    break;
                    
                case 'revenue':
                    $data = $this->exportRevenue($startDate, $endDate);
                    $filename = "revenue_export_" . date('Ymd') . ".$format";
                    break;
                    
                default:
                    throw new Exception("Invalid export type");
            }
            
            if ($format === 'csv') {
                $this->outputCSV($data, $filename);
            } else {
                $this->outputJSON($data, $filename);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Export error: ' . $e->getMessage()
            ]);
        }
    }

    private function exportBookings($startDate, $endDate) {
        $query = "SELECT b.booking_code, b.booking_date, b.departure_date,
                         b.num_adults, b.num_children, b.num_infants,
                         b.total_price, b.payment_method, b.payment_status, b.booking_status,
                         t.name as tour_name, t.destination,
                         u.full_name as customer_name, u.email, u.phone,
                         b.created_at
                  FROM bookings b
                  LEFT JOIN tours t ON b.tour_id = t.id
                  LEFT JOIN users u ON b.user_id = u.id
                  WHERE DATE(b.created_at) BETWEEN :start_date AND :end_date
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function exportCustomers($startDate, $endDate) {
        $query = "SELECT u.full_name, u.email, u.phone, u.address,
                         COUNT(b.id) as total_bookings,
                         SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                         MAX(b.created_at) as last_booking_date,
                         u.created_at as registration_date
                  FROM users u
                  LEFT JOIN bookings b ON u.id = b.user_id
                  WHERE DATE(u.created_at) BETWEEN :start_date AND :end_date
                    AND u.status = 'active'
                  GROUP BY u.id
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function exportRevenue($startDate, $endDate) {
        $query = "SELECT DATE(b.created_at) as date,
                         COUNT(b.id) as booking_count,
                         SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue,
                         AVG(CASE WHEN b.payment_status = 'paid' THEN b.total_price END) as avg_booking_value,
                         t.destination,
                         t.name as tour_name,
                         COUNT(DISTINCT b.user_id) as unique_customers
                  FROM bookings b
                  LEFT JOIN tours t ON b.tour_id = t.id
                  WHERE DATE(b.created_at) BETWEEN :start_date AND :end_date
                    AND b.booking_status != 'cancelled'
                  GROUP BY DATE(b.created_at), t.id
                  ORDER BY date DESC, revenue DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function outputCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // Add header row
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    private function outputJSON($data, $filename) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    // Thêm phương thức mới: Lấy dữ liệu cho biểu đồ
    public function getChartData() {
        try {
            $this->checkAdminAuth();
            
            $chartType = $_GET['chart'] ?? 'revenue';
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            
            switch ($chartType) {
                case 'revenue':
                    $data = $this->getRevenueChartData($year);
                    break;
                    
                case 'bookings':
                    $data = $this->getBookingsChartData($year);
                    break;
                    
                case 'customers':
                    $data = $this->getCustomersChartData($year);
                    break;
                    
                default:
                    throw new Exception("Invalid chart type");
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function getRevenueChartData($year) {
        $query = "SELECT MONTH(created_at) as month,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue
                  FROM bookings
                  WHERE YEAR(created_at) = :year
                    AND booking_status != 'cancelled'
                  GROUP BY MONTH(created_at)
                  ORDER BY month";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBookingsChartData($year) {
        $query = "SELECT MONTH(created_at) as month,
                         COUNT(*) as booking_count,
                         COUNT(DISTINCT user_id) as unique_customers
                  FROM bookings
                  WHERE YEAR(created_at) = :year
                    AND booking_status != 'cancelled'
                  GROUP BY MONTH(created_at)
                  ORDER BY month";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCustomersChartData($year) {
        $query = "SELECT MONTH(created_at) as month,
                         COUNT(*) as new_customers,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers
                  FROM users
                  WHERE YEAR(created_at) = :year
                  GROUP BY MONTH(created_at)
                  ORDER BY month";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thêm phương thức mới: Lấy thống kê nhanh
    public function getQuickStats() {
        try {
            $this->checkAdminAuth();
            
            $period = $_GET['period'] ?? 'today'; // today, yesterday, week, month
            
            switch ($period) {
                case 'today':
                    $dateCondition = "DATE(created_at) = CURDATE()";
                    break;
                    
                case 'yesterday':
                    $dateCondition = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                    
                case 'week':
                    $dateCondition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                    
                case 'month':
                    $dateCondition = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                    
                default:
                    $dateCondition = "DATE(created_at) = CURDATE()";
            }
            
            // Bookings
            $query = "SELECT COUNT(*) as bookings,
                             SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue
                      FROM bookings
                      WHERE $dateCondition AND booking_status != 'cancelled'";
            
            $stmt = $this->db->query($query);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // New customers
            $query = "SELECT COUNT(*) as new_customers
                      FROM users
                      WHERE $dateCondition AND status = 'active'";
            
            $stmt = $this->db->query($query);
            $customers = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['new_customers'] = $customers['new_customers'];
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // Thêm phương thức mới: Xóa cache hoặc refresh data
    public function refreshDashboard() {
        try {
            $this->checkAdminAuth();
            
            // Có thể thêm logic clear cache ở đây nếu cần
            // Ví dụ: xóa cache file hoặc reset session cache
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Dashboard data refreshed successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Refresh error: ' . $e->getMessage()
            ]);
        }
    }
}