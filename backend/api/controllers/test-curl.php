<?php
// test-curl.php - Test API với cURL
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Booking API with cURL</h1>";

// Tạo token test
$user = [
    'id' => 1,
    'full_name' => 'Nguyễn Văn Test',
    'email' => 'test@example.com',
    'phone' => '0123456789',
    'isLoggedIn' => true,
    'user_type' => 'customer'
];

$token = base64_encode(json_encode($user));

// Dữ liệu booking
$bookingData = [
    'room_id' => 1,
    'check_in' => '2024-12-26',
    'check_out' => '2024-12-28',
    'num_guests' => 2,
    'total_price' => 2000000,
    'payment_method' => 'cash',
    'special_requests' => 'Test từ cURL',
    'customer_name' => 'Nguyễn Văn Test',
    'customer_email' => 'test@example.com',
    'customer_phone' => '0123456789'
];

// URL API
$url = 'http://localhost/Web-DatPhong-main/backend/api/controllers/BookingController.php?action=create';

// Tạo cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "<h2>Request Details:</h2>";
echo "<p>URL: $url</p>";
echo "<p>Method: POST</p>";
echo "<p>Token: " . substr($token, 0, 50) . "...</p>";

echo "<h2>Response:</h2>";
echo "<p>HTTP Code: $httpCode</p>";
echo "<h3>Headers:</h3>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";
echo "<h3>Body:</h3>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Parse JSON
if ($body) {
    $json = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<h3>Parsed JSON:</h3>";
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<p style='color:red'>JSON Parse Error: " . json_last_error_msg() . "</p>";
    }
}
?>