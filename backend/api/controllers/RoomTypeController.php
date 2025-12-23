<?php
// RoomTypeController.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include files
    require_once '../config/Database.php';
    require_once '../models/RoomType.php';
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // RoomType model
    $roomType = new Models\RoomType($db);
    
    // Get action
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'getAll':
            handleGetAll($roomType);
            break;
            
        case 'getById':
            handleGetById($roomType);
            break;
            
        case 'create':
            handleCreate($roomType);
            break;
            
        case 'update':
            handleUpdate($roomType);
            break;
            
        case 'delete':
            handleDelete($roomType);
            break;
            
        case 'getStats':
            handleGetStats($roomType);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ. Các action hỗ trợ: getAll, getById, create, update, delete, getStats'
            ]);
            break;
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}

// ===== HANDLER FUNCTIONS =====

function handleGetAll($roomType) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    $filters = [
        'search' => $_GET['search'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'min_capacity' => $_GET['min_capacity'] ?? null
    ];
    
    $result = $roomType->getAll($page, $limit, $filters);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách loại phòng thành công',
        'data' => $result['data'],
        'pagination' => [
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'total_pages' => $result['total_pages']
        ]
    ]);
}

function handleGetById($roomType) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID là bắt buộc'
        ]);
        return;
    }
    
    $roomTypeData = $roomType->getById($id);
    
    if ($roomTypeData) {
        echo json_encode([
            'success' => true,
            'data' => $roomTypeData
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy loại phòng'
        ]);
    }
}

function handleCreate($roomType) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    $required = ['type_name', 'base_price', 'capacity'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Thiếu thông tin: $field"
            ]);
            return;
        }
    }
    
    // Check if type_name already exists
    if ($roomType->typeNameExists($data['type_name'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Tên loại phòng đã tồn tại'
        ]);
        return;
    }
    
    // Set default values
    $data['description'] = $data['description'] ?? '';
    $data['amenities'] = $data['amenities'] ?? [];
    
    if ($roomType->create($data)) {
        echo json_encode([
            'success' => true,
            'message' => 'Tạo loại phòng thành công',
            'id' => $roomType->id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo loại phòng'
        ]);
    }
}

function handleUpdate($roomType) {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID là bắt buộc'
        ]);
        return;
    }
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Check if type_name already exists (excluding current)
    if (isset($data['type_name'])) {
        if ($roomType->typeNameExists($data['type_name'], $id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Tên loại phòng đã tồn tại'
            ]);
            return;
        }
    }
    
    if ($roomType->update($id, $data)) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật loại phòng thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật loại phòng'
        ]);
    }
}

function handleDelete($roomType) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'ID là bắt buộc'
        ]);
        return;
    }
    
    if ($roomType->delete($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa loại phòng thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa loại phòng (có phòng đang sử dụng loại này)'
        ]);
    }
}

function handleGetStats($roomType) {
    $stats = $roomType->getStats();
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}
?>