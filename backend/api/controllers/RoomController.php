<?php
// RoomController.php - FIXED WITH CORRECT PATH

error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ĐƯỜNG DẪN CHÍNH XÁC DỰA TRÊN CẤU TRÚC THƯ MỤC CỦA BẠN
    // RoomController.php nằm ở: backend/api/controllers/
    // database.php nằm ở: backend/api/config/
    // Room.php nằm ở: backend/api/models/
    
    // Đường dẫn tương đối từ controllers đến config
    $database_path = __DIR__ . '/../config/database.php'; // controllers -> api/config
    
    // Đường dẫn tương đối từ controllers đến models  
    $model_path = __DIR__ . '/../models/Room.php'; // controllers -> api/models
    
    // Kiểm tra file tồn tại
    if (!file_exists($database_path)) {
        throw new Exception("Database config not found at: " . $database_path);
    }
    
    if (!file_exists($model_path)) {
        throw new Exception("Room model not found at: " . $model_path);
    }
    
    // Include files
    require_once $database_path;
    require_once $model_path;
    
    // Tạo kết nối database
    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);
    
    // Lấy action từ query string
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action parameter is required',
            'valid_actions' => ['getAll', 'getById', 'getAvailable', 'getByStatus', 'statistics']
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Xử lý các action
    switch ($action) {
        case 'getAll':
            $rooms = $room->getAll();
            echo json_encode([
                'success' => true,
                'message' => 'Rooms retrieved successfully',
                'data' => $rooms,
                'count' => count($rooms)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'getById':
            $id = $_GET['id'] ?? 0;
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Valid Room ID is required'
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            $roomData = $room->getById($id);
            if ($roomData) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Room retrieved successfully',
                    'data' => $roomData
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Room not found'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'getAvailable':
            $rooms = $room->getAvailableRooms();
            echo json_encode([
                'success' => true,
                'message' => 'Available rooms retrieved successfully',
                'data' => $rooms,
                'count' => count($rooms)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'getByStatus':
            $status = $_GET['status'] ?? '';
            $valid_statuses = ['available', 'occupied', 'maintenance', 'cleaning'];
            
            if (!in_array($status, $valid_statuses)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Valid status is required',
                    'valid_statuses' => $valid_statuses
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            $rooms = $room->getByStatus($status);
            echo json_encode([
                'success' => true,
                'message' => 'Rooms retrieved by status',
                'data' => $rooms,
                'count' => count($rooms)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'statistics':
            $stats = $room->getStatistics();
            echo json_encode([
                'success' => true,
                'message' => 'Room statistics retrieved successfully',
                'data' => $stats
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action',
                'valid_actions' => ['getAll', 'getById', 'getAvailable', 'getByStatus', 'statistics']
            ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}