<?php
// quick-fix.php - Phiên bản hoàn chỉnh
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock classes
class MockRoom {
    public function getById($id) {
        // Trả về array thay vì PDOStatement
        return [
            'id' => $id,
            'room_number' => '101',
            'room_type' => 'Deluxe Room',
            'status' => 'available',
            'capacity' => 2,
            'price_per_night' => 1000000,
            'image_url' => 'https://via.placeholder.com/150'
        ];
    }
    
    public function updateStatus($room_id, $status) {
        error_log("MockRoom: Updating room $room_id to $status");
        return true;
    }
}

class MockBooking {
    public $id;
    
    public function create($data) {
        $this->id = rand(1000, 9999);
        error_log("MockBooking: Created booking ID " . $this->id);
        return true;
    }
}

class SimpleBookingController {
    private $booking;
    private $room;
    
    public function __construct() {
        $this->booking = new MockBooking();
        $this->room = new MockRoom();
    }
    
    private function authenticateUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        error_log("Auth header: " . substr($authHeader, 0, 50) . "...");
        
        if (strpos($authHeader, 'Bearer ') !== 0) {
            error_log("Auth failed: No Bearer token");
            return null;
        }
        
        $token = str_replace('Bearer ', '', $authHeader);
        error_log("Token received: " . substr($token, 0, 30) . "...");
        
        try {
            // Decode base64
            $decoded = base64_decode($token);
            error_log("Decoded token length: " . strlen($decoded));
            
            // Try to decode as JSON
            $userData = json_decode($decoded, true);
            
            if ($userData && isset($userData['id']) && isset($userData['isLoggedIn'])) {
                error_log("User authenticated: " . $userData['full_name']);
                return $userData;
            } else {
                error_log("Invalid user data structure");
                return null;
            }
        } catch (Exception $e) {
            error_log("Token decode error: " . $e->getMessage());
            return null;
        }
    }
    
    public function create() {
        error_log("=== CREATE BOOKING CALLED ===");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("GET params: " . print_r($_GET, true));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Method not allowed. Use POST.', [], 405);
            return;
        }
        
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Authentication failed. Please login.', [], 401);
            return;
        }
        
        // Đọc dữ liệu POST
        $input = file_get_contents('php://input');
        error_log("Raw input: " . $input);
        
        $data = json_decode($input, true);
        
        if (!$data) {
            $this->jsonResponse(false, 'Invalid JSON data', [], 400);
            return;
        }
        
        error_log("Parsed data: " . print_r($data, true));
        
        // Kiểm tra required fields
        $required = ['room_id', 'check_in', 'check_out', 'num_guests', 'total_price'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->jsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), [], 400);
            return;
        }
        
        // Kiểm tra phòng
        $room = $this->room->getById($data['room_id']);
        
        if (!$room) {
            $this->jsonResponse(false, 'Room not found', [], 404);
            return;
        }
        
        if ($room['status'] !== 'available') {
            $this->jsonResponse(false, 'Room is not available', [], 400);
            return;
        }
        
        // Tạo booking
        $result = $this->booking->create($data);
        
        if ($result) {
            $this->jsonResponse(true, 'Booking created successfully!', [
                'booking_id' => $this->booking->id,
                'booking_code' => 'BK' . date('YmdHis') . rand(1000, 9999),
                'room_number' => $room['room_number'],
                'room_type' => $room['room_type'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'total_price' => $data['total_price'],
                'customer_name' => $user['full_name'] ?? 'Customer',
                'debug_info' => [
                    'user_id' => $user['id'],
                    'room_id' => $data['room_id'],
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            $this->jsonResponse(false, 'Failed to create booking', [], 500);
        }
    }
    
    private function jsonResponse($success, $message, $data = [], $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Xử lý request
error_log("=== NEW REQUEST ===");
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET: " . print_r($_GET, true));

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'create') {
    $controller = new SimpleBookingController();
    $controller->create();
} else {
    // Trả về thông báo rõ ràng hơn
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Use: POST /quick-fix.php?action=create',
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'action_received' => $action,
            'required_method' => 'POST',
            'required_action' => 'create'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>