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

try {
    // Overview numbers
    $overview = [
        'total_rooms' => (int)$db->query("SELECT COUNT(*) AS c FROM rooms")->fetch(PDO::FETCH_ASSOC)['c'],
        'today_bookings' => (int)$db->query("SELECT COUNT(*) AS c FROM bookings WHERE DATE(check_in) = CURDATE()")->fetch(PDO::FETCH_ASSOC)['c'],
        'total_customers' => (int)$db->query("SELECT COUNT(*) AS c FROM users WHERE user_type = 'customer' AND status != 'deleted'")->fetch(PDO::FETCH_ASSOC)['c'],
        'monthly_revenue' => (float)$db->query("SELECT IFNULL(SUM(total_price),0) AS s FROM bookings WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['s'],
        'service_revenue' => 0,
    ];

    // Occupancy
    $occupancyStats = [
        'current_occupancy_rate' => 0,
        'occupancy_by_type' => [],
    ];
    $occupancyQuery = $db->query("SELECT rt.type_name, COUNT(r.id) AS total_rooms,
                                         SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms
                                  FROM rooms r
                                  LEFT JOIN room_types rt ON rt.id = r.room_type_id
                                  GROUP BY rt.type_name");
    $occupancyData = $occupancyQuery->fetchAll(PDO::FETCH_ASSOC);
    foreach ($occupancyData as $row) {
        $occupancyStats['occupancy_by_type'][] = [
            'type_name' => $row['type_name'] ?? 'Khác',
            'total_rooms' => (int)$row['total_rooms'],
            'occupied_rooms' => (int)$row['occupied_rooms'],
        ];
    }
    $totalRooms = array_sum(array_column($occupancyStats['occupancy_by_type'], 'total_rooms'));
    $totalOccupied = array_sum(array_column($occupancyStats['occupancy_by_type'], 'occupied_rooms'));
    $occupancyStats['current_occupancy_rate'] = $totalRooms ? round($totalOccupied / $totalRooms * 100) : 0;

    // Time stats for charts: monthly revenue current year
    $timeStats = ['monthly_revenue' => []];
    $stmt = $db->query("SELECT DATE_FORMAT(created_at,'%m') AS month, SUM(total_price) AS revenue
                        FROM bookings
                        WHERE YEAR(created_at)=YEAR(CURDATE())
                        GROUP BY DATE_FORMAT(created_at,'%m')
                        ORDER BY month");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $timeStats['monthly_revenue'][] = [
            'month' => $row['month'],
            'revenue' => (float)$row['revenue'],
        ];
    }

    // Recent activities: today checkins / checkouts
    $recent = [
        'today_checkins' => [],
        'today_checkouts' => [],
    ];
    $checkinStmt = $db->query("SELECT b.check_in, b.num_guests, r.room_number, u.full_name as customer_name
                               FROM bookings b
                               LEFT JOIN rooms r ON r.id = b.room_id
                               LEFT JOIN users u ON u.id = b.customer_id
                               WHERE DATE(b.check_in) = CURDATE()");
    $recent['today_checkins'] = $checkinStmt->fetchAll(PDO::FETCH_ASSOC);

    $checkoutStmt = $db->query("SELECT b.check_out, b.total_price, r.room_number, u.full_name as customer_name
                                FROM bookings b
                                LEFT JOIN rooms r ON r.id = b.room_id
                                LEFT JOIN users u ON u.id = b.customer_id
                                WHERE DATE(b.check_out) = CURDATE()");
    $recent['today_checkouts'] = $checkoutStmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenue by type pie
    $revenueByType = [];
    $revTypeStmt = $db->query("SELECT rt.type_name, SUM(b.total_price) AS revenue
                               FROM bookings b
                               LEFT JOIN rooms r ON r.id = b.room_id
                               LEFT JOIN room_types rt ON rt.id = r.room_type_id
                               WHERE YEAR(b.created_at)=YEAR(CURDATE())
                               GROUP BY rt.type_name");
    while ($row = $revTypeStmt->fetch(PDO::FETCH_ASSOC)) {
        $revenueByType[] = [
            'type_name' => $row['type_name'] ?? 'Khác',
            'revenue' => (float)$row['revenue'],
        ];
    }

    // Payment methods pie
    $paymentMethods = [];
    $payStmt = $db->query("SELECT payment_method as method, COUNT(*) as count
                           FROM bookings
                           WHERE payment_method IS NOT NULL
                           GROUP BY payment_method");
    $paymentMethods = $payStmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'data' => [
            'overview' => $overview,
            'occupancy_stats' => $occupancyStats,
            'time_stats' => $timeStats,
            'recent_activities' => $recent,
            'revenue_by_type' => $revenueByType,
            'payment_methods' => $paymentMethods,
            'yearly_revenue' => $timeStats['monthly_revenue'],
        ],
    ];

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

