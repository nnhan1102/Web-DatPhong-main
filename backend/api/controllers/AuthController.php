<?php
// backend/api/controllers/AuthController.php

// CORS headers đầu tiên
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Xác định đường dẫn đúng cho cấu trúc của bạn
$baseDir = __DIR__ . '/../'; // Lùi về thư mục backend/api/
$configPath = $baseDir . 'config/database.php';
$userModelPath = $baseDir . 'models/User.php';

// Debug: Ghi log đường dẫn
error_log("Looking for config at: " . $configPath);
error_log("Looking for model at: " . $userModelPath);

// Kiểm tra file tồn tại
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Config file not found',
        'path' => $configPath,
        'current_dir' => __DIR__,
        'files_in_dir' => scandir(__DIR__)
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!file_exists($userModelPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'User model file not found',
        'path' => $userModelPath
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once $configPath;
require_once $userModelPath;

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->user = new User($this->db);
            error_log("AuthController initialized successfully");
        } catch (Exception $e) {
            error_log("AuthController construct error: " . $e->getMessage());
            $this->jsonResponse(false, "Database connection failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
            exit();
        }
    }

    // Xử lý đăng ký - SIÊU ĐƠN GIẢN
    public function register() {
        error_log("=== REGISTER START ===");
        
        try {
            // Lấy input
            $input = file_get_contents("php://input");
            error_log("Raw input: " . $input);
            
            $data = json_decode($input, true);
            
            // Nếu không phải JSON, thử POST
            if (!$data) {
                $data = $_POST;
                error_log("Using POST data");
            }
            
            error_log("Parsed data: " . print_r($data, true));
            
            // Kiểm tra dữ liệu bắt buộc
            if (empty($data['username']) || empty($data['email']) || 
                empty($data['password']) || empty($data['full_name']) || 
                empty($data['phone'])) {
                
                $this->jsonResponse(false, "Thiếu thông tin bắt buộc", [
                    'required_fields' => ['username', 'email', 'password', 'full_name', 'phone'],
                    'received' => array_keys($data)
                ], 400);
                return;
            }
            
            // Kiểm tra email đơn giản
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(false, "Email không hợp lệ", [], 400);
                return;
            }
            
            // Kiểm tra trùng email/username
            if ($this->user->emailExists($data['email'])) {
                $this->jsonResponse(false, "Email đã được đăng ký", [], 400);
                return;
            }
            
            if ($this->user->usernameExists($data['username'])) {
                $this->jsonResponse(false, "Tên đăng nhập đã tồn tại", [], 400);
                return;
            }
            
            // Đăng ký user
            $result = $this->user->register($data);
            
            if ($result) {
                // Tạo session
                session_start();
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_email'] = $result['email'];
                
                error_log("Register successful for user: " . $result['email']);
                
                $this->jsonResponse(true, "Đăng ký thành công", [
                    'user' => $result,
                    'session_id' => session_id()
                ], 201);
            } else {
                error_log("Register failed in User model");
                $this->jsonResponse(false, "Đăng ký thất bại. Vui lòng thử lại.", [], 500);
            }
            
        } catch (Exception $e) {
            error_log("Register exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $this->jsonResponse(false, "Lỗi server", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
        
        error_log("=== REGISTER END ===");
    }

    // Xử lý đăng nhập
    public function login() {
        try {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            if (empty($data['identifier']) || empty($data['password'])) {
                $this->jsonResponse(false, "Thiếu thông tin đăng nhập", [], 400);
                return;
            }
            
            $user = $this->user->login($data['identifier'], $data['password']);
            
            if ($user) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                
                $this->jsonResponse(true, "Đăng nhập thành công", [
                    'user' => $user,
                    'session_id' => session_id()
                ]);
            } else {
                $this->jsonResponse(false, "Thông tin đăng nhập không chính xác", [], 401);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(false, "Lỗi server: " . $e->getMessage(), [], 500);
        }
    }

    // Lấy user hiện tại
    public function getCurrentUser() {
        try {
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(false, "Chưa đăng nhập", [], 401);
                return;
            }
            
            $user = $this->user->getById($_SESSION['user_id']);
            
            if ($user) {
                $this->jsonResponse(true, "Thành công", ['user' => $user]);
            } else {
                $this->jsonResponse(false, "User không tồn tại", [], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(false, "Lỗi server", [], 500);
        }
    }

    // Đăng xuất
    public function logout() {
        try {
            session_start();
            session_destroy();
            $this->jsonResponse(true, "Đã đăng xuất");
        } catch (Exception $e) {
            $this->jsonResponse(false, "Lỗi server", [], 500);
        }
    }

    // Helper function
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
$action = $_GET['action'] ?? '';

error_log("AuthController called with action: " . $action);

try {
    $auth = new AuthController();
    
    switch ($action) {
        case 'register':
            $auth->register();
            break;
            
        case 'login':
            $auth->login();
            break;
            
        case 'logout':
            $auth->logout();
            break;
            
        case 'me':
            $auth->getCurrentUser();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ. Các action hợp lệ: register, login, logout, me',
                'requested_action' => $action
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    error_log("Controller initialization failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>