<?php
class Database {
    private $host = "localhost";
    private $db_name = "hotel_opulent"; // Đã sửa tên database
    private $username = "root";
    private $password = "12345678";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    // Helper method để bind parameters
    public function bindParams($stmt, $params) {
        foreach ($params as $key => $value) {
            $paramType = PDO::PARAM_STR;
            
            if (is_int($value)) {
                $paramType = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $paramType = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $paramType = PDO::PARAM_NULL;
            }
            
            $stmt->bindValue(":$key", $value, $paramType);
        }
        return $stmt;
    }
}

// ============================
// MODEL CLASSES
// ============================

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
    public function register() {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password, full_name, phone, address, user_type) 
                  VALUES (:username, :email, :password, :full_name, :phone, :address, :user_type)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':user_type', $this->user_type);
        
        return $stmt->execute();
    }

    // Đăng nhập
    public function login() {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username OR email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($this->password, $row['password'])) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->user_type = $row['user_type'];
            return true;
        }
        return false;
    }

    // Lấy thông tin người dùng theo ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật thông tin người dùng
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET full_name = :full_name, phone = :phone, address = :address, 
                      email = :email, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}

class Room {
    private $conn;
    private $table = 'rooms';
    private $type_table = 'room_types';

    public $id;
    public $room_number;
    public $room_type_id;
    public $floor;
    public $view_type;
    public $status;
    public $image_url;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy danh sách phòng có sẵn
    public function getAvailableRooms($check_in, $check_out, $room_type_id = null) {
        $query = "SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.amenities 
                  FROM " . $this->table . " r
                  INNER JOIN " . $this->type_table . " rt ON r.room_type_id = rt.id
                  WHERE r.status = 'available' 
                  AND r.id NOT IN (
                      SELECT room_id FROM bookings 
                      WHERE (:check_in < check_out AND :check_out > check_in)
                      AND status NOT IN ('cancelled', 'checked_out')
                  )";
        
        if ($room_type_id) {
            $query .= " AND r.room_type_id = :room_type_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        
        if ($room_type_id) {
            $stmt->bindParam(':room_type_id', $room_type_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Lấy chi tiết phòng
    public function getDetails($room_id) {
        $query = "SELECT r.*, rt.type_name, rt.description, rt.base_price, rt.capacity, rt.amenities 
                  FROM " . $this->table . " r
                  INNER JOIN " . $this->type_table . " rt ON r.room_type_id = rt.id
                  WHERE r.id = :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái phòng
    public function updateStatus($room_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':room_id', $room_id);
        
        return $stmt->execute();
    }
}

class Booking {
    private $conn;
    private $table = 'bookings';
    private $services_table = 'booking_services';

    public $id;
    public $booking_code;
    public $customer_id;
    public $room_id;
    public $check_in;
    public $check_out;
    public $num_guests;
    public $total_price;
    public $status;
    public $special_requests;
    public $payment_method;
    public $payment_status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Tạo mã booking tự động
    private function generateBookingCode() {
        return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }

    // Tạo booking mới
    public function create() {
        $this->booking_code = $this->generateBookingCode();
        
        $query = "INSERT INTO " . $this->table . " 
                  (booking_code, customer_id, room_id, check_in, check_out, 
                   num_guests, total_price, special_requests, payment_method) 
                  VALUES (:booking_code, :customer_id, :room_id, :check_in, :check_out, 
                          :num_guests, :total_price, :special_requests, :payment_method)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':booking_code', $this->booking_code);
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':room_id', $this->room_id);
        $stmt->bindParam(':check_in', $this->check_in);
        $stmt->bindParam(':check_out', $this->check_out);
        $stmt->bindParam(':num_guests', $this->num_guests);
        $stmt->bindParam(':total_price', $this->total_price);
        $stmt->bindParam(':special_requests', $this->special_requests);
        $stmt->bindParam(':payment_method', $this->payment_method);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Thêm dịch vụ vào booking
    public function addService($service_id, $quantity, $price, $service_date = null) {
        $query = "INSERT INTO " . $this->services_table . " 
                  (booking_id, service_id, quantity, price, service_date) 
                  VALUES (:booking_id, :service_id, :quantity, :price, :service_date)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':booking_id', $this->id);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':service_date', $service_date);
        
        return $stmt->execute();
    }

    // Lấy booking theo mã
    public function getByCode($booking_code) {
        $query = "SELECT b.*, u.full_name, u.email, u.phone, 
                         r.room_number, rt.type_name,
                         (SELECT SUM(bs.price * bs.quantity) 
                          FROM booking_services bs 
                          WHERE bs.booking_id = b.id) as services_total
                  FROM " . $this->table . " b
                  INNER JOIN users u ON b.customer_id = u.id
                  INNER JOIN rooms r ON b.room_id = r.id
                  INNER JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.booking_code = :booking_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_code', $booking_code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái booking
    public function updateStatus($booking_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :booking_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':booking_id', $booking_id);
        
        return $stmt->execute();
    }

    // Lấy booking theo customer
    public function getCustomerBookings($customer_id) {
        $query = "SELECT b.*, r.room_number, rt.type_name 
                  FROM " . $this->table . " b
                  INNER JOIN rooms r ON b.room_id = r.id
                  INNER JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.customer_id = :customer_id
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

class Service {
    private $conn;
    private $table = 'services';

    public $id;
    public $service_name;
    public $description;
    public $price;
    public $category;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy tất cả dịch vụ
    public function getAll($category = null) {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'available'";
        
        if ($category) {
            $query .= " AND category = :category";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Lấy dịch vụ theo ID
    public function getById($service_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :service_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class Promotion {
    private $conn;
    private $table = 'promotions';

    public $id;
    public $promo_code;
    public $description;
    public $discount_type;
    public $discount_value;
    public $min_amount;
    public $valid_from;
    public $valid_to;
    public $usage_limit;
    public $used_count;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Kiểm tra mã khuyến mãi
    public function validate($promo_code, $total_amount) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE promo_code = :promo_code 
                  AND status = 'active'
                  AND CURDATE() BETWEEN valid_from AND valid_to
                  AND (usage_limit IS NULL OR used_count < usage_limit)
                  AND (min_amount IS NULL OR :total_amount >= min_amount)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':promo_code', $promo_code);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tính discount
    public function calculateDiscount($promo_data, $total_amount) {
        if ($promo_data['discount_type'] == 'percentage') {
            return $total_amount * ($promo_data['discount_value'] / 100);
        } else {
            return min($promo_data['discount_value'], $total_amount);
        }
    }

    // Tăng số lần sử dụng
    public function incrementUsage($promo_code) {
        $query = "UPDATE " . $this->table . " 
                  SET used_count = used_count + 1 
                  WHERE promo_code = :promo_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':promo_code', $promo_code);
        
        return $stmt->execute();
    }
}

// ============================
// EXAMPLE USAGE
// ============================

// Khởi tạo database connection
$database = new Database();
$db = $database->getConnection();

// Ví dụ: Tạo booking mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    $booking = new Booking($db);
    
    $booking->customer_id = $_POST['customer_id'];
    $booking->room_id = $_POST['room_id'];
    $booking->check_in = $_POST['check_in'];
    $booking->check_out = $_POST['check_out'];
    $booking->num_guests = $_POST['num_guests'];
    $booking->total_price = $_POST['total_price'];
    $booking->special_requests = $_POST['special_requests'];
    $booking->payment_method = $_POST['payment_method'];
    
    $booking_id = $booking->create();
    
    if ($booking_id) {
        // Thêm dịch vụ nếu có
        if (isset($_POST['services'])) {
            foreach ($_POST['services'] as $service) {
                $booking->addService($service['id'], $service['quantity'], $service['price']);
            }
        }
        
        echo "Booking created successfully. Booking Code: " . $booking->booking_code;
    }
}

// Ví dụ: Tìm phòng trống
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search_rooms'])) {
    $room = new Room($db);
    
    $available_rooms = $room->getAvailableRooms(
        $_GET['check_in'],
        $_GET['check_out'],
        $_GET['room_type_id'] ?? null
    );
    
    header('Content-Type: application/json');
    echo json_encode($available_rooms);
}

// Ví dụ: Đăng ký người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->full_name = $_POST['full_name'];
    $user->phone = $_POST['phone'];
    $user->address = $_POST['address'];
    $user->user_type = 'customer';
    
    if ($user->register()) {
        echo "Registration successful!";
    } else {
        echo "Registration failed.";
    }
}

// Ví dụ: Đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $user = new User($db);
    
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];
    
    if ($user->login()) {
        session_start();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_type'] = $user->user_type;
        $_SESSION['full_name'] = $user->full_name;
        
        echo "Login successful! Welcome " . $user->full_name;   
    } else {
        echo "Invalid credentials.";
    }
}
?>

