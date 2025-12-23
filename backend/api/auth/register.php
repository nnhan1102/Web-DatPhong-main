<?php
// backend/api/auth/register.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Ghi log để debug
error_log("=== REGISTER ENDPOINT CALLED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

try {
    // Kết nối database TRỰC TIẾP như file test của bạn
    $conn = new mysqli('localhost', 'root', '12345678', 'hotel_opulent');
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Kết nối database thất bại: ' . $conn->connect_error
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
    
    // Nếu vẫn không có data
    if (empty($data)) {
        error_log("No data received");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Không nhận được dữ liệu',
            'debug' => [
                'php_input' => $input,
                'post_data' => $_POST,
                'get_data' => $_GET
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("Parsed data: " . print_r($data, true));
    
    // Kiểm tra dữ liệu bắt buộc
    $required = ['username', 'email', 'password', 'full_name', 'phone'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' là bắt buộc';
        }
    }
    
    if (!empty($errors)) {
        error_log("Validation errors: " . print_r($errors, true));
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu thông tin bắt buộc',
            'errors' => $errors,
            'received_data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Chuẩn hóa dữ liệu
    $username = trim($data['username']);
    $email = trim($data['email']);
    $password = $data['password'];
    $full_name = trim($data['full_name']);
    $phone = trim($data['phone']);
    $address = isset($data['address']) ? trim($data['address']) : '';
    
    // Kiểm tra email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email: " . $email);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email không hợp lệ'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Kiểm tra email tồn tại
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        error_log("Email already exists: " . $email);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email đã được đăng ký'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Kiểm tra username tồn tại
    $checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $checkUsername->store_result();
    
    if ($checkUsername->num_rows > 0) {
        error_log("Username already exists: " . $username);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập đã tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Kiểm tra phone tồn tại
    $checkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    $checkPhone->store_result();
    
    if ($checkPhone->num_rows > 0) {
        error_log("Phone already exists: " . $phone);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Số điện thoại đã được đăng ký'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Tạo user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, full_name, phone, address, user_type, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active')
    ");
    
    $stmt->bind_param(
        "ssssss", 
        $username,
        $email,
        $hashed_password,
        $full_name,
        $phone,
        $address
    );
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        error_log("User created successfully with ID: " . $user_id);
        
        // Lấy thông tin user vừa tạo
        $userResult = $conn->query("
            SELECT id, username, email, full_name, phone, address, user_type, created_at 
            FROM users WHERE id = $user_id
        ");
        $user = $userResult->fetch_assoc();
        
        error_log("Registration successful for user: " . $email);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'user' => $user
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        error_log("Database insert error: " . $stmt->error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi tạo tài khoản: ' . $stmt->error,
            'sql_error' => $stmt->error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
}

error_log("=== REGISTER ENDPOINT FINISHED ===");
?>