<?php
// test-direct-access.php - Đặt trong backend/api/
$directUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/backend/api/controllers/BookingController.php';

echo "<h1>Test Direct Access</h1>";
echo "<p>URL: <a href='$directUrl'>$directUrl</a></p>";

// Test bằng file_get_contents
echo "<h2>Test bằng PHP:</h2>";
try {
    $content = @file_get_contents($directUrl);
    if ($content === FALSE) {
        echo "<p style='color:red'>❌ Không thể truy cập</p>";
        
        // Kiểm tra lỗi
        $error = error_get_last();
        echo "<pre>Error: " . print_r($error, true) . "</pre>";
    } else {
        echo "<p style='color:green'>✅ Có thể truy cập</p>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}

// Kiểm tra permissions
echo "<h2>File Permissions:</h2>";
$controllerPath = __DIR__ . '/controllers/BookingController.php';
echo "<p>Path: $controllerPath</p>";
echo "<p>Exists: " . (file_exists($controllerPath) ? '✅ Yes' : '❌ No') . "</p>";
if (file_exists($controllerPath)) {
    echo "<p>Readable: " . (is_readable($controllerPath) ? '✅ Yes' : '❌ No') . "</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($controllerPath)), -4) . "</p>";
}
?>