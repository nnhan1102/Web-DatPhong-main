<?php
// ServiceController.php

// Bật error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers - ĐẶT TRƯỚC MỌI OUTPUT
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// DEBUG: Log incoming request
error_log("ServiceController accessed: " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET params: " . print_r($_GET, true));
error_log("POST data: " . file_get_contents('php://input'));

// Kết nối database
try {
    require_once '../config/Database.php';
    require_once '../models/Service.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $service = new Service($db);
    
    // Lấy action từ GET hoặc POST
    $action = isset($_GET['action']) ? $_GET['action'] : 
              (isset($_POST['action']) ? $_POST['action'] : '');
    
    // Debug action
    error_log("Action received: " . $action);
    
    // Xử lý action
    switch (strtolower($action)) {
        case 'getall':
        case 'get_all':
            handleGetAllServices($service);
            break;
            
        case 'getbyid':
        case 'get_by_id':
        case 'getbyid':
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
            handleGetServiceById($service, $id);
            break;
            
        case 'getbycategory':
        case 'get_by_category':
            handleGetServicesByCategory($service);
            break;
            
        case 'create':
        case 'add':
            handleCreateService($service);
            break;
            
        case 'update':
        case 'edit':
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
            handleUpdateService($service, $id);
            break;
            
        case 'delete':
        case 'remove':
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
            handleDeleteService($service, $id);
            break;
            
        case 'updatestatus':
        case 'update_status':
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
            handleUpdateServiceStatus($service, $id);
            break;
            
        case 'getstats':
        case 'get_stats':
            handleGetServiceStats($service);
            break;
            
        case 'getpopular':
        case 'get_popular':
            handleGetPopularServices($service);
            break;
            
        case 'search':
            handleSearchServices($service);
            break;
            
        default:
            // Nếu không có action, trả về tất cả dịch vụ (để test)
            if (empty($action)) {
                handleGetAllServices($service);
            } else {
                error_log("Invalid action: " . $action);
                echo jsonResponse(false, 'Action không hợp lệ: ' . $action, [], 400);
            }
            break;
    }
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo jsonResponse(false, 'Database Error: ' . $e->getMessage(), [], 500);
} catch(Exception $e) {
    error_log("Server Error: " . $e->getMessage());
    echo jsonResponse(false, 'Server Error: ' . $e->getMessage(), [], 500);
}

// Hàm xử lý lấy tất cả dịch vụ
function handleGetAllServices($service) {
    try {
        // Lấy parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        
        // Tính toán offset
        $offset = ($page - 1) * $limit;
        
        // Build filters
        $filters = [
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'sort' => $sort,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        // Lấy dữ liệu
        $services = $service->getAllServices($filters);
        $total = $service->countServices($filters);
        
        // Tính toán pagination
        $totalPages = ceil($total / $limit);
        
        echo jsonResponse(true, 'Lấy danh sách dịch vụ thành công', [
            'data' => $services,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
        
    } catch(Exception $e) {
        error_log("Error in handleGetAllServices: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý lấy dịch vụ theo ID
function handleGetServiceById($service, $id) {
    if (!$id) {
        echo jsonResponse(false, 'Thiếu ID dịch vụ', [], 400);
        return;
    }
    
    try {
        $serviceData = $service->getServiceById($id);
        
        if ($serviceData) {
            echo jsonResponse(true, 'Lấy thông tin dịch vụ thành công', $serviceData);
        } else {
            echo jsonResponse(false, 'Không tìm thấy dịch vụ', [], 404);
        }
    } catch(Exception $e) {
        error_log("Error in handleGetServiceById: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý lấy dịch vụ theo category
function handleGetServicesByCategory($service) {
    try {
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        
        if (empty($category)) {
            echo jsonResponse(false, 'Thiếu category', [], 400);
            return;
        }
        
        $services = $service->getServicesByCategory($category);
        
        echo jsonResponse(true, 'Lấy dịch vụ theo category thành công', $services);
    } catch(Exception $e) {
        error_log("Error in handleGetServicesByCategory: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý tạo dịch vụ mới
function handleCreateService($service) {
    try {
        // Lấy dữ liệu từ request
        $data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Nếu là form data
            if (!empty($_POST)) {
                $data = $_POST;
            } 
            // Nếu là JSON
            else {
                $jsonData = file_get_contents('php://input');
                if (!empty($jsonData)) {
                    $data = json_decode($jsonData, true);
                }
            }
        } else {
            echo jsonResponse(false, 'Phương thức không hợp lệ. Yêu cầu POST', [], 405);
            return;
        }
        
        if (empty($data)) {
            echo jsonResponse(false, 'Dữ liệu không hợp lệ', [], 400);
            return;
        }
        
        // Log dữ liệu nhận được
        error_log("Create service data: " . print_r($data, true));
        
        // Validate required fields
        $required = ['service_name', 'price'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo jsonResponse(false, "Thiếu trường bắt buộc: $field", [], 400);
                return;
            }
        }
        
        // Validate price
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            echo jsonResponse(false, 'Giá không hợp lệ', [], 400);
            return;
        }
        
        // Validate category
        $allowedCategories = ['transport', 'food', 'spa', 'other'];
        if (isset($data['category']) && !in_array($data['category'], $allowedCategories)) {
            echo jsonResponse(false, 'Category không hợp lệ', [], 400);
            return;
        }
        
        // Tạo dịch vụ
        $result = $service->createService($data);
        
        if ($result) {
            echo jsonResponse(true, 'Tạo dịch vụ thành công', ['id' => $db->lastInsertId()], 201);
        } else {
            echo jsonResponse(false, 'Không thể tạo dịch vụ', [], 500);
        }
    } catch(Exception $e) {
        error_log("Error in handleCreateService: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý cập nhật dịch vụ
function handleUpdateService($service, $id) {
    if (!$id) {
        echo jsonResponse(false, 'Thiếu ID dịch vụ', [], 400);
        return;
    }
    
    try {
        // Lấy dữ liệu từ request
        $data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Nếu là form data
            if (!empty($_POST)) {
                $data = $_POST;
            } 
            // Nếu là JSON
            else {
                $jsonData = file_get_contents('php://input');
                if (!empty($jsonData)) {
                    $data = json_decode($jsonData, true);
                }
            }
        } else {
            echo jsonResponse(false, 'Phương thức không hợp lệ. Yêu cầu PUT hoặc POST', [], 405);
            return;
        }
        
        if (empty($data)) {
            echo jsonResponse(false, 'Dữ liệu không hợp lệ', [], 400);
            return;
        }
        
        // Kiểm tra dịch vụ tồn tại
        $existing = $service->getServiceById($id);
        if (!$existing) {
            echo jsonResponse(false, 'Không tìm thấy dịch vụ', [], 404);
            return;
        }
        
        // Validate price nếu có
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            echo jsonResponse(false, 'Giá không hợp lệ', [], 400);
            return;
        }
        
        // Validate category nếu có
        $allowedCategories = ['transport', 'food', 'spa', 'other'];
        if (isset($data['category']) && !in_array($data['category'], $allowedCategories)) {
            echo jsonResponse(false, 'Category không hợp lệ', [], 400);
            return;
        }
        
        // Cập nhật thông tin
        $result = $service->updateService($id, $data);
        
        if ($result) {
            echo jsonResponse(true, 'Cập nhật dịch vụ thành công');
        } else {
            echo jsonResponse(false, 'Không thể cập nhật dịch vụ', [], 500);
        }
    } catch(Exception $e) {
        error_log("Error in handleUpdateService: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý xóa dịch vụ
function handleDeleteService($service, $id) {
    if (!$id) {
        echo jsonResponse(false, 'Thiếu ID dịch vụ', [], 400);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo jsonResponse(false, 'Phương thức không hợp lệ', [], 405);
        return;
    }
    
    try {
        // Kiểm tra dịch vụ tồn tại
        $existing = $service->getServiceById($id);
        if (!$existing) {
            echo jsonResponse(false, 'Không tìm thấy dịch vụ', [], 404);
            return;
        }
        
        // Xóa dịch vụ
        $result = $service->deleteService($id);
        
        if ($result) {
            echo jsonResponse(true, 'Xóa dịch vụ thành công');
        } else {
            echo jsonResponse(false, 'Không thể xóa dịch vụ', [], 500);
        }
    } catch(Exception $e) {
        error_log("Error in handleDeleteService: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý cập nhật trạng thái dịch vụ
function handleUpdateServiceStatus($service, $id) {
    if (!$id) {
        echo jsonResponse(false, 'Thiếu ID dịch vụ', [], 400);
        return;
    }
    
    try {
        // Lấy dữ liệu từ request
        $data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Nếu là form data
            if (!empty($_POST)) {
                $data = $_POST;
            } 
            // Nếu là JSON
            else {
                $jsonData = file_get_contents('php://input');
                if (!empty($jsonData)) {
                    $data = json_decode($jsonData, true);
                }
            }
        } else {
            echo jsonResponse(false, 'Phương thức không hợp lệ. Yêu cầu PUT hoặc POST', [], 405);
            return;
        }
        
        if (!isset($data['status'])) {
            echo jsonResponse(false, 'Thiếu trạng thái', [], 400);
            return;
        }
        
        $status = $data['status'];
        $allowedStatus = ['available', 'unavailable'];
        
        if (!in_array($status, $allowedStatus)) {
            echo jsonResponse(false, 'Trạng thái không hợp lệ', [], 400);
            return;
        }
        
        // Cập nhật trạng thái
        $result = $service->updateStatus($id, $status);
        
        if ($result) {
            echo jsonResponse(true, 'Cập nhật trạng thái thành công');
        } else {
            echo jsonResponse(false, 'Không thể cập nhật trạng thái', [], 500);
        }
    } catch(Exception $e) {
        error_log("Error in handleUpdateServiceStatus: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý lấy thống kê dịch vụ
function handleGetServiceStats($service) {
    try {
        $stats = $service->getServiceStats();
        
        echo jsonResponse(true, 'Lấy thống kê dịch vụ thành công', $stats);
    } catch(Exception $e) {
        error_log("Error in handleGetServiceStats: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý lấy dịch vụ phổ biến
function handleGetPopularServices($service) {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        $services = $service->getPopularServices($limit);
        
        echo jsonResponse(true, 'Lấy dịch vụ phổ biến thành công', $services);
    } catch(Exception $e) {
        error_log("Error in handleGetPopularServices: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm xử lý tìm kiếm dịch vụ
function handleSearchServices($service) {
    try {
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        
        if (empty($keyword)) {
            echo jsonResponse(true, 'Thành công', []);
            return;
        }
        
        $results = $service->searchServices($keyword);
        
        echo jsonResponse(true, 'Tìm kiếm dịch vụ thành công', $results);
    } catch(Exception $e) {
        error_log("Error in handleSearchServices: " . $e->getMessage());
        echo jsonResponse(false, 'Lỗi: ' . $e->getMessage(), [], 500);
    }
}

// Hàm trả về JSON response
function jsonResponse($success, $message, $data = [], $code = 200) {
    http_response_code($code);
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'code' => $code
    ];
    
    // Log response
    error_log("JSON Response: " . json_encode($response));
    
    return json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>