<?php
require_once '../config/database.php';
require_once '../models/User.php'; // Đổi tên từ Customer.php thành User.php

class CustomerController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Lấy tất cả customers (admin)
    public function getAllCustomers() {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            // Lấy parameters từ query string
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
            $order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
            $userType = isset($_GET['user_type']) ? $_GET['user_type'] : 'customer';
            
            // Validate sort field
            $allowedSort = ['id', 'full_name', 'email', 'created_at', 'total_spent', 'phone'];
            if (!in_array($sort, $allowedSort)) {
                $sort = 'created_at';
            }
            
            // Validate order
            $order = $order === 'ASC' ? 'ASC' : 'DESC';
            
            // Tính offset
            $offset = ($page - 1) * $limit;
            
            // Build query conditions
            $conditions = ["u.user_type = :user_type"];
            $params = [':user_type' => $userType];
            
            if (!empty($search)) {
                $conditions[] = "(u.full_name LIKE :search OR 
                                 u.email LIKE :search OR 
                                 u.phone LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            // Thêm filter theo status nếu có
            if (isset($_GET['status'])) {
                $conditions[] = "u.status = :status";
                $params[':status'] = $_GET['status'];
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Query tổng số records
            $countQuery = "SELECT COUNT(DISTINCT u.id) as total 
                          FROM users u
                          $whereClause";
            
            $stmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            // Query dữ liệu customers với thống kê (cho hotel)
            $query = "SELECT u.*,
                             COUNT(b.id) as bookings_count,
                             SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                             MAX(b.created_at) as last_booking_date
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.customer_id 
                      $whereClause
                      GROUP BY u.id
                      ORDER BY $sort $order
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($customers as &$customer) {
                $customer['bookings_count'] = (int)$customer['bookings_count'];
                $customer['total_spent'] = (float)$customer['total_spent'] ?? 0;
                $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
                $customer['last_booking_date'] = $customer['last_booking_date'] ? 
                    date('d/m/Y', strtotime($customer['last_booking_date'])) : '—';
                
                // Format user_type
                $customer['user_type_display'] = $this->getUserTypeDisplay($customer['user_type']);
                
                // Format status
                $customer['status_display'] = $this->getStatusDisplay($customer['status']);
                
                // Xóa password
                unset($customer['password']);
            }
            
            // Tính tổng số trang
            $totalPages = ceil($total / $limit);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $customers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'search' => $search,
                    'user_type' => $userType,
                    'sort' => $sort,
                    'order' => $order
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

    // Lấy customer theo ID
    public function getCustomer($id) {
        try {
            // Kiểm tra quyền admin hoặc customer xem chính mình
            $this->checkCustomerAccess($id);
            
            $customerId = (int)$id;
            
            // Query thông tin customer với thống kê booking hotel
            $query = "SELECT u.*,
                             COUNT(b.id) as bookings_count,
                             SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                             COUNT(DISTINCT r.id) as rooms_booked
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.customer_id 
                      LEFT JOIN rooms r ON b.room_id = r.id
                      WHERE u.id = :id
                      GROUP BY u.id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dữ liệu
                $customer['bookings_count'] = (int)$customer['bookings_count'];
                $customer['total_spent'] = (float)$customer['total_spent'] ?? 0;
                $customer['rooms_booked'] = (int)$customer['rooms_booked'];
                $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
                $customer['updated_at'] = $customer['updated_at'] ? 
                    date('d/m/Y', strtotime($customer['updated_at'])) : '—';
                
                // Xóa password
                unset($customer['password']);
                
                // Lấy booking history (hotel bookings)
                $bookings = $this->getCustomerBookings($customerId);
                $customer['bookings'] = $bookings;
                
                // Lấy thống kê theo loại phòng
                $roomTypeStats = $this->getCustomerRoomTypeStats($customerId);
                $customer['room_type_stats'] = $roomTypeStats;
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $customer
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
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

    // Tạo customer mới (đăng ký)
    public function createCustomer() {
        try {
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['full_name', 'email', 'phone', 'password'];
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
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
                return;
            }
            
            // Kiểm tra email đã tồn tại chưa
            if ($this->emailExists($data['email'])) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]);
                return;
            }
            
            // Validate password strength
            if (strlen($data['password']) < 6) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Password must be at least 6 characters'
                ]);
                return;
            }
            
            // Prepare customer data
            $customerData = [
                'full_name' => trim($data['full_name']),
                'email' => trim($data['email']),
                'phone' => trim($data['phone']),
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'address' => $data['address'] ?? '',
                'user_type' => $data['user_type'] ?? 'customer',
                'status' => 'active'
            ];
            
            // Insert vào database
            $columns = implode(', ', array_keys($customerData));
            $placeholders = ':' . implode(', :', array_keys($customerData));
            
            $query = "INSERT INTO users ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($query);
            
            foreach ($customerData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            if ($stmt->execute()) {
                $customerId = $this->db->lastInsertId();
                
                // Lấy customer vừa tạo (không có password)
                $createdCustomer = $this->getCustomerById($customerId);
                
                // Tạo JWT token
                $token = $this->generateToken($customerId, $data['email']);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer registered successfully',
                    'data' => [
                        'customer' => $createdCustomer,
                        'token' => $token
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to register customer'
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

    // Cập nhật customer
    public function updateCustomer($id) {
        try {
            // Kiểm tra quyền
            $this->checkCustomerAccess($id);
            
            $customerId = (int)$id;
            
            // Kiểm tra customer tồn tại
            if (!$this->customerExists($customerId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
                ]);
                return;
            }
            
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Prepare update data
            $updateData = [];
            if (isset($data['full_name'])) $updateData['full_name'] = trim($data['full_name']);
            if (isset($data['phone'])) $updateData['phone'] = trim($data['phone']);
            if (isset($data['address'])) $updateData['address'] = $data['address'];
            if (isset($data['avatar'])) $updateData['avatar'] = $data['avatar'];
            if (isset($data['user_type']) && $this->isAdmin()) {
                $updateData['user_type'] = $data['user_type'];
            }
            if (isset($data['status']) && $this->isAdmin()) {
                $updateData['status'] = $data['status'];
            }
            
            // Nếu đổi email, cần validate
            if (isset($data['email']) && $data['email'] !== '') {
                $newEmail = trim($data['email']);
                
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid email format'
                    ]);
                    return;
                }
                
                // Kiểm tra email mới đã tồn tại chưa (trừ email hiện tại)
                if ($this->emailExists($newEmail, $customerId)) {
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Email already in use by another account'
                    ]);
                    return;
                }
                
                $updateData['email'] = $newEmail;
            }
            
            // Nếu đổi password
            if (isset($data['password']) && $data['password'] !== '') {
                if (strlen($data['password']) < 6) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Password must be at least 6 characters'
                    ]);
                    return;
                }
                
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
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
            
            $query = "UPDATE users SET " . implode(', ', $setClause) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            foreach ($updateData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $customerId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Lấy customer đã cập nhật
                $updatedCustomer = $this->getCustomerById($customerId);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer updated successfully',
                    'data' => $updatedCustomer
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update customer'
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

    // Xóa customer (admin only - soft delete)
    public function deleteCustomer($id) {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            $customerId = (int)$id;
            
            // Kiểm tra customer tồn tại
            if (!$this->customerExists($customerId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
                ]);
                return;
            }
            
            // Kiểm tra customer có booking không
            $bookingCount = $this->getCustomerBookingCount($customerId);
            if ($bookingCount > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing bookings'
                ]);
                return;
            }
            
            // Soft delete
            $query = "UPDATE users SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete customer'
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

    // Đăng nhập customer
    public function login() {
        try {
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email and password are required'
                ]);
                return;
            }
            
            // Tìm user theo email
            $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]);
                return;
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]);
                return;
            }
            
            // Xóa password từ response
            unset($user['password']);
            
            // Format user data
            $user['created_at'] = date('d/m/Y', strtotime($user['created_at']));
            
            // Tạo token
            $token = $this->generateToken($user['id'], $user['email'], $user['user_type']);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
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

    // Đăng xuất
    public function logout() {
        try {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy profile của current user
    public function getProfile() {
        try {
            // Lấy user ID từ token
            $userId = $this->getUserIdFromToken();
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Not authenticated'
                ]);
                return;
            }
            
            // Lấy thông tin user với thống kê booking hotel
            $query = "SELECT u.*,
                             COUNT(b.id) as bookings_count,
                             SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.customer_id 
                      WHERE u.id = :id AND u.status = 'active'
                      GROUP BY u.id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dates
                $user['created_at'] = date('d/m/Y', strtotime($user['created_at']));
                $user['bookings_count'] = (int)$user['bookings_count'];
                $user['total_spent'] = (float)$user['total_spent'] ?? 0;
                
                // Xóa password
                unset($user['password']);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
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

    // ===== CÁC METHOD MỚI CHO HOTEL =====
    
    // Thống kê customers (admin)
    public function getCustomerStats() {
        try {
            $this->checkAdminAuth();
            
            $stats = [];
            
            // Tổng số customers
            $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'customer' AND status = 'active'";
            $stmt = $this->db->query($query);
            $stats['total_customers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Customers mới hôm nay
            $today = date('Y-m-d');
            $query = "SELECT COUNT(*) as total FROM users 
                      WHERE DATE(created_at) = :today 
                      AND user_type = 'customer' 
                      AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $stats['new_today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Customers theo tháng
            $currentMonth = date('Y-m');
            $query = "SELECT COUNT(*) as total FROM users 
                      WHERE DATE_FORMAT(created_at, '%Y-%m') = :month 
                      AND user_type = 'customer' 
                      AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':month', $currentMonth);
            $stmt->execute();
            $stats['new_this_month'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Top customers by spending
            $query = "SELECT u.full_name, u.email,
                             SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                             COUNT(b.id) as bookings_count
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.customer_id
                      WHERE u.user_type = 'customer' AND u.status = 'active'
                      GROUP BY u.id
                      ORDER BY total_spent DESC
                      LIMIT 5";
            $stmt = $this->db->query($query);
            $stats['top_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Export customers data
    public function exportCustomers() {
        try {
            $this->checkAdminAuth();
            
            $format = $_GET['format'] ?? 'json';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            // Query customers data
            $query = "SELECT u.id, u.full_name, u.email, u.phone, u.address, u.user_type, u.status,
                             COUNT(b.id) as total_bookings,
                             SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                             MAX(b.created_at) as last_booking_date,
                             u.created_at as registration_date
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.customer_id
                      WHERE u.user_type = 'customer'
                        AND DATE(u.created_at) BETWEEN :start_date AND :end_date
                      GROUP BY u.id
                      ORDER BY u.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data
            foreach ($customers as &$customer) {
                $customer['total_bookings'] = (int)$customer['total_bookings'];
                $customer['total_spent'] = (float)$customer['total_spent'] ?? 0;
                $customer['registration_date'] = date('d/m/Y', strtotime($customer['registration_date']));
                $customer['last_booking_date'] = $customer['last_booking_date'] ? 
                    date('d/m/Y', strtotime($customer['last_booking_date'])) : 'N/A';
            }
            
            if ($format === 'csv') {
                $this->outputCSV($customers, 'customers_export_' . date('Ymd') . '.csv');
            } else {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $customers,
                    'export_info' => [
                        'format' => $format,
                        'date_range' => $startDate . ' to ' . $endDate,
                        'total_records' => count($customers)
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Export error: ' . $e->getMessage()
            ]);
        }
    }

    // ===== HELPER METHODS =====

    private function checkAdminAuth() {
        // Kiểm tra session admin
        session_start();
        
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied: Admin privileges required'
            ]);
            exit;
        }
    }

    private function checkCustomerAccess($customerId) {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: Please login'
            ]);
            exit;
        }
        
        // Admin có quyền truy cập tất cả
        if ($_SESSION['user_type'] === 'admin') {
            return;
        }
        
        // User chỉ có thể truy cập chính mình
        if ($_SESSION['user_id'] != $customerId) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied: You can only access your own data'
            ]);
            exit;
        }
    }

    private function isAdmin() {
        session_start();
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }

    private function getCustomerById($customerId) {
        $query = "SELECT id, full_name, email, phone, address, user_type, status, created_at, updated_at 
                  FROM users 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
            $customer['updated_at'] = $customer['updated_at'] ? 
                date('d/m/Y', strtotime($customer['updated_at'])) : '—';
            $customer['user_type_display'] = $this->getUserTypeDisplay($customer['user_type']);
            $customer['status_display'] = $this->getStatusDisplay($customer['status']);
            return $customer;
        }
        
        return null;
    }

    private function getCustomerBookings($customerId) {
        $query = "SELECT b.*, 
                         r.room_number,
                         rt.type_name as room_type,
                         rt.base_price as room_price
                  FROM bookings b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.customer_id = :customer_id
                  ORDER BY b.created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dữ liệu
        foreach ($bookings as &$booking) {
            $booking['total_price'] = (float)$booking['total_price'];
            $booking['check_in'] = date('d/m/Y', strtotime($booking['check_in']));
            $booking['check_out'] = date('d/m/Y', strtotime($booking['check_out']));
            $booking['created_at'] = date('d/m/Y H:i', strtotime($booking['created_at']));
            
            // Format booking status
            $booking['status_display'] = $this->getBookingStatusDisplay($booking['status']);
            $booking['payment_status_display'] = $this->getPaymentStatusDisplay($booking['payment_status']);
        }
        
        return $bookings;
    }

    private function getCustomerRoomTypeStats($customerId) {
        $query = "SELECT rt.type_name,
                         COUNT(b.id) as booking_count,
                         SUM(b.total_price) as total_spent
                  FROM bookings b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.customer_id = :customer_id
                    AND b.payment_status = 'paid'
                  GROUP BY rt.id
                  ORDER BY total_spent DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM users WHERE email = :email AND status != 'inactive'";
        $params = [':email' => $email];
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    private function customerExists($customerId) {
        $query = "SELECT id FROM users WHERE id = :id AND status != 'inactive'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function getCustomerBookingCount($customerId) {
        $query = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = :customer_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    private function generateToken($userId, $email, $userType = 'customer') {
        // Tạo session cho user
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = $userType;
        $_SESSION['login_time'] = time();
        
        // Trả về session ID như token
        return session_id();
    }

    private function getUserIdFromToken() {
        session_start();
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function outputCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // Add header row
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    private function getUserTypeDisplay($userType) {
        $types = [
            'admin' => 'Quản trị viên',
            'staff' => 'Nhân viên',
            'customer' => 'Khách hàng'
        ];
        
        return $types[$userType] ?? $userType;
    }

    private function getStatusDisplay($status) {
        $statuses = [
            'active' => 'Hoạt động',
            'inactive' => 'Ngừng hoạt động'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getBookingStatusDisplay($status) {
        $statuses = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'checked_out' => 'Đã trả phòng',
            'cancelled' => 'Đã hủy'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getPaymentStatusDisplay($status) {
        $statuses = [
            'pending' => 'Chưa thanh toán',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            'failed' => 'Thất bại'
        ];
        
        return $statuses[$status] ?? $status;
    }
}

// API Router
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new CustomerController();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getAll':
                $controller->getAllCustomers();
                break;
            case 'get':
                if (isset($_GET['id'])) {
                    $controller->getCustomer($_GET['id']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                }
                break;
            case 'profile':
                $controller->getProfile();
                break;
            case 'stats':
                $controller->getCustomerStats();
                break;
            case 'export':
                $controller->exportCustomers();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action parameter required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CustomerController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'create';
    
    switch ($action) {
        case 'create':
            $controller->createCustomer();
            break;
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $controller = new CustomerController();
    
    if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] === 'update') {
        $controller->updateCustomer($_GET['id']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer ID and action required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $controller = new CustomerController();
    
    if (isset($_GET['id'])) {
        $controller->deleteCustomer($_GET['id']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Customer ID required']);
    }
}