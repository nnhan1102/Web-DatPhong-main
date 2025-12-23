<?php
// models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user - SỬA LẠI
    public function register($data) {
<<<<<<< HEAD
        try {
            error_log("User::register called with data: " . print_r($data, true));
=======
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
>>>>>>> 582f04a39e270fe9b49fa2236a67353f94b15850
            
            // Check if email or username already exists
            $checkQuery = "SELECT id FROM " . $this->table_name . " WHERE email = ? OR username = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            
            if (!$checkStmt) {
                error_log("Prepare check statement failed: " . $this->conn->error);
                return false;
            }
            
            $checkStmt->bind_param("ss", $data['email'], $data['username']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                error_log("User already exists with email: " . $data['email'] . " or username: " . $data['username']);
                return false;
            }
            
            // Hash password - KHÔNG YÊU CẦU MIN LENGTH
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO " . $this->table_name . " 
                     (username, email, password, full_name, phone, address, user_type, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare insert statement failed: " . $this->conn->error);
                return false;
            }
            
            $user_type = $data['user_type'] ?? 'customer';
            $status = $data['status'] ?? 'active';
            
            $stmt->bind_param(
                "ssssssss", 
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['phone'],
                $data['address'],
                $user_type,
                $status
            );
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                error_log("User inserted successfully with ID: " . $user_id);
                
                // Get the created user
                return $this->getById($user_id);
            } else {
                error_log("Execute failed: " . $stmt->error);
                return false;
            }
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Login user
    public function login($identifier, $password) {
        try {
            $query = "SELECT id, username, email, password, full_name, phone, address, user_type, status 
                     FROM " . $this->table_name . " 
                     WHERE (email = ? OR username = ?) AND status = 'active' 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare login statement failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Remove password from response
                    unset($user['password']);
                    return $user;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    // Get user by ID
    public function getById($id) {
        try {
            $query = "SELECT id, username, email, full_name, phone, address, user_type, status, created_at 
                     FROM " . $this->table_name . " 
                     WHERE id = ? 
                     LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare getById statement failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                return $result->fetch_assoc();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    // Check if email exists
    public function emailExists($email) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->num_rows > 0;
        } catch (Exception $e) {
            error_log("Email exists error: " . $e->getMessage());
            return false;
        }
    }

    // Check if username exists
    public function usernameExists($username) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->num_rows > 0;
        } catch (Exception $e) {
            error_log("Username exists error: " . $e->getMessage());
            return false;
        }
    }
}
?>