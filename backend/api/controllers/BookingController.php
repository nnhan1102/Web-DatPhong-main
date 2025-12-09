<?php
require_once '../config/database.php';
require_once '../models/Booking.php';

class BookingController {
    private $db;
    private $booking;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->booking = new Booking($this->db);
    }

    // Lấy tất cả bookings (admin)
    public function getAllBookings() {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            // Lấy parameters từ query string
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $paymentStatus = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            // Tính offset
            $offset = ($page - 1) * $limit;
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = "(b.booking_code LIKE :search OR 
                                 u.full_name LIKE :search OR 
                                 u.email LIKE :search OR 
                                 u.phone LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($status)) {
                $conditions[] = "b.booking_status = :status";
                $params[':status'] = $status;
            }
            
            if (!empty($paymentStatus)) {
                $conditions[] = "b.payment_status = :payment_status";
                $params[':payment_status'] = $paymentStatus;
            }
            
            if (!empty($startDate)) {
                $conditions[] = "DATE(b.created_at) >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if (!empty($endDate)) {
                $conditions[] = "DATE(b.created_at) <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Query tổng số records
            $countQuery = "SELECT COUNT(DISTINCT b.id) as total 
                          FROM bookings b
                          LEFT JOIN users u ON b.user_id = u.id
                          $whereClause";
            
            $stmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            // Query dữ liệu bookings
            $query = "SELECT b.*, 
                             t.name as tour_name,
                             t.destination,
                             t.duration_days,
                             t.duration_nights,
                             t.featured_image,
                             u.full_name as customer_name,
                             u.email as customer_email,
                             u.phone as customer_phone
                      FROM bookings b
                      LEFT JOIN tours t ON b.tour_id = t.id
                      LEFT JOIN users u ON b.user_id = u.id
                      $whereClause
                      ORDER BY b.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($bookings as &$booking) {
                $booking['customer_info'] = json_decode($booking['customer_info'], true) ?: [];
                $booking['total_price'] = (float)$booking['total_price'];
                $booking['num_adults'] = (int)$booking['num_adults'];
                $booking['num_children'] = (int)$booking['num_children'];
                $booking['num_infants'] = (int)$booking['num_infants'];
                
                // Format dates
                $booking['departure_date'] = date('d/m/Y', strtotime($booking['departure_date']));
                $booking['created_at'] = date('d/m/Y H:i', strtotime($booking['created_at']));
            }
            
            // Tính tổng số trang
            $totalPages = ceil($total / $limit);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy booking theo ID
    public function getBooking($id) {
        try {
            // Kiểm tra quyền admin hoặc booking thuộc về user
            $this->checkBookingAccess($id);
            
            $bookingId = (int)$id;
            
            $query = "SELECT b.*, 
                             t.name as tour_name,
                             t.slug as tour_slug,
                             t.destination,
                             t.duration_days,
                             t.duration_nights,
                             t.featured_image,
                             t.price_adult,
                             t.price_child,
                             t.price_infant,
                             t.discount_percent,
                             u.full_name as customer_name,
                             u.email as customer_email,
                             u.phone as customer_phone,
                             u.address as customer_address
                      FROM bookings b
                      LEFT JOIN tours t ON b.tour_id = t.id
                      LEFT JOIN users u ON b.user_id = u.id
                      WHERE b.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $bookingId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dữ liệu
                $booking['customer_info'] = json_decode($booking['customer_info'], true) ?: [];
                $booking['total_price'] = (float)$booking['total_price'];
                $booking['num_adults'] = (int)$booking['num_adults'];
                $booking['num_children'] = (int)$booking['num_children'];
                $booking['num_infants'] = (int)$booking['num_infants'];
                
                // Tính giá chi tiết
                $adultPrice = $booking['price_adult'] * (1 - $booking['discount_percent'] / 100);
                $childPrice = $booking['price_child'] * (1 - $booking['discount_percent'] / 100);
                $infantPrice = $booking['price_infant'] * (1 - $booking['discount_percent'] / 100);
                
                $booking['price_breakdown'] = [
                    'adults' => [
                        'count' => $booking['num_adults'],
                        'price_per_person' => $adultPrice,
                        'total' => $booking['num_adults'] * $adultPrice
                    ],
                    'children' => [
                        'count' => $booking['num_children'],
                        'price_per_person' => $childPrice,
                        'total' => $booking['num_children'] * $childPrice
                    ],
                    'infants' => [
                        'count' => $booking['num_infants'],
                        'price_per_person' => $infantPrice,
                        'total' => $booking['num_infants'] * $infantPrice
                    ]
                ];
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $booking
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Booking not found'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Tạo booking mới
    public function createBooking() {
        try {
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['tour_id', 'departure_date', 'num_adults'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ]);
                    return;
                }
            }
            
            // Kiểm tra tour tồn tại và còn chỗ
            $tourId = (int)$data['tour_id'];
            $departureDate = $data['departure_date'];
            $numAdults = (int)$data['num_adults'];
            $numChildren = (int)($data['num_children'] ?? 0);
            $numInfants = (int)($data['num_infants'] ?? 0);
            
            $tour = $this->getTourDetails($tourId);
            if (!$tour) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
                return;
            }
            
            // Kiểm tra số chỗ còn lại
            $totalPassengers = $numAdults + $numChildren + $numInfants;
            if ($totalPassengers > $tour['available_slots']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Not enough available slots'
                ]);
                return;
            }
            
            // Kiểm tra ngày khởi hành hợp lệ
            $minDate = date('Y-m-d', strtotime('+3 days'));
            if ($departureDate < $minDate) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Departure date must be at least 3 days from now'
                ]);
                return;
            }
            
            // Tính giá
            $adultPrice = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
            $childPrice = $tour['price_child'] * (1 - $tour['discount_percent'] / 100);
            $infantPrice = $tour['price_infant'] * (1 - $tour['discount_percent'] / 100);
            
            $totalPrice = ($numAdults * $adultPrice) + 
                         ($numChildren * $childPrice) + 
                         ($numInfants * $infantPrice);
            
            // Generate booking code
            $bookingCode = $this->generateBookingCode();
            
            // Lấy user ID từ token (nếu đã login)
            $userId = $this->getUserIdFromToken();
            
            // Prepare booking data
            $bookingData = [
                'booking_code' => $bookingCode,
                'user_id' => $userId,
                'tour_id' => $tourId,
                'booking_date' => date('Y-m-d'),
                'departure_date' => $departureDate,
                'num_adults' => $numAdults,
                'num_children' => $numChildren,
                'num_infants' => $numInfants,
                'total_price' => $totalPrice,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'special_requests' => $data['special_requests'] ?? '',
                'customer_info' => json_encode([
                    'full_name' => $data['customer_name'] ?? '',
                    'email' => $data['customer_email'] ?? '',
                    'phone' => $data['customer_phone'] ?? '',
                    'address' => $data['customer_address'] ?? ''
                ])
            ];
            
            // Insert vào database
            $columns = implode(', ', array_keys($bookingData));
            $placeholders = ':' . implode(', :', array_keys($bookingData));
            
            $query = "INSERT INTO bookings ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($query);
            
            foreach ($bookingData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            if ($stmt->execute()) {
                $bookingId = $this->db->lastInsertId();
                
                // Cập nhật số chỗ còn lại của tour
                $this->updateTourSlots($tourId, $totalPassengers);
                
                // Gửi email xác nhận
                $this->sendBookingConfirmation($bookingId, $bookingCode, $data['customer_email'] ?? '');
                
                // Lấy booking vừa tạo
                $createdBooking = $this->getBookingById($bookingId);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data' => [
                        'booking_id' => $bookingId,
                        'booking_code' => $bookingCode,
                        'total_price' => $totalPrice,
                        'booking_details' => $createdBooking
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create booking'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Cập nhật booking (admin)
    public function updateBooking($id) {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            $bookingId = (int)$id;
            
            // Kiểm tra booking tồn tại
            if (!$this->bookingExists($bookingId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Booking not found'
                ]);
                return;
            }
            
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Prepare update data
            $updateData = [];
            if (isset($data['departure_date'])) $updateData['departure_date'] = $data['departure_date'];
            if (isset($data['num_adults'])) $updateData['num_adults'] = (int)$data['num_adults'];
            if (isset($data['num_children'])) $updateData['num_children'] = (int)$data['num_children'];
            if (isset($data['num_infants'])) $updateData['num_infants'] = (int)$data['num_infants'];
            if (isset($data['payment_status'])) $updateData['payment_status'] = $data['payment_status'];
            if (isset($data['booking_status'])) $updateData['booking_status'] = $data['booking_status'];
            if (isset($data['special_requests'])) $updateData['special_requests'] = $data['special_requests'];
            
            // Recalculate total price if passenger numbers changed
            if (isset($data['num_adults']) || isset($data['num_children']) || isset($data['num_infants'])) {
                $booking = $this->getBookingDetails($bookingId);
                $tour = $this->getTourDetails($booking['tour_id']);
                
                $numAdults = isset($data['num_adults']) ? (int)$data['num_adults'] : $booking['num_adults'];
                $numChildren = isset($data['num_children']) ? (int)$data['num_children'] : $booking['num_children'];
                $numInfants = isset($data['num_infants']) ? (int)$data['num_infants'] : $booking['num_infants'];
                
                $adultPrice = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
                $childPrice = $tour['price_child'] * (1 - $tour['discount_percent'] / 100);
                $infantPrice = $tour['price_infant'] * (1 - $tour['discount_percent'] / 100);
                
                $updateData['total_price'] = ($numAdults * $adultPrice) + 
                                            ($numChildren * $childPrice) + 
                                            ($numInfants * $infantPrice);
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data to update'
                ]);
                return;
            }
            
            // Build update query
            $setClause = [];
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            
            $query = "UPDATE bookings SET " . implode(', ', $setClause) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            foreach ($updateData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $bookingId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Gửi thông báo nếu có thay đổi trạng thái
                if (isset($data['booking_status'])) {
                    $this->sendStatusUpdateNotification($bookingId, $data['booking_status']);
                }
                
                // Lấy booking đã cập nhật
                $updatedBooking = $this->getBookingById($bookingId);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking updated successfully',
                    'data' => $updatedBooking
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update booking'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Hủy booking
    public function cancelBooking($id) {
        try {
            $bookingId = (int)$id;
            
            // Kiểm tra booking tồn tại
            if (!$this->bookingExists($bookingId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Booking not found'
                ]);
                return;
            }
            
            // Kiểm tra quyền (admin hoặc chủ booking)
            $this->checkBookingAccess($bookingId);
            
            // Kiểm tra xem booking có thể hủy không
            $booking = $this->getBookingDetails($bookingId);
            $departureDate = new DateTime($booking['departure_date']);
            $today = new DateTime();
            $daysDifference = $today->diff($departureDate)->days;
            
            if ($daysDifference < 3) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Booking can only be cancelled at least 3 days before departure'
                ]);
                return;
            }
            
            // Cập nhật trạng thái
            $query = "UPDATE bookings SET booking_status = 'cancelled', updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $bookingId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Hoàn lại số chỗ cho tour
                $totalPassengers = $booking['num_adults'] + $booking['num_children'] + $booking['num_infants'];
                $this->restoreTourSlots($booking['tour_id'], $totalPassengers);
                
                // Gửi thông báo hủy booking
                $this->sendCancellationNotification($bookingId);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking cancelled successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to cancel booking'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy bookings của user
    public function getUserBookings() {
        try {
            // Lấy user ID từ token
            $userId = $this->getUserIdFromToken();
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
                return;
            }
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            $offset = ($page - 1) * $limit;
            
            // Build query conditions
            $conditions = ["b.user_id = :user_id"];
            $params = [':user_id' => $userId];
            
            if (!empty($status)) {
                $conditions[] = "b.booking_status = :status";
                $params[':status'] = $status;
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Query tổng số records
            $countQuery = "SELECT COUNT(*) as total FROM bookings b WHERE $whereClause";
            $stmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            // Query dữ liệu bookings
            $query = "SELECT b.*, 
                             t.name as tour_name,
                             t.destination,
                             t.duration_days,
                             t.duration_nights,
                             t.featured_image
                      FROM bookings b
                      LEFT JOIN tours t ON b.tour_id = t.id
                      WHERE $whereClause
                      ORDER BY b.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($bookings as &$booking) {
                $booking['total_price'] = (float)$booking['total_price'];
                $booking['departure_date'] = date('d/m/Y', strtotime($booking['departure_date']));
                $booking['created_at'] = date('d/m/Y H:i', strtotime($booking['created_at']));
            }
            
            $totalPages = ceil($total / $limit);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // ===== HELPER METHODS =====

    private function checkAdminAuth() {
        // Kiểm tra admin authentication (giống trong TourController)
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: No token provided'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        if ($token !== 'admin-secret-token') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden: Invalid token'
            ]);
            exit;
        }
    }

    private function checkBookingAccess($bookingId) {
        // Kiểm tra user có quyền truy cập booking không
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            // Public access not allowed for specific booking
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Trong thực tế, bạn sẽ decode JWT để lấy user_id
        // Ở đây giả sử token là user_id đơn giản
        $userId = (int)$token;
        
        // Kiểm tra xem booking có thuộc về user không
        $query = "SELECT user_id FROM bookings WHERE id = :booking_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || ($booking['user_id'] != $userId && $token !== 'admin-secret-token')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit;
        }
    }

    private function generateBookingCode() {
        $prefix = 'OPL';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return $prefix . '-' . $date . '-' . $random;
    }

    private function getTourDetails($tourId) {
        $query = "SELECT * FROM tours WHERE id = :id AND status = 'active' AND deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    private function updateTourSlots($tourId, $bookedSlots) {
        $query = "UPDATE tours SET available_slots = available_slots - :slots WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
        $stmt->bindParam(':slots', $bookedSlots, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function restoreTourSlots($tourId, $slots) {
        $query = "UPDATE tours SET available_slots = available_slots + :slots WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
        $stmt->bindParam(':slots', $slots, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function getBookingById($bookingId) {
        $query = "SELECT b.*, t.name as tour_name, t.destination 
                  FROM bookings b
                  LEFT JOIN tours t ON b.tour_id = t.id
                  WHERE b.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    private function getBookingDetails($bookingId) {
        return $this->getBookingById($bookingId);
    }

    private function bookingExists($bookingId) {
        $query = "SELECT id FROM bookings WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function getUserIdFromToken() {
        // Trong thực tế, bạn sẽ decode JWT để lấy user_id
        // Ở đây trả về null nếu không có user
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            
            // Giả sử token là user_id đơn giản
            if (is_numeric($token) && $token > 0) {
                return (int)$token;
            }
        }
        
        return null;
    }

    private function sendBookingConfirmation($bookingId, $bookingCode, $email) {
        // Gửi email xác nhận booking
        // Trong thực tế, bạn sẽ sử dụng PHPMailer hoặc service khác
        if (!empty($email)) {
            // Log để debug
            error_log("Booking confirmation sent to $email - Booking #$bookingCode");
        }
    }

    private function sendStatusUpdateNotification($bookingId, $newStatus) {
        // Gửi thông báo cập nhật trạng thái
        $booking = $this->getBookingDetails($bookingId);
        if ($booking && !empty($booking['customer_email'])) {
            error_log("Status update sent for Booking #{$booking['booking_code']} - New status: $newStatus");
        }
    }

    private function sendCancellationNotification($bookingId) {
        // Gửi thông báo hủy booking
        $booking = $this->getBookingDetails($bookingId);
        if ($booking && !empty($booking['customer_email'])) {
            error_log("Cancellation notification sent for Booking #{$booking['booking_code']}");
        }
    }
}