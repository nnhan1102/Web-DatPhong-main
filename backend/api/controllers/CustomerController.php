<?php
// controllers/CustomerController.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/Database.php';
require_once '../models/User.php';
require_once 'AuthController.php';

class CustomerController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Lấy danh sách tất cả khách hàng (chỉ admin)
    public function getAllCustomers() {
        // Check admin authentication
        session_start();
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Không có quyền truy cập', [], 403);
            return;
        }

        // Get query parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'limit' => $limit,
            'offset' => $offset
        ];

        // Lấy dữ liệu
        $customers = $this->user->getAll($filters);
        $total = $this->user->countAll($filters);
        
        // Tính toán pagination
        $totalPages = ceil($total / $limit);

        $this->jsonResponse(true, 'Lấy danh sách khách hàng thành công', [
            'customers' => $customers,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
    }

    // Lấy thông tin chi tiết khách hàng
    public function getCustomerById() {
        $id = $_GET['id'] ?? 0;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(false, 'ID khách hàng không hợp lệ', [], 400);
            return;
        }

        session_start();
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['user_type'] ?? '';
        
        // Chỉ admin hoặc chính user đó mới được xem
        if ($user_type !== 'admin' && $user_id != $id) {
            $this->jsonResponse(false, 'Không có quyền xem thông tin này', [], 403);
            return;
        }

        $customer = $this->user->getById($id);
        
        if ($customer) {
            $this->jsonResponse(true, 'Lấy thông tin khách hàng thành công', [
                'customer' => $customer
            ]);
        } else {
            $this->jsonResponse(false, 'Không tìm thấy khách hàng', [], 404);
        }
    }

    // Cập nhật thông tin khách hàng
    public function updateCustomer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id) {
            $this->jsonResponse(false, 'Thiếu ID khách hàng', [], 400);
            return;
        }

        // Lấy dữ liệu
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $data = $_POST;
        }

        // Kiểm tra quyền
        session_start();
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['user_type'] ?? '';
        
        // Chỉ admin hoặc chính user đó mới được cập nhật
        if ($user_type !== 'admin' && $user_id != $id) {
            $this->jsonResponse(false, 'Không có quyền cập nhật', [], 403);
            return;
        }

        // Set default values
        $data['full_name'] = $data['full_name'] ?? '';
        $data['phone'] = $data['phone'] ?? '';
        $data['address'] = $data['address'] ?? '';
        $data['status'] = $data['status'] ?? 'active';

        // Admin có thể cập nhật status, user bình thường không thể
        if ($user_type !== 'admin') {
            unset($data['status']);
        }

        // Cập nhật thông tin
        $result = $this->user->update($id, $data);
        
        if ($result) {
            $this->jsonResponse(true, 'Cập nhật thông tin thành công');
        } else {
            $this->jsonResponse(false, 'Cập nhật thất bại', [], 500);
        }
    }

    // Xóa khách hàng (chỉ admin)
    public function deleteCustomer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id) {
            $this->jsonResponse(false, 'Thiếu ID khách hàng', [], 400);
            return;
        }

        // Check admin permission
        session_start();
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Không có quyền xóa', [], 403);
            return;
        }

        // Xóa khách hàng
        $result = $this->user->delete($id);
        
        if ($result) {
            $this->jsonResponse(true, 'Xóa khách hàng thành công');
        } else {
            $this->jsonResponse(false, 'Xóa thất bại', [], 500);
        }
    }

    // Cập nhật trạng thái khách hàng (chỉ admin)
    public function updateCustomerStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id) {
            $this->jsonResponse(false, 'Thiếu ID khách hàng', [], 400);
            return;
        }

        // Check admin permission
        session_start();
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Không có quyền', [], 403);
            return;
        }

        // Lấy dữ liệu
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $data = $_POST;
        }

        // Validate status
        $allowedStatus = ['active', 'inactive'];
        if (!isset($data['status']) || !in_array($data['status'], $allowedStatus)) {
            $this->jsonResponse(false, 'Trạng thái không hợp lệ', [], 400);
            return;
        }

        // Update status
        $updateData = [
            'full_name' => '', // giữ nguyên
            'phone' => '', // giữ nguyên
            'address' => '', // giữ nguyên
            'status' => $data['status']
        ];
        
        $result = $this->user->update($id, $updateData);
        
        if ($result) {
            $this->jsonResponse(true, 'Cập nhật trạng thái thành công');
        } else {
            $this->jsonResponse(false, 'Cập nhật thất bại', [], 500);
        }
    }

    // Tìm kiếm khách hàng
    public function searchCustomers() {
        $keyword = $_GET['keyword'] ?? '';
        
        if (strlen($keyword) < 2) {
            $this->jsonResponse(true, 'Thành công', ['customers' => []]);
            return;
        }

        session_start();
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Không có quyền tìm kiếm', [], 403);
            return;
        }

        $customers = $this->user->search($keyword);
        
        $this->jsonResponse(true, 'Tìm kiếm thành công', [
            'customers' => $customers,
            'count' => count($customers)
        ]);
    }

    // Lấy thống kê khách hàng (chỉ admin)
    public function getCustomerStats() {
        session_start();
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Không có quyền', [], 403);
            return;
        }

        $stats = $this->user->getStats();
        
        $this->jsonResponse(true, 'Lấy thống kê thành công', [
            'stats' => $stats
        ]);
    }

    // Helper function cho JSON response
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

// Khởi tạo controller
$customer = new CustomerController();

switch ($action) {
    case 'getAll':
        $customer->getAllCustomers();
        break;
        
    case 'getById':
        $customer->getCustomerById();
        break;
        
    case 'update':
        $customer->updateCustomer();
        break;
        
    case 'delete':
        $customer->deleteCustomer();
        break;
        
    case 'updateStatus':
        $customer->updateCustomerStatus();
        break;
        
    case 'search':
        $customer->searchCustomers();
        break;
        
    case 'getStats':
        $customer->getCustomerStats();
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ',
            'valid_actions' => [
                'getAll', 
                'getById', 
                'update', 
                'delete', 
                'updateStatus', 
                'search', 
                'getStats'
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;
}
?>