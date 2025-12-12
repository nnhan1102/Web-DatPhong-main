<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

$db = (new Database())->getConnection();

try {
    $city = isset($_GET['city']) ? urldecode($_GET['city']) : '';
    $checkIn = isset($_GET['checkIn']) ? $_GET['checkIn'] : date('Y-m-d');
    $checkOut = isset($_GET['checkOut']) ? $_GET['checkOut'] : date('Y-m-d', strtotime('+1 day'));
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $minStars = isset($_GET['minStars']) ? (int)$_GET['minStars'] : 0;
    $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : 10000000;
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereConditions = [];
    $params = [];
    
    if (!empty($city)) {
        $whereConditions[] = "h.city LIKE :city";
        $params[':city'] = '%' . $city . '%';
    }
    
    if ($minStars > 0) {
        $whereConditions[] = "h.stars >= :minStars";
        $params[':minStars'] = $minStars;
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM hotels h $whereClause";
    $stmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalHotels = $totalResult['total'];
    
    // Get hotels with images
    $query = "
        SELECT 
            h.*,
            (SELECT hi.image_url FROM hotel_images hi 
             WHERE hi.hotel_id = h.id AND hi.is_primary = 1 LIMIT 1) as primary_image,
            (SELECT GROUP_CONCAT(hi.image_url) FROM hotel_images hi 
             WHERE hi.hotel_id = h.id) as all_images,
            (SELECT MIN(hr.price_per_night) FROM hotel_rooms hr 
             WHERE hr.hotel_id = h.id AND hr.is_available = 1) as min_price,
            (SELECT COUNT(r.id) FROM hotel_rooms hr 
             LEFT JOIN rooms r ON r.id = hr.room_id 
             WHERE hr.hotel_id = h.id AND r.status = 'available') as rooms_available
        FROM hotels h
        $whereClause
        ORDER BY h.rating DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results
    foreach ($hotels as &$hotel) {
        $hotel['id'] = (int)$hotel['id'];
        $hotel['stars'] = (int)$hotel['stars'];
        $hotel['rating'] = (float)$hotel['rating'];
        $hotel['min_price'] = (float)$hotel['min_price'];
        $hotel['rooms_available'] = (int)$hotel['rooms_available'];
        
        // Parse amenities from JSON
        $hotel['amenities'] = json_decode($hotel['amenities'], true) ?? [];
        
        // Process images
        $images = explode(',', $hotel['all_images'] ?? '');
        $hotel['images'] = array_filter($images);
        if (!empty($hotel['primary_image'])) {
            array_unshift($hotel['images'], $hotel['primary_image']);
        }
        $hotel['images'] = array_unique($hotel['images']);
        
        unset($hotel['all_images']);
    }
    
    $response = [
        'success' => true,
        'data' => $hotels,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalHotels,
            'pages' => ceil($totalHotels / $limit)
        ],
        'search_params' => [
            'city' => $city,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching hotels: ' . $e->getMessage(),
        'data' => []
    ]);
}