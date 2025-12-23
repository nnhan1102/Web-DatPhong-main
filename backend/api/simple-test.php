<?php
// simple-test.php - Test đơn giản nhất
header('Content-Type: text/plain');

$url = 'http://localhost' . $_SERVER['REQUEST_URI'];
$base_url = dirname(dirname($url)) . '/backend/api/controller/BookingController.php';

echo "=== Test Booking API ===\n\n";

echo "1. Test file existence:\n";
$file_path = __DIR__ . '/../backend/api/controller/BookingController.php';
if (file_exists($file_path)) {
    echo "   ✅ File exists: " . realpath($file_path) . "\n";
    echo "   Size: " . filesize($file_path) . " bytes\n";
} else {
    echo "   ❌ File NOT found: " . $file_path . "\n";
    echo "   Current dir: " . __DIR__ . "\n";
}

echo "\n2. Test URL access:\n";
$test_url = str_replace('/test/', '/backend/api/controller/BookingController.php', $url);
echo "   Test URL: " . $test_url . "\n";

// Test với curl
echo "\n3. CURL Test:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: " . $http_code . "\n";

if ($http_code == 200) {
    echo "   ✅ Server responds with 200 OK\n";
} elseif ($http_code == 404) {
    echo "   ❌ 404 Not Found - Wrong URL\n";
} elseif ($http_code == 500) {
    echo "   ❌ 500 Internal Server Error - PHP error\n";
}

echo "\n4. Direct file test:\n";
// Thử đọc file trực tiếp
$content = file_get_contents($file_path);
if ($content) {
    echo "   ✅ Can read file\n";
    
    // Kiểm tra nội dung
    if (strpos($content, '<?php') === 0) {
        echo "   ✅ Starts with PHP tag\n";
    } else {
        echo "   ⚠️ Doesn't start with PHP tag\n";
    }
    
    if (strpos($content, 'class BookingController') !== false) {
        echo "   ✅ Contains BookingController class\n";
    } else {
        echo "   ❌ Missing BookingController class\n";
    }
    
    // Kiểm tra lỗi thường gặp
    $errors = [];
    if (strpos($content, '<? ') !== false) {
        $errors[] = "Uses short open tag <?";
    }
    if (strpos($content, '<?=') !== false) {
        $errors[] = "Uses short echo tag <?=";
    }
    
    if (count($errors) > 0) {
        echo "   ⚠️ Potential issues:\n";
        foreach ($errors as $error) {
            echo "      - " . $error . "\n";
        }
    }
} else {
    echo "   ❌ Cannot read file\n";
}

echo "\n=== End Test ===\n";