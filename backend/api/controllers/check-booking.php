<?php
// check-booking.php - Đặt trong backend/api/controllers/
header('Content-Type: application/json');

$bookingController = __DIR__ . '/BookingController.php';

echo json_encode([
    'booking_controller_exists' => file_exists($bookingController),
    'booking_controller_path' => $bookingController,
    'booking_controller_readable' => is_readable($bookingController),
    'file_size' => file_exists($bookingController) ? filesize($bookingController) : 0,
    'directory_contents' => array_values(array_diff(scandir(__DIR__), ['.', '..']))
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>