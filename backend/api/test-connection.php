<?php
// backend/api/test-connection.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ĐƯỜNG DẪN ĐÚNG CHO CẤU TRÚC THƯ MỤC CỦA BẠN
    $configPath = __DIR__ . '/config/database.php';
    $userModelPath = __DIR__ . '/models/User.php';
    
    $checks = [
        'PHP Version' => phpversion(),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'Request Method' => $_SERVER['REQUEST_METHOD'],
        'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'Current Directory' => __DIR__,
        'config/database.php path' => $configPath,
        'config/database.php exists' => file_exists($configPath) ? 'Yes' : 'No',
        'models/User.php path' => $userModelPath,
        'models/User.php exists' => file_exists($userModelPath) ? 'Yes' : 'No',
        'Include Path' => get_include_path(),
    ];
    
    // Nếu files không tồn tại, tạo đường dẫn khác thử
    if (!file_exists($configPath)) {
        // Thử đường dẫn khác
        $configPath2 = __DIR__ . '/../config/database.php';
        $checks['Alternative config path'] = $configPath2;
        $checks['Alternative config exists'] = file_exists($configPath2) ? 'Yes' : 'No';
        
        if (file_exists($configPath2)) {
            $configPath = $configPath2;
        }
    }
    
    if (!file_exists($userModelPath)) {
        // Thử đường dẫn khác
        $userModelPath2 = __DIR__ . '/../models/User.php';
        $checks['Alternative model path'] = $userModelPath2;
        $checks['Alternative model exists'] = file_exists($userModelPath2) ? 'Yes' : 'No';
        
        if (file_exists($userModelPath2)) {
            $userModelPath = $userModelPath2;
        }
    }
    
    // Thử kết nối database nếu config tồn tại
    if (file_exists($configPath)) {
        require_once $configPath;
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $checks['Database Connection'] = 'Success';
            
            // Thử query đơn giản
            $stmt = $conn->query("SELECT 1 as test");
            $row = $stmt->fetch();
            $checks['Database Query Test'] = $row['test'] == 1 ? 'Success' : 'Failed';
            
            // Kiểm tra table users
            try {
                $stmt = $conn->query("SHOW TABLES LIKE 'users'");
                $checks['Users Table Exists'] = $stmt->rowCount() > 0 ? 'Yes' : 'No';
                
                if ($stmt->rowCount() > 0) {
                    // Đếm số users
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                    $row = $stmt->fetch();
                    $checks['Total Users'] = $row['count'];
                }
            } catch (Exception $e) {
                $checks['Users Table Check'] = 'Error: ' . $e->getMessage();
            }
            
        } catch (Exception $e) {
            $checks['Database Connection'] = 'Failed: ' . $e->getMessage();
        }
    }
    
    // Kiểm tra session
    session_start();
    $checks['Session ID'] = session_id();
    $checks['Session Status'] = session_status();
    $checks['Session Variables'] = count($_SESSION);
    
    // Kiểm tra PHP extensions
    $extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($extensions as $ext) {
        $checks["PHP Extension: $ext"] = extension_loaded($ext) ? 'Loaded' : 'Not Loaded';
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'API Test Connection',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $checks,
        'server' => [
            'PHP_SELF' => $_SERVER['PHP_SELF'] ?? '',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? '',
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'REQUEST_TIME' => $_SERVER['REQUEST_TIME'] ?? '',
        ],
        'suggestions' => [
            'If files not found' => 'Check the file structure. config/database.php should be in: ' . __DIR__ . '/config/',
            'Common locations' => [
                'config/database.php' => __DIR__ . '/config/database.php',
                'models/User.php' => __DIR__ . '/models/User.php',
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed: ' . $e->getMessage(),
        'error' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>