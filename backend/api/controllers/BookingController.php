<?php
// controllers/BookingController.php - VERSION ĐẦY ĐỦ CHO CUSTOMER VÀ ADMIN

// Bật debug ở đầu file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/booking_errors.log');

// Log request để debug
file_put_contents(__DIR__ . '/booking_debug.log', 
    "\n=== " . date('Y-m-d H:i:s') . " =================================\n" .
    "Method: " . $_SERVER['REQUEST_METHOD'] . "\n" .
    "URI: " . $_SERVER['REQUEST_URI'] . "\n" .
    "GET: " . print_r($_GET, true) . "\n" .
    "Headers: " . json_encode(getallheaders(), JSON_PRETTY_PRINT) . "\n" .
    "Input: " . file_get_contents('php://input') . "\n",
    FILE_APPEND
);

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
require_once '../models/Booking.php';
require_once '../models/Room.php';
require_once '../models/Customer.php';

class BookingController {
    private $db;
    private $booking;
    private $room;
    private $customer;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->booking = new Booking($this->db);
            $this->room = new Room($this->db);
            $this->customer = new Customer($this->db);
            
            // Log thành công
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Database connection established\n",
                FILE_APPEND
            );
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Database connection failed: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            throw $e;
        }
    }

    private function authenticateUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "Auth header received: " . substr($authHeader, 0, 50) . "...\n",
            FILE_APPEND
        );
        
        if (strpos($authHeader, 'Bearer ') !== 0) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Auth failed: No Bearer token\n",
                FILE_APPEND
            );
            return null;
        }
        
        $token = str_replace('Bearer ', '', $authHeader);
        
        // Giải mã token
        try {
            // Decode base64
            $decoded = base64_decode($token);
            
            if (!$decoded) {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Token base64 decode failed\n",
                    FILE_APPEND
                );
                return null;
            }
            
            // Thử decode với urldecode trước
            $jsonStr = urldecode($decoded);
            $userData = json_decode($jsonStr, true);
            
            // Nếu không được, thử decode trực tiếp
            if (!$userData || json_last_error() !== JSON_ERROR_NONE) {
                $userData = json_decode($decoded, true);
            }
            
            if ($userData && isset($userData['id']) && isset($userData['isLoggedIn'])) {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "User authenticated: " . ($userData['full_name'] ?? 'Unknown') . "\n",
                    FILE_APPEND
                );
                return $userData;
            } else {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Invalid user data structure\n",
                    FILE_APPEND
                );
                return null;
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Token decode error: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return null;
        }
    }

    // ========== ADMIN METHODS ==========

    // Lấy tất cả bookings (cho admin)
    public function getAll() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== GET ALL BOOKINGS (ADMIN) CALLED ===\n",
            FILE_APPEND
        );
        
        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập', [], 401);
            return;
        }

        // Chỉ admin mới được xem tất cả bookings
        if ($user['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Chỉ admin mới có quyền xem tất cả đặt phòng', [], 403);
            return;
        }

        // Lấy parameters từ query string
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

        try {
            // Gọi phương thức trong model để lấy tất cả bookings
            $bookings = $this->booking->getAllBookings($search, $status, $date_from, $date_to, $limit, $offset);
            $total = $this->booking->countAllBookings($search, $status, $date_from, $date_to);
            
            // Tính toán pagination
            $totalPages = ceil($total / $limit);

            $this->jsonResponse(true, 'Lấy danh sách đặt phòng thành công', [
                'data' => $bookings,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Error in getAll: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // Cập nhật booking (cho admin)
    public function update() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== UPDATE BOOKING (ADMIN) CALLED ===\n",
            FILE_APPEND
        );
        
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'PUT' && $method !== 'POST') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập', [], 401);
            return;
        }

        // Chỉ admin mới được cập nhật booking
        if ($user['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Chỉ admin mới có quyền cập nhật đặt phòng', [], 403);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(false, 'ID booking không hợp lệ', [], 400);
            return;
        }

        // Lấy dữ liệu
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "Update data for booking $id: " . print_r($data, true) . "\n",
            FILE_APPEND
        );
        
        if (!$data) {
            $this->jsonResponse(false, 'Dữ liệu không hợp lệ', [], 400);
            return;
        }

        // Kiểm tra booking có tồn tại không
        $existingBooking = $this->booking->getById($id);
        if (!$existingBooking) {
            $this->jsonResponse(false, 'Không tìm thấy booking', [], 404);
            return;
        }

        // Nếu cập nhật trạng thái booking
        if (isset($data['status']) && $data['status'] !== $existingBooking['status']) {
            $oldStatus = $existingBooking['status'];
            $newStatus = $data['status'];
            
            // Logic cập nhật trạng thái phòng
            if (($oldStatus === 'cancelled' || $oldStatus === 'checked_out') && 
                ($newStatus === 'confirmed' || $newStatus === 'checked_in' || $newStatus === 'pending')) {
                // Từ cancelled/checked_out sang active -> cập nhật phòng thành occupied
                $this->room->updateStatus($existingBooking['room_id'], 'occupied');
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Room status updated to occupied for booking $id\n",
                    FILE_APPEND
                );
            } elseif ($newStatus === 'cancelled' || $newStatus === 'checked_out') {
                // Hủy booking hoặc đã trả phòng -> cập nhật phòng thành available
                $this->room->updateStatus($existingBooking['room_id'], 'available');
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Room status updated to available for booking $id\n",
                    FILE_APPEND
                );
            }
        }

        // Cập nhật booking
        try {
            $result = $this->booking->update($id, $data);
            
            if ($result) {
                $this->jsonResponse(true, 'Cập nhật booking thành công');
            } else {
                $this->jsonResponse(false, 'Cập nhật booking thất bại', [], 500);
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Error updating booking: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // Xóa booking (cho admin)
    public function delete() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== DELETE BOOKING (ADMIN) CALLED ===\n",
            FILE_APPEND
        );
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập', [], 401);
            return;
        }

        // Chỉ admin mới được xóa booking
        if ($user['user_type'] !== 'admin') {
            $this->jsonResponse(false, 'Chỉ admin mới có quyền xóa đặt phòng', [], 403);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id) {
            $this->jsonResponse(false, 'Thiếu ID booking', [], 400);
            return;
        }

        // Kiểm tra booking có tồn tại không
        $existingBooking = $this->booking->getById($id);
        if (!$existingBooking) {
            $this->jsonResponse(false, 'Không tìm thấy booking', [], 404);
            return;
        }

        // Cập nhật trạng thái phòng về available trước khi xóa
        $this->room->updateStatus($existingBooking['room_id'], 'available');

        // Xóa booking
        try {
            $query = "DELETE FROM bookings WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $id);
            $result = $stmt->execute();
            
            if ($result) {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Booking $id deleted successfully\n",
                    FILE_APPEND
                );
                $this->jsonResponse(true, 'Xóa booking thành công');
            } else {
                $this->jsonResponse(false, 'Xóa booking thất bại', [], 500);
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Error deleting booking: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // ========== CUSTOMER METHODS ==========

    // Tạo booking mới (cho customer)
    public function create() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== CREATE BOOKING CALLED ===\n",
            FILE_APPEND
        );
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập để đặt phòng', [], 401);
            return;
        }

        // Kiểm tra user có phải customer không
        if ($user['user_type'] !== 'customer') {
            $this->jsonResponse(false, 'Chỉ khách hàng mới có thể đặt phòng', [], 403);
            return;
        }

        // Lấy dữ liệu
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "Raw input: " . $input . "\n" .
            "Parsed data: " . print_r($data, true) . "\n",
            FILE_APPEND
        );
        
        if (!$data) {
            $this->jsonResponse(false, 'Dữ liệu không hợp lệ', [], 400);
            return;
        }

        // Validate required fields
        $required = ['room_id', 'check_in', 'check_out', 'num_guests', 'total_price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->jsonResponse(false, "Thiếu thông tin bắt buộc: $field", [], 400);
                return;
            }
        }

        // Kiểm tra phòng có tồn tại và available không
        $room = $this->room->getById($data['room_id']);
        
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "Room data: " . print_r($room, true) . "\n",
            FILE_APPEND
        );
        
        if (!$room) {
            $this->jsonResponse(false, 'Phòng không tồn tại', [], 404);
            return;
        }

        // Kiểm tra trạng thái phòng
        if (!isset($room['status']) || $room['status'] !== 'available') {
            $this->jsonResponse(false, 'Phòng hiện không khả dụng. Trạng thái: ' . ($room['status'] ?? 'unknown'), [], 400);
            return;
        }

        // Set customer_id từ user
        $data['customer_id'] = $user['id'];
        
        // Set default values
        $data['status'] = $data['status'] ?? 'pending';
        $data['payment_method'] = $data['payment_method'] ?? 'cash';
        $data['payment_status'] = $data['payment_status'] ?? 'pending';
        $data['special_requests'] = $data['special_requests'] ?? '';
        
        // Generate booking code
        $data['booking_code'] = 'BK' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Validate dates
        $check_in = new DateTime($data['check_in']);
        $check_out = new DateTime($data['check_out']);
        if ($check_in >= $check_out) {
            $this->jsonResponse(false, 'Ngày check-out phải sau ngày check-in', [], 400);
            return;
        }

        // Validate number of guests
        if ($data['num_guests'] > $room['capacity']) {
            $this->jsonResponse(false, 'Số lượng khách vượt quá sức chứa của phòng', [], 400);
            return;
        }

        // Tạo booking
        try {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Attempting to create booking with data: " . print_r($data, true) . "\n",
                FILE_APPEND
            );
            
            $result = $this->booking->create($data);
            
            if ($result) {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Booking created successfully. Booking ID: " . $this->booking->id . "\n",
                    FILE_APPEND
                );
                
                // Cập nhật trạng thái phòng thành occupied
                $updateResult = $this->room->updateStatus($data['room_id'], 'occupied');
                
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Room status update result: " . ($updateResult ? 'success' : 'failed') . "\n",
                    FILE_APPEND
                );
                
                $this->jsonResponse(true, 'Đặt phòng thành công', [
                    'booking_id' => $this->booking->id,
                    'booking_code' => $data['booking_code'],
                    'room_number' => $room['room_number'],
                    'room_type' => $room['room_type'],
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'total_price' => $data['total_price'],
                    'debug_info' => [
                        'room_data_received' => $room,
                        'user_id' => $user['id']
                    ]
                ], 201);
            } else {
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Booking creation failed\n",
                    FILE_APPEND
                );
                $this->jsonResponse(false, 'Đặt phòng thất bại', [], 500);
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Exception in booking creation: " . $e->getMessage() . "\n" .
                "Stack trace: " . $e->getTraceAsString() . "\n",
                FILE_APPEND
            );
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // Lấy danh sách booking của user hiện tại (cho customer)
    public function getMyBookings() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== GET MY BOOKINGS CALLED ===\n",
            FILE_APPEND
        );
        
        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập để xem đặt phòng', [], 401);
            return;
        }

        $user_id = $user['id'];
        
        // Lấy parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        $status = $_GET['status'] ?? '';
        
        // Lấy bookings của user
        try {
            $bookings = $this->booking->getUserBookings($user_id, $status, $limit, $offset);
            $total = $this->booking->countUserBookings($user_id, $status);
            
            // Tính toán pagination
            $totalPages = ceil($total / $limit);

            $this->jsonResponse(true, 'Lấy danh sách đặt phòng thành công', [
                'bookings' => $bookings,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // Lấy chi tiết booking (cho cả customer và admin)
    public function getBookingById() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== GET BOOKING BY ID CALLED ===\n",
            FILE_APPEND
        );
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id || !is_numeric($id)) {
            $this->jsonResponse(false, 'ID booking không hợp lệ', [], 400);
            return;
        }

        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập', [], 401);
            return;
        }

        $user_id = $user['id'];
        $user_type = $user['user_type'];
        
        // Lấy booking
        try {
            $booking = $this->booking->getById($id);
            
            if (!$booking) {
                $this->jsonResponse(false, 'Không tìm thấy booking', [], 404);
                return;
            }

            // Kiểm tra quyền xem
            // Admin có thể xem tất cả, user chỉ xem booking của mình
            if ($user_type !== 'admin' && $booking['customer_id'] != $user_id) {
                $this->jsonResponse(false, 'Không có quyền xem booking này', [], 403);
                return;
            }

            $this->jsonResponse(true, 'Lấy thông tin booking thành công', [
                'booking' => $booking
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
    }

    // Hủy booking (cho customer)
    public function cancelBooking() {
        file_put_contents(__DIR__ . '/booking_debug.log', 
            "=== CANCEL BOOKING CALLED ===\n",
            FILE_APPEND
        );
        
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'POST' && $method !== 'DELETE') {
            $this->jsonResponse(false, 'Method không được phép', [], 405);
            return;
        }

        $id = $_GET['id'] ?? 0;
        if (!$id) {
            $this->jsonResponse(false, 'Thiếu ID booking', [], 400);
            return;
        }

        // Xác thực user
        $user = $this->authenticateUser();
        if (!$user) {
            $this->jsonResponse(false, 'Vui lòng đăng nhập', [], 401);
            return;
        }

        $user_id = $user['id'];
        $user_type = $user['user_type'];
        
        // Lấy booking
        try {
            $booking = $this->booking->getById($id);
            
            if (!$booking) {
                $this->jsonResponse(false, 'Không tìm thấy booking', [], 404);
                return;
            }

            // Kiểm tra quyền hủy
            // Admin có thể hủy tất cả, user chỉ hủy booking của mình
            if ($user_type !== 'admin' && $booking['customer_id'] != $user_id) {
                $this->jsonResponse(false, 'Không có quyền hủy booking này', [], 403);
                return;
            }

            // Chỉ có thể hủy booking ở trạng thái pending hoặc confirmed
            if (!in_array($booking['status'], ['pending', 'confirmed'])) {
                $this->jsonResponse(false, 'Không thể hủy booking ở trạng thái này', [], 400);
                return;
            }

            // Kiểm tra thời gian hủy (không hủy trong vòng 24h trước check-in)
            $check_in = new DateTime($booking['check_in']);
            $now = new DateTime();
            $hours_diff = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;
            
            if ($hours_diff < 24) {
                $this->jsonResponse(false, 'Không thể hủy trong vòng 24 giờ trước check-in', [], 400);
                return;
            }

            // Hủy booking
            $result = $this->booking->update($id, ['status' => 'cancelled']);
            
            if ($result) {
                // Cập nhật trạng thái phòng về available
                $this->room->updateStatus($booking['room_id'], 'available');
                
                file_put_contents(__DIR__ . '/booking_debug.log', 
                    "Booking $id cancelled successfully\n",
                    FILE_APPEND
                );
                
                $this->jsonResponse(true, 'Hủy booking thành công');
            } else {
                $this->jsonResponse(false, 'Hủy booking thất bại', [], 500);
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/booking_debug.log', 
                "Error cancelling booking: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            $this->jsonResponse(false, 'Lỗi server: ' . $e->getMessage(), [], 500);
        }
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
try {
    $booking = new BookingController();

    switch ($action) {
        // Customer actions
        case 'create':
            $booking->create();
            break;
            
        case 'my':
            $booking->getMyBookings();
            break;
            
        case 'get':
            $booking->getBookingById();
            break;
            
        case 'cancel':
            $booking->cancelBooking();
            break;
            
        // Admin actions
        case 'getAll':
            $booking->getAll();
            break;
            
        case 'update':
            $booking->update();
            break;
            
        case 'delete':
            $booking->delete();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ',
                'valid_actions' => [
                    'create', 'my', 'get', 'cancel',  // Customer actions
                    'getAll', 'update', 'delete'      // Admin actions
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Exception $e) {
    // Log lỗi khởi tạo controller
    file_put_contents(__DIR__ . '/booking_errors.log', 
        date('Y-m-d H:i:s') . " - Controller init failed: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => 'Controller initialization failed'
    ], JSON_UNESCAPED_UNICODE);
}
?>