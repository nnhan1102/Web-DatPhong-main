<?php
require_once __DIR__ . '/../config/database.php';

class Customer {
    private $conn;
    private $table_name = "users";

    // Customer properties
    public $id;
    public $full_name;
    public $email;
    public $password;
    public $phone;
    public $address;
    public $date_of_birth;
    public $gender;
    public $national_id;
    public $passport_number;
    public $profile_image;
    public $status;
    public $role;
    public $email_verified_at;
    public $verification_token;
    public $reset_token;
    public $reset_token_expiry;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new customer (register)
    public function create() {
        try {
            // Check if email already exists
            $checkQuery = "SELECT id FROM " . $this->table_name . " 
                          WHERE email = :email AND status != 'deleted'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $this->email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                throw new Exception("Email already registered");
            }
            
            // Hash password
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            $query = "INSERT INTO " . $this->table_name . "
                     (full_name, email, password, phone, address, 
                      date_of_birth, gender, national_id, passport_number,
                      profile_image, status, role, verification_token)
                     VALUES
                     (:full_name, :email, :password, :phone, :address,
                      :date_of_birth, :gender, :national_id, :passport_number,
                      :profile_image, :status, :role, :verification_token)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->national_id = htmlspecialchars(strip_tags($this->national_id));
            $this->passport_number = htmlspecialchars(strip_tags($this->passport_number));
            $this->status = $this->status ?: 'pending';
            $this->role = $this->role ?: 'customer';
            
            // Bind parameters
            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':address', $this->address);
            $stmt->bindParam(':date_of_birth', $this->date_of_birth);
            $stmt->bindParam(':gender', $this->gender);
            $stmt->bindParam(':national_id', $this->national_id);
            $stmt->bindParam(':passport_number', $this->passport_number);
            $stmt->bindParam(':profile_image', $this->profile_image);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':role', $this->role);
            $stmt->bindParam(':verification_token', $verificationToken);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                $this->verification_token = $verificationToken;
                return $this->id;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Create customer failed: " . $e->getMessage());
        }
    }

    // Customer login
    public function login($email, $password) {
        try {
            $query = "SELECT * FROM " . $this->table_name . "
                     WHERE email = :email AND status = 'active' 
                     AND role IN ('customer', 'admin')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer && password_verify($password, $customer['password'])) {
                // Update last login (optional - add field to users table if needed)
                // $updateQuery = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
                // $updateStmt = $this->conn->prepare($updateQuery);
                // $updateStmt->bindParam(':id', $customer['id']);
                // $updateStmt->execute();
                
                // Remove password from returned data
                unset($customer['password']);
                unset($customer['verification_token']);
                unset($customer['reset_token']);
                
                return $customer;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Login failed: " . $e->getMessage());
        }
    }

    // Read all customers with pagination
    public function readAll($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE role = 'customer' AND status != 'deleted'";
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereClause .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND DATE(created_at) >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND DATE(created_at) <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $whereClause;
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get customers with limit and offset
            $query = "SELECT id, full_name, email, phone, address, date_of_birth, 
                     gender, national_id, passport_number, profile_image, 
                     status, created_at, updated_at
                     FROM " . $this->table_name . "
                     $whereClause
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'customers' => $customers,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            throw new Exception("Read customers failed: " . $e->getMessage());
        }
    }

    // Read single customer by ID
    public function readOne() {
        try {
            $query = "SELECT id, full_name, email, phone, address, date_of_birth, 
                     gender, national_id, passport_number, profile_image, 
                     status, role, email_verified_at, created_at, updated_at
                     FROM " . $this->table_name . "
                     WHERE id = :id AND status != 'deleted'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->address = $row['address'];
                $this->date_of_birth = $row['date_of_birth'];
                $this->gender = $row['gender'];
                $this->national_id = $row['national_id'];
                $this->passport_number = $row['passport_number'];
                $this->profile_image = $row['profile_image'];
                $this->status = $row['status'];
                $this->role = $row['role'];
                $this->email_verified_at = $row['email_verified_at'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return $row;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Read customer failed: " . $e->getMessage());
        }
    }

    // Update customer profile
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET full_name = :full_name,
                         phone = :phone,
                         address = :address,
                         date_of_birth = :date_of_birth,
                         gender = :gender,
                         national_id = :national_id,
                         passport_number = :passport_number,
                         profile_image = :profile_image,
                         updated_at = NOW()
                     WHERE id = :id AND status != 'deleted'";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->full_name = htmlspecialchars(strip_tags($this->full_name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->address = htmlspecialchars(strip_tags($this->address));
            $this->national_id = htmlspecialchars(strip_tags($this->national_id));
            $this->passport_number = htmlspecialchars(strip_tags($this->passport_number));
            
            // Bind parameters
            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':address', $this->address);
            $stmt->bindParam(':date_of_birth', $this->date_of_birth);
            $stmt->bindParam(':gender', $this->gender);
            $stmt->bindParam(':national_id', $this->national_id);
            $stmt->bindParam(':passport_number', $this->passport_number);
            $stmt->bindParam(':profile_image', $this->profile_image);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update customer failed: " . $e->getMessage());
        }
    }

    // Update customer status
    public function updateStatus($status) {
        try {
            $validStatuses = ['active', 'inactive', 'suspended', 'deleted'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            $query = "UPDATE " . $this->table_name . "
                     SET status = :status,
                         updated_at = NOW()
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update customer status failed: " . $e->getMessage());
        }
    }

    // Change password
    public function changePassword($currentPassword, $newPassword) {
        try {
            // Get current password hash
            $query = "SELECT password FROM " . $this->table_name . "
                     WHERE id = :id AND status != 'deleted'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer || !password_verify($currentPassword, $customer['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE " . $this->table_name . "
                           SET password = :password, updated_at = NOW()
                           WHERE id = :id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $newPasswordHash);
            $updateStmt->bindParam(':id', $this->id);
            
            return $updateStmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Change password failed: " . $e->getMessage());
        }
    }

    // Verify email with token
    public function verifyEmail($token) {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET status = 'active',
                         email_verified_at = NOW(),
                         verification_token = NULL
                     WHERE verification_token = :token 
                     AND status = 'pending'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Email verification failed: " . $e->getMessage());
        }
    }

    // Forgot password - generate reset token
    public function forgotPassword($email) {
        try {
            // Check if email exists
            $query = "SELECT id FROM " . $this->table_name . "
                     WHERE email = :email AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception("Email not found");
            }
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $updateQuery = "UPDATE " . $this->table_name . "
                           SET reset_token = :reset_token,
                               reset_token_expiry = :reset_token_expiry,
                               updated_at = NOW()
                           WHERE id = :id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':reset_token', $resetToken);
            $updateStmt->bindParam(':reset_token_expiry', $resetTokenExpiry);
            $updateStmt->bindParam(':id', $customer['id']);
            
            if ($updateStmt->execute()) {
                return [
                    'reset_token' => $resetToken,
                    'expiry' => $resetTokenExpiry
                ];
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Forgot password failed: " . $e->getMessage());
        }
    }

    // Reset password with token
    public function resetPassword($token, $newPassword) {
        try {
            // Check if token is valid and not expired
            $query = "SELECT id FROM " . $this->table_name . "
                     WHERE reset_token = :token 
                     AND reset_token_expiry > NOW()
                     AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception("Invalid or expired reset token");
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE " . $this->table_name . "
                           SET password = :password,
                               reset_token = NULL,
                               reset_token_expiry = NULL,
                               updated_at = NOW()
                           WHERE id = :id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $newPasswordHash);
            $updateStmt->bindParam(':id', $customer['id']);
            
            return $updateStmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Reset password failed: " . $e->getMessage());
        }
    }

    // Get customer statistics
    public function getStatistics() {
        try {
            $query = "SELECT 
                     COUNT(*) as total_customers,
                     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                     SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_customers,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_customers,
                     SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_customers,
                     COUNT(DISTINCT DATE(created_at)) as days_with_registrations
                     FROM " . $this->table_name . "
                     WHERE role = 'customer' AND status != 'deleted'";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get customer statistics failed: " . $e->getMessage());
        }
    }

    // Get customer by email
    public function getByEmail($email) {
        try {
            $query = "SELECT id, full_name, email, phone, address, 
                     status, role, email_verified_at, created_at
                     FROM " . $this->table_name . "
                     WHERE email = :email AND status != 'deleted'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get customer by email failed: " . $e->getMessage());
        }
    }

    // Get customer's booking history
    public function getBookingHistory($customerId) {
        try {
            $query = "SELECT b.id, b.booking_code, b.booking_date, b.departure_date,
                     b.num_adults, b.num_children, b.num_infants, b.total_price,
                     b.payment_method, b.payment_status, b.booking_status,
                     t.name as tour_name, t.destination as tour_destination,
                     b.created_at, b.updated_at
                     FROM bookings b
                     LEFT JOIN tours t ON b.tour_id = t.id
                     WHERE b.user_id = :customer_id
                     ORDER BY b.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get booking history failed: " . $e->getMessage());
        }
    }

    // Get customer's spending statistics
    public function getSpendingStatistics($customerId) {
        try {
            $query = "SELECT 
                     COUNT(b.id) as total_bookings,
                     SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_spent,
                     AVG(CASE WHEN b.payment_status = 'paid' THEN b.total_price END) as avg_booking_value,
                     MIN(b.created_at) as first_booking_date,
                     MAX(b.created_at) as last_booking_date,
                     SUM(b.num_adults + b.num_children + b.num_infants) as total_passengers
                     FROM bookings b
                     WHERE b.user_id = :customer_id
                     AND b.booking_status != 'cancelled'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get spending statistics failed: " . $e->getMessage());
        }
    }

    // Search customers
    public function search($keyword, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT id, full_name, email, phone, address, 
                     status, created_at
                     FROM " . $this->table_name . "
                     WHERE (full_name LIKE :keyword 
                     OR email LIKE :keyword 
                     OR phone LIKE :keyword)
                     AND role = 'customer' 
                     AND status != 'deleted'
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $keyword = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $keyword);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Search customers failed: " . $e->getMessage());
        }
    }
}
?>