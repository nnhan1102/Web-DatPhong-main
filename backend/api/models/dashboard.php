<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Room.php';
require_once 'models/Booking.php';

// Xử lý CORS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kiểm tra session admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied: Admin privileges required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$room = new Room($db);
$booking = new Booking($db);

$action = $_GET['action'] ?? 'getDashboardData';

switch ($action) {
    case 'getDashboardData':
        getDashboardData($user, $room, $booking);
        break;
    case 'getChartData':
        getChartData($booking);
        break;
    case 'getQuickStats':
        getQuickStats($user, $room, $booking);
        break;
    default:
        getDashboardData($user, $room, $booking);
}

function getDashboardData($user, $room, $booking) {
    try {
        // Lấy thống kê users
        $userStats = $user->getStats();
        
        // Lấy thống kê bookings
        $bookingStats = getBookingStats($booking);
        
        // Lấy thống kê rooms
        $roomStats = $room->getStats();
        
        // Lấy recent bookings
        $recentBookings = getRecentBookings($booking, 10);
        
        // Lấy chart data
        $chartData = getRevenueChartData($booking);
        
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

function getBookingStats($booking) {
    $db = $booking->conn;
    
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
    
    return [
        'total_bookings' => $total,
        'today_bookings' => $todayCount,
        'today_revenue' => $todayRevenue,
        'total_revenue' => $totalRevenue
    ];
}

function getRecentBookings($booking, $limit = 10) {
    $db = $booking->conn;
    
    $query = "SELECT 
                b.id, b.booking_code, b.customer_name, b.customer_email,
                b.check_in_date, b.check_out_date, b.total_price, b.status,
                b.created_at,
                GROUP_CONCAT(r.room_number) as rooms
              FROM bookings b
              LEFT JOIN booking_rooms br ON b.id = br.booking_id
              LEFT JOIN rooms r ON br.room_id = r.id
              GROUP BY b.id
              ORDER BY b.created_at DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRevenueChartData($booking) {
    $db = $booking->conn;
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
            'date' => $date,
            'revenue' => $revenue
        ];
    }
    
    return $data;
}

function getQuickStats($user, $room, $booking) {
    try {
        $userStats = $user->getStats();
        $bookingStats = getBookingStats($booking);
        $roomStats = $room->getStats();
        
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
                'occupancy_rate' => $roomStats['occupancy_rate'] ?? 0
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

function getChartData($booking) {
    $period = $_GET['period'] ?? 'week';
    $db = $booking->conn;
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
                'date' => $date,
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
?>