<?php
// models/Booking.php - VERSION ĐẦY ĐỦ CHO CẢ ADMIN VÀ CUSTOMER

class Booking {
    private $conn;
    public $table_name = "bookings";

    public $id;
    public $booking_code;
    public $customer_id;
    public $room_id;
    public $check_in;
    public $check_out;
    public $num_guests;
    public $total_price;
    public $status;
    public $payment_method;
    public $payment_status;
    public $special_requests;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new booking
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET booking_code = :booking_code,
                      customer_id = :customer_id,
                      room_id = :room_id,
                      check_in = :check_in,
                      check_out = :check_out,
                      num_guests = :num_guests,
                      total_price = :total_price,
                      status = :status,
                      payment_method = :payment_method,
                      payment_status = :payment_status,
                      special_requests = :special_requests";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->booking_code = htmlspecialchars(strip_tags($data['booking_code']));
        $this->customer_id = htmlspecialchars(strip_tags($data['customer_id']));
        $this->room_id = htmlspecialchars(strip_tags($data['room_id']));
        $this->check_in = htmlspecialchars(strip_tags($data['check_in']));
        $this->check_out = htmlspecialchars(strip_tags($data['check_out']));
        $this->num_guests = htmlspecialchars(strip_tags($data['num_guests']));
        $this->total_price = htmlspecialchars(strip_tags($data['total_price']));
        $this->status = htmlspecialchars(strip_tags($data['status']));
        $this->payment_method = htmlspecialchars(strip_tags($data['payment_method']));
        $this->payment_status = htmlspecialchars(strip_tags($data['payment_status']));
        $this->special_requests = htmlspecialchars(strip_tags($data['special_requests']));

        // Bind parameters
        $stmt->bindParam(":booking_code", $this->booking_code);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in", $this->check_in);
        $stmt->bindParam(":check_out", $this->check_out);
        $stmt->bindParam(":num_guests", $this->num_guests);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":special_requests", $this->special_requests);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Get booking by ID
    public function getById($id) {
        $query = "SELECT b.*, r.room_number, r.room_type, r.price_per_night,
                         c.full_name as customer_name, c.email as customer_email, 
                         c.phone as customer_phone
                  FROM " . $this->table_name . " b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN customers c ON b.customer_id = c.id
                  WHERE b.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Tính số đêm
            $check_in = new DateTime($row['check_in']);
            $check_out = new DateTime($row['check_out']);
            $row['nights'] = $check_out->diff($check_in)->days;
            
            return $row;
        }
        return null;
    }

    // Lấy tất cả bookings (cho admin)
    public function getAllBookings($search = '', $status = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
        $query = "SELECT 
                    b.*, 
                    r.room_number, 
                    r.room_type,
                    r.price_per_night,
                    c.full_name as customer_name, 
                    c.email as customer_email, 
                    c.phone as customer_phone,
                    DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') as created_date,
                    DATE_FORMAT(b.check_in, '%d/%m/%Y') as check_in_formatted,
                    DATE_FORMAT(b.check_out, '%d/%m/%Y') as check_out_formatted
                  FROM " . $this->table_name . " b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN customers c ON b.customer_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        // Add search conditions
        if (!empty($search)) {
            $query .= " AND (
                b.booking_code LIKE ? OR 
                c.full_name LIKE ? OR 
                c.email LIKE ? OR 
                r.room_number LIKE ?
            )";
            $searchTerm = "%" . $search . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($status)) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(b.check_in) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(b.check_in) <= ?";
            $params[] = $date_to;
        }
        
        $query .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính số đêm cho mỗi booking
        foreach ($bookings as &$booking) {
            $check_in = new DateTime($booking['check_in']);
            $check_out = new DateTime($booking['check_out']);
            $booking['nights'] = $check_out->diff($check_in)->days;
        }
        
        return $bookings;
    }

    // Đếm tất cả bookings (cho admin)
    public function countAllBookings($search = '', $status = '', $date_from = '', $date_to = '') {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN customers c ON b.customer_id = c.id
                  WHERE 1=1";
        
        $params = [];
        
        // Add search conditions
        if (!empty($search)) {
            $query .= " AND (
                b.booking_code LIKE ? OR 
                c.full_name LIKE ? OR 
                c.email LIKE ? OR 
                r.room_number LIKE ?
            )";
            $searchTerm = "%" . $search . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($status)) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(b.check_in) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(b.check_in) <= ?";
            $params[] = $date_to;
        }

        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get bookings by user ID
    public function getUserBookings($user_id, $status = '', $limit = 10, $offset = 0) {
        $query = "SELECT b.*, r.room_number, r.room_type, r.image_url,
                         DATE_FORMAT(b.created_at, '%d/%m/%Y %H:%i') as created_date
                  FROM " . $this->table_name . " b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  WHERE b.customer_id = ?";
        
        $params = [$user_id];
        
        if (!empty($status)) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count user bookings
    public function countUserBookings($user_id, $status = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE customer_id = ?";
        
        $params = [$user_id];
        
        if (!empty($status)) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Update booking
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET ";
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }

        $query .= implode(", ", $updates);
        $query .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->conn->prepare($query);
        
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }

        return $stmt->execute();
    }
}
?>