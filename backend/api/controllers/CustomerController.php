<?php
require_once '../config/database.php';
require_once '../models/Customer.php';

class CustomerController {
    private $db;
    private $customer;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->customer = new Customer($this->db);
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
            
            // Validate sort field
            $allowedSort = ['id', 'full_name', 'email', 'created_at', 'total_spent'];
            if (!in_array($sort, $allowedSort)) {
                $sort = 'created_at';
            }
            
            // Validate order
            $order = $order === 'ASC' ? 'ASC' : 'DESC';
            
            // Tính offset
            $offset = ($page - 1) * $limit;
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = "(u.full_name LIKE :search OR 
                                 u.email LIKE :search OR 
                                 u.phone LIKE :search)";
                $params[':search'] = "%$search%";
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
            
            // Query dữ liệu customers với thống kê
            $query = "SELECT u.*,
                             COUNT(b.id) as bookings_count,
                             SUM(b.total_price) as total_spent,
                             MAX(b.created_at) as last_booking_date
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.user_id AND b.payment_status = 'paid'
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
                $customer['total_spent'] = (float)$customer['total_spent'];
                $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
                $customer['last_booking_date'] = $customer['last_booking_date'] ? 
                    date('d/m/Y', strtotime($customer['last_booking_date'])) : '—';
                
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
            
            // Query thông tin customer
            $query = "SELECT u.*,
                             COUNT(b.id) as bookings_count,
                             SUM(b.total_price) as total_spent
                      FROM users u
                      LEFT JOIN bookings b ON u.id = b.user_id AND b.payment_status = 'paid'
                      WHERE u.id = :id
                      GROUP BY u.id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dữ liệu
                $customer['bookings_count'] = (int)$customer['bookings_count'];
                $customer['total_spent'] = (float)$customer['total_spent'];
                $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
                
                // Xóa password
                unset($customer['password']);
                
                // Lấy booking history
                $bookings = $this->getCustomerBookings($customerId);
                $customer['bookings'] = $bookings;
                
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
                'avatar' => $data['avatar'] ?? '',
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
                
                // Tạo JWT token (trong thực tế)
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
            
            $query = "UPDATE users SET " . implode(', ', $setClause) . ", updated_at = NOW() WHERE id = :id";
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

    // Xóa customer (admin only)
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
            $query = "UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = :id";
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
            
            // Tạo token
            $token = $this->generateToken($user['id'], $user['email']);
            
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
            // Trong JWT, logout được xử lý ở client side
            // Ở đây có thể invalidate token nếu dùng blacklist
            
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
            
            // Lấy thông tin user
            $query = "SELECT id, full_name, email, phone, address, avatar, created_at 
                      FROM users 
                      WHERE id = :id AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dates
                $user['created_at'] = date('d/m/Y', strtotime($user['created_at']));
                
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

    // ===== HELPER METHODS =====

    private function checkAdminAuth() {
        // Kiểm tra admin authentication
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

    private function checkCustomerAccess($customerId) {
        // Kiểm tra user có quyền truy cập customer data không
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Nếu là admin token
        if ($token === 'admin-secret-token') {
            return;
        }
        
        // Nếu là user token, kiểm tra xem có phải xem chính mình không
        $userId = (int)$token; // Giả sử token là user_id
        
        if ($userId !== (int)$customerId) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit;
        }
    }

    private function getCustomerById($customerId) {
        $query = "SELECT id, full_name, email, phone, address, avatar, status, created_at 
                  FROM users 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            $customer['created_at'] = date('d/m/Y', strtotime($customer['created_at']));
            return $customer;
        }
        
        return null;
    }

    private function getCustomerBookings($customerId) {
        $query = "SELECT b.*, 
                         t.name as tour_name,
                         t.destination,
                         t.featured_image
                  FROM bookings b
                  LEFT JOIN tours t ON b.tour_id = t.id
                  WHERE b.user_id = :customer_id
                  ORDER BY b.created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dữ liệu
        foreach ($bookings as &$booking) {
            $booking['total_price'] = (float)$booking['total_price'];
            $booking['departure_date'] = date('d/m/Y', strtotime($booking['departure_date']));
            $booking['created_at'] = date('d/m/Y H:i', strtotime($booking['created_at']));
        }
        
        return $bookings;
    }

    private function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM users WHERE email = :email AND status != 'deleted'";
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
        $query = "SELECT id FROM users WHERE id = :id AND status != 'deleted'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function getCustomerBookingCount($customerId) {
        $query = "SELECT COUNT(*) as count FROM bookings WHERE user_id = :customer_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    private function generateToken($userId, $email) {
        // Trong thực tế, bạn sẽ tạo JWT token
        // Ở đây tạo một simple token cho demo
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'exp' => time() + (7 * 24 * 60 * 60) // 7 days
        ];
        
        // Mã hóa đơn giản (trong thực tế dùng JWT)
        return base64_encode(json_encode($payload));
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            
            // Giải mã token đơn giản
            try {
                $payload = json_decode(base64_decode($token), true);
                
                if (isset($payload['user_id']) && isset($payload['exp'])) {
                    // Kiểm tra token hết hạn
                    if ($payload['exp'] > time()) {
                        return (int)$payload['user_id'];
                    }
                }
            } catch (Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}