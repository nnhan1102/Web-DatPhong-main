<?php
// Thêm header để hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

// Kết nối database
$conn = new mysqli('localhost', 'root', '12345678', 'hotel_opulent');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>=== SIMPLE REGISTER TEST ===</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "<strong>Script:</strong> " . $_SERVER['PHP_SELF'] . "<br><br>";

echo "<h3>Testing database connection...</h3>";

// Kiểm tra bảng users
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ <strong>Users table exists</strong><br>";
    
    // Đếm users
    $countResult = $conn->query("SELECT COUNT(*) as total FROM users");
    $row = $countResult->fetch_assoc();
    echo "<strong>Total users:</strong> " . $row['total'] . "<br><br>";
    
    // Hiển thị cấu trúc bảng
    echo "<h3>=== USERS TABLE STRUCTURE ===</h3>";
    $structure = $conn->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ <strong>Users table does NOT exist</strong><br><br>";
}

// === THỬ ĐĂNG KÝ TEST USER ===
echo "<h3>=== TEST USER REGISTRATION ===</h3>";

$timestamp = time();
$test_username = "testuser_" . $timestamp;
$test_email = "test_" . $timestamp . "@example.com";
$test_password = password_hash("Test123!", PASSWORD_DEFAULT);
$test_full_name = "Test User";
$test_phone = "0123456789";
$test_user_type = "customer";

// Kiểm tra email đã tồn tại chưa
$check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_email->bind_param("s", $test_email);
$check_email->execute();
$check_email->store_result();

// Kiểm tra username đã tồn tại chưa
$check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check_username->bind_param("s", $test_username);
$check_username->execute();
$check_username->store_result();

if ($check_email->num_rows > 0) {
    echo "❌ <strong>Test email already exists</strong><br>";
} elseif ($check_username->num_rows > 0) {
    echo "❌ <strong>Test username already exists</strong><br>";
} else {
    // Thử insert user mới - ĐÚNG với cấu trúc bảng của bạn
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $status = "active";
    $stmt->bind_param("sssssss", $test_username, $test_email, $test_password, $test_full_name, $test_phone, $test_user_type, $status);
    
    if ($stmt->execute()) {
        echo "✅ <strong>TEST REGISTRATION SUCCESS</strong><br>";
        echo "<strong>Test user created:</strong><br>";
        echo "- Username: " . $test_username . "<br>";
        echo "- Email: " . $test_email . "<br>";
        echo "- Full Name: " . $test_full_name . "<br>";
        echo "- Phone: " . $test_phone . "<br>";
        echo "- Password: Test123!<br>";
        echo "- User ID: " . $stmt->insert_id . "<br>";
        
        // Hiển thị user vừa tạo
        echo "<h4>Newly Created User:</h4>";
        $new_user = $conn->query("SELECT * FROM users WHERE id = " . $stmt->insert_id);
        if ($new_user->num_rows > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            $user_data = $new_user->fetch_assoc();
            foreach ($user_data as $key => $value) {
                echo "<tr><td><strong>" . $key . "</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ <strong>TEST REGISTRATION FAILED</strong><br>";
        echo "<strong>Error:</strong> " . $stmt->error . "<br>";
        
        // Debug thêm
        echo "<h4>Debug Info:</h4>";
        echo "SQL: INSERT INTO users (username, email, password, full_name, phone, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)<br>";
        echo "Params: username=$test_username, email=$test_email, password=[hashed], full_name=$test_full_name, phone=$test_phone, user_type=$test_user_type, status=$status<br>";
    }
    $stmt->close();
}

// === HIỂN THỊ TẤT CẢ USERS ===
echo "<h3>=== ALL USERS IN DATABASE ===</h3>";
$users = $conn->query("SELECT id, username, email, full_name, phone, user_type, status, created_at FROM users ORDER BY id");
if ($users->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Phone</th><th>Type</th><th>Status</th><th>Created</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['phone']) . "</td>";
        echo "<td>" . $user['user_type'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found in database.<br>";
}

$conn->close();
?>