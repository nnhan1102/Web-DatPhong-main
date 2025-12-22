<?php
namespace Models;

use PDO;

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $address;
    public $user_type;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Đăng ký người dùng mới
    public function register($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password, full_name, phone, address, user_type, status) 
                  VALUES (:username, :email, :password, :full_name, :phone, :address, :user_type, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        // Generate username if not provided
        if (empty($data['username'])) {
            $data['username'] = strtolower(str_replace(' ', '.', $data['full_name'])) . rand(100, 999);
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        
        $user_type = $data['user_type'] ?? 'customer';
        $status = $data['status'] ?? 'active';
        
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Đăng nhập
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE email = :email AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            // Gán giá trị cho object
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->user_type = $row['user_type'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            
            // Update last login (nếu có trường last_login)
            $this->updateLastLogin();
            
            return true;
        }
        return false;
    }

    // Lấy thông tin user theo ID
    public function getById($id) {
        $query = "SELECT id, username, email, full_name, phone, address, 
                         user_type, status, created_at, updated_at 
                  FROM " . $this->table . " 
                  WHERE id = :id AND status != 'deleted'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy user theo email
    public function getByEmail($email) {
        $query = "SELECT id, username, email, full_name, phone, address, 
                         user_type, status, created_at, updated_at 
                  FROM " . $this->table . " 
                  WHERE email = :email AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật thông tin user
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['full_name'])) {
            $setClause[] = "full_name = :full_name";
            $params[':full_name'] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $setClause[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $setClause[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }
        if (isset($data['address'])) {
            $setClause[] = "address = :address";
            $params[':address'] = $data['address'];
        }
        if (isset($data['user_type'])) {
            $setClause[] = "user_type = :user_type";
            $params[':user_type'] = $data['user_type'];
        }
        if (isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $setClause[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($setClause)) {
            return false;
        }
        
        $setClause[] = "updated_at = CURRENT_TIMESTAMP";
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $setClause) . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    // Xóa user (soft delete)
    public function delete($id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Kiểm tra email đã tồn tại
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE email = :email AND status = 'active'";
        
        $params = [':email' => $email];
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Kiểm tra username đã tồn tại
    public function usernameExists($username, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE username = :username AND status = 'active'";
        
        $params = [':username' => $username];
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Update last login
    private function updateLastLogin() {
        if ($this->id) {
            // Kiểm tra xem bảng có cột last_login không
            $query = "SHOW COLUMNS FROM " . $this->table . " LIKE 'last_login'";
            $stmt = $this->conn->query($query);
            
            if ($stmt->rowCount() > 0) {
                $query = "UPDATE " . $this->table . " 
                          SET last_login = CURRENT_TIMESTAMP 
                          WHERE id = :id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    // Lấy tất cả users với pagination
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE status != 'deleted'";
        $params = [];
        
        if (!empty($filters['user_type'])) {
            $whereClause .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Query tổng số records
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table . " $whereClause";
        $stmt = $this->conn->prepare($countQuery);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Query dữ liệu
        $query = "SELECT id, username, email, full_name, phone, address, 
                         user_type, status, created_at, updated_at 
                  FROM " . $this->table . " 
                  $whereClause 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy thống kê user
    public function getStats() {
        $stats = [];
        
        // Tổng số users
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'active'";
        $stmt = $this->conn->query($query);
        $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Users theo type
        $query = "SELECT user_type, COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE status = 'active' 
                  GROUP BY user_type";
        $stmt = $this->conn->query($query);
        $stats['users_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // New users today
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE DATE(created_at) = :today AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $stats['new_users_today'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
}