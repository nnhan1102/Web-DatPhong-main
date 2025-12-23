<?php
// backend/api/auth/logout.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Đăng xuất thành công'
], JSON_UNESCAPED_UNICODE);
?>