<?php
// test-endpoint.php - Đặt trong backend/api/test-endpoint.php
header('Content-Type: application/json');

$test_data = [
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'Test123!',
    'full_name' => 'Test User Direct',
    'phone' => '09' . rand(10000000, 99999999),
    'address' => '123 Test Street'
];

echo "Testing endpoint: /backend/api/auth/register.php\n";
echo "Test data:\n";
print_r($test_data);
echo "\n\n";

$url = 'http://localhost/Web-DatPhong-main/backend/api/auth/register.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $http_code\n";
echo "Response:\n";
print_r(json_decode($response, true));

// Kiểm tra trong database
echo "\n\nChecking database...\n";
$conn = new mysqli('localhost', 'root', '12345678', 'hotel_opulent');
$result = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
echo "Last 5 users in database:\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}\n";
}
$conn->close();
?>