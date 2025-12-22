<?php
namespace Models;

use PDO;

class Booking {
    private $conn;
    private $table = 'bookings';
    private $bookingServicesTable = 'booking_services';

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

    // Tạo mã booking
    private function generateBookingCode() {
        $prefix = 'BK';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return $prefix . $date . $random;
    }

    // Tạo booking mới
    public function create($data) {
        // Tạo booking code
        $booking_code = $this->generateBookingCode();
        
        $query = "INSERT INTO {$this->table} 
                  (booking_code, customer_id, room_id, check_in, check_out, 
                   num_guests, total_price, special_requests, payment_method, payment_status, status) 
                  VALUES (:booking_code, :customer_id, :room_id, :check_in, :check_out, 
                          :num_guests, :total_price, :special_requests, :payment_method, :payment_status, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':booking_code', $booking_code);
        $stmt->bindValue(':customer_id', $data['customer_id'], PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $data['room_id'], PDO::PARAM_INT);
        $stmt->bindValue(':check_in', $data['check_in']);
        $stmt->bindValue(':check_out', $data['check_out']);
        $stmt->bindValue(':num_guests', $data['num_guests'], PDO::PARAM_INT);
        $stmt->bindValue(':total_price', $data['total_price']);
        $stmt->bindValue(':special_requests', $data['special_requests']);
        $stmt->bindValue(':payment_method', $data['payment_method']);
        $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'pending');
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->booking_code = $booking_code;
            
            // Thêm dịch vụ nếu có
            if (!empty($data['services'])) {
                $this->addServices($this->id, $data['services']);
            }
            
            return true;
        }
        return false;
    }

    // Thêm dịch vụ vào booking
    private function addServices($booking_id, $services) {
        foreach ($services as $service) {
            $query = "INSERT INTO {$this->bookingServicesTable} 
                      (booking_id, service_id, quantity, price, service_date) 
                      VALUES (:booking_id, :service_id, :quantity, :price, :service_date)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindValue(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->bindValue(':service_id', $service['service_id'], PDO::PARAM_INT);
            $stmt->bindValue(':quantity', $service['quantity'], PDO::PARAM_INT);
            $stmt->bindValue(':price', $service['price']);
            $stmt->bindValue(':service_date', $service['service_date'] ?? null);
            
            $stmt->execute();
        }
    }

    // Lấy tất cả bookings
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND b.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $whereClause .= " AND b.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['customer_id'])) {
            $whereClause .= " AND b.customer_id = :customer_id";
            $params[':customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['room_id'])) {
            $whereClause .= " AND b.room_id = :room_id";
            $params[':room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['check_in_from'])) {
            $whereClause .= " AND b.check_in >= :check_in_from";
            $params[':check_in_from'] = $filters['check_in_from'];
        }
        
        if (!empty($filters['check_in_to'])) {
            $whereClause .= " AND b.check_in <= :check_in_to";
            $params[':check_in_to'] = $filters['check_in_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (b.booking_code LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        // Query tổng số records
        $countQuery = "SELECT COUNT(*) as total 
                      FROM {$this->table} b
                      LEFT JOIN users u ON b.customer_id = u.id
                      $whereClause";
        
        $stmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Query dữ liệu
        $query = "SELECT b.*, 
                         u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                         r.room_number, rt.type_name as room_type,
                         (SELECT SUM(bs.price * bs.quantity) 
                          FROM booking_services bs 
                          WHERE bs.booking_id = b.id) as services_total
                  FROM {$this->table} b
                  LEFT JOIN users u ON b.customer_id = u.id
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  $whereClause
                  ORDER BY b.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính tổng tiền (phòng + dịch vụ)
        foreach ($bookings as &$booking) {
            $booking['services_total'] = (float)$booking['services_total'] ?? 0;
            $booking['grand_total'] = $booking['total_price'] + $booking['services_total'];
        }
        
        return [
            'data' => $bookings,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy booking theo ID
    public function getById($id) {
        $query = "SELECT b.*, 
                         u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address,
                         r.room_number, r.floor, r.view_type, r.image_url as room_image,
                         rt.type_name as room_type, rt.base_price as room_price, rt.capacity,
                         (SELECT SUM(bs.price * bs.quantity) 
                          FROM booking_services bs 
                          WHERE bs.booking_id = b.id) as services_total
                  FROM {$this->table} b
                  LEFT JOIN users u ON b.customer_id = u.id
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            $booking['services_total'] = (float)$booking['services_total'] ?? 0;
            $booking['grand_total'] = $booking['total_price'] + $booking['services_total'];
            
            // Lấy dịch vụ
            $booking['services'] = $this->getBookingServices($id);
        }
        
        return $booking;
    }

    // Lấy booking theo mã
    public function getByCode($booking_code) {
        $query = "SELECT b.*, 
                         u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                         r.room_number,
                         rt.type_name as room_type
                  FROM {$this->table} b
                  LEFT JOIN users u ON b.customer_id = u.id
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.booking_code = :booking_code";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_code', $booking_code);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy dịch vụ của booking
    private function getBookingServices($booking_id) {
        $query = "SELECT bs.*, s.service_name, s.category
                  FROM {$this->bookingServicesTable} bs
                  LEFT JOIN services s ON bs.service_id = s.id
                  WHERE bs.booking_id = :booking_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật booking
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['payment_status'])) {
            $setClause[] = "payment_status = :payment_status";
            $params[':payment_status'] = $data['payment_status'];
        }
        
        if (isset($data['payment_method'])) {
            $setClause[] = "payment_method = :payment_method";
            $params[':payment_method'] = $data['payment_method'];
        }
        
        if (isset($data['special_requests'])) {
            $setClause[] = "special_requests = :special_requests";
            $params[':special_requests'] = $data['special_requests'];
        }
        
        if (isset($data['check_in'])) {
            $setClause[] = "check_in = :check_in";
            $params[':check_in'] = $data['check_in'];
        }
        
        if (isset($data['check_out'])) {
            $setClause[] = "check_out = :check_out";
            $params[':check_out'] = $data['check_out'];
        }
        
        if (isset($data['num_guests'])) {
            $setClause[] = "num_guests = :num_guests";
            $params[':num_guests'] = $data['num_guests'];
        }
        
        if (isset($data['total_price'])) {
            $setClause[] = "total_price = :total_price";
            $params[':total_price'] = $data['total_price'];
        }
        
        if (empty($setClause)) {
            return false;
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $setClause) . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    // Xóa booking (soft delete)
    public function delete($id) {
        // Chỉ cho phép xóa nếu booking ở trạng thái pending hoặc cancelled
        $query = "UPDATE {$this->table} 
                  SET status = 'cancelled' 
                  WHERE id = :id AND status IN ('pending', 'confirmed')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Kiểm tra booking có tồn tại không
    public function exists($id) {
        $query = "SELECT id FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Lấy bookings của customer
    public function getByCustomer($customer_id, $limit = 10) {
        $query = "SELECT b.*, 
                         r.room_number,
                         rt.type_name as room_type
                  FROM {$this->table} b
                  LEFT JOIN rooms r ON b.room_id = r.id
                  LEFT JOIN room_types rt ON r.room_type_id = rt.id
                  WHERE b.customer_id = :customer_id
                  ORDER BY b.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tính số đêm
    public function calculateNights($check_in, $check_out) {
        $start = new DateTime($check_in);
        $end = new DateTime($check_out);
        $interval = $start->diff($end);
        return $interval->days;
    }

    // Kiểm tra availability
    public function checkRoomAvailability($room_id, $check_in, $check_out, $exclude_booking_id = null) {
        $query = "SELECT COUNT(*) as count 
                  FROM {$this->table} 
                  WHERE room_id = :room_id
                  AND (:check_in < check_out AND :check_out > check_in)
                  AND status NOT IN ('cancelled', 'checked_out')";
        
        $params = [
            ':room_id' => $room_id,
            ':check_in' => $check_in,
            ':check_out' => $check_out
        ];
        
        if ($exclude_booking_id) {
            $query .= " AND id != :exclude_booking_id";
            $params[':exclude_booking_id'] = $exclude_booking_id;
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == 0;
    }

    // Lấy thống kê booking
    public function getStats($start_date = null, $end_date = null) {
        $stats = [];
        
        $whereClause = "WHERE status != 'cancelled'";
        $params = [];
        
        if ($start_date) {
            $whereClause .= " AND created_at >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $whereClause .= " AND created_at <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        // Tổng số booking
        $query = "SELECT COUNT(*) as total FROM {$this->table} $whereClause";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['total_bookings'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Doanh thu
        $query = "SELECT SUM(total_price) as revenue 
                  FROM {$this->table} 
                  WHERE payment_status = 'paid' $whereClause";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['revenue'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        
        // Booking theo status
        $query = "SELECT status, COUNT(*) as count 
                  FROM {$this->table} 
                  $whereClause
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['bookings_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Booking theo payment method
        $query = "SELECT payment_method, COUNT(*) as count 
                  FROM {$this->table} 
                  $whereClause
                  GROUP BY payment_method";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['bookings_by_payment_method'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Booking theo tháng
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                         COUNT(*) as count,
                         SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as revenue
                  FROM {$this->table} 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 6";
        $stmt = $this->conn->query($query);
        $stats['monthly_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}