<?php
// backend/api/auth/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Ghi log để debug
error_log("=== LOGIN ENDPOINT CALLED ===");

try {
    // Kết nối database TRỰC TIẾP
    $conn = new mysqli('localhost', 'root', '12345678', 'hotel_opulent');
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Kết nối database thất bại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("Database connected successfully");
    
    // Nhận dữ liệu
    $input = file_get_contents('php://input');
    error_log("Raw input: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data || empty($data)) {
        $data = $_POST;
        error_log("Using POST data instead: " . print_r($data, true));
    }
    
    if (empty($data)) {
        error_log("No data received");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Không nhận được dữ liệu'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("Parsed data: " . print_r($data, true));
    
    // Kiểm tra dữ liệu bắt buộc
    if (empty($data['identifier']) || empty($data['password'])) {
        error_log("Missing required fields");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ thông tin đăng nhập'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $identifier = trim($data['identifier']);
    $password = $data['password'];
    
    // Kiểm tra xem identifier là email hay username
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    // Tìm user trong database
    if ($isEmail) {
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, phone, user_type, status FROM users WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, phone, user_type, status FROM users WHERE username = ?");
    }
    
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("User not found: " . $identifier);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản không tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $user = $result->fetch_assoc();
    error_log("User found: " . print_r($user, true));
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu không chính xác'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Kiểm tra trạng thái tài khoản
    if ($user['status'] !== 'active') {
        error_log("Account not active: " . $user['status']);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản đã bị khóa hoặc chưa kích hoạt'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Xóa password từ response
    unset($user['password']);
    
    // Tạo session (đơn giản)
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    
    error_log("Login successful for user: " . $user['email']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'user' => $user,
        'session_id' => session_id()
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

error_log("=== LOGIN ENDPOINT FINISHED ===");
?>