<?php
class Room {
    private $conn;
    private $table = 'rooms';
    private $roomTypeTable = 'room_types';

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

    // Lấy tất cả phòng
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['room_type_id'])) {
            $whereClause .= " AND r.room_type_id = :room_type_id";
            $params[':room_type_id'] = $filters['room_type_id'];
        }
        
        if (!empty($filters['floor'])) {
            $whereClause .= " AND r.floor = :floor";
            $params[':floor'] = $filters['floor'];
        }
        
        if (!empty($filters['view_type'])) {
            $whereClause .= " AND r.view_type = :view_type";
            $params[':view_type'] = $filters['view_type'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (r.room_number LIKE :search OR rt.type_name LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        // Query tổng số records
        $countQuery = "SELECT COUNT(*) as total 
                      FROM {$this->table} r
                      LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                      $whereClause";
        
        $stmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Query dữ liệu
        $query = "SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.amenities
                  FROM {$this->table} r
                  LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                  $whereClause
                  ORDER BY r.room_number
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse amenities JSON
        foreach ($rooms as &$room) {
            if ($room['amenities']) {
                $room['amenities'] = json_decode($room['amenities'], true);
            }
        }
        
        return [
            'data' => $rooms,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy phòng theo ID
    public function getById($id) {
        $query = "SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.description, rt.amenities
                  FROM {$this->table} r
                  LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                  WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room && $room['amenities']) {
            $room['amenities'] = json_decode($room['amenities'], true);
        }
        
        return $room;
    }

    // Lấy phòng theo số phòng
    public function getByRoomNumber($room_number) {
        $query = "SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.amenities
                  FROM {$this->table} r
                  LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                  WHERE r.room_number = :room_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_number', $room_number);
        $stmt->execute();
        
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room && $room['amenities']) {
            $room['amenities'] = json_decode($room['amenities'], true);
        }
        
        return $room;
    }

    // Tạo phòng mới
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (room_number, room_type_id, floor, view_type, status, image_url) 
                  VALUES (:room_number, :room_type_id, :floor, :view_type, :status, :image_url)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':room_number', $data['room_number']);
        $stmt->bindParam(':room_type_id', $data['room_type_id'], PDO::PARAM_INT);
        $stmt->bindParam(':floor', $data['floor'], PDO::PARAM_INT);
        $stmt->bindParam(':view_type', $data['view_type']);
        $stmt->bindParam(':status', $data['status'] ?? 'available');
        $stmt->bindParam(':image_url', $data['image_url']);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Cập nhật phòng
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['room_number'])) {
            $setClause[] = "room_number = :room_number";
            $params[':room_number'] = $data['room_number'];
        }
        
        if (isset($data['room_type_id'])) {
            $setClause[] = "room_type_id = :room_type_id";
            $params[':room_type_id'] = $data['room_type_id'];
        }
        
        if (isset($data['floor'])) {
            $setClause[] = "floor = :floor";
            $params[':floor'] = $data['floor'];
        }
        
        if (isset($data['view_type'])) {
            $setClause[] = "view_type = :view_type";
            $params[':view_type'] = $data['view_type'];
        }
        
        if (isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['image_url'])) {
            $setClause[] = "image_url = :image_url";
            $params[':image_url'] = $data['image_url'];
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

    // Xóa phòng
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Kiểm tra số phòng đã tồn tại
    public function roomNumberExists($room_number, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE room_number = :room_number";
        $params = [':room_number' => $room_number];
        
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

    // Lấy phòng trống theo ngày
    public function getAvailableRooms($check_in, $check_out, $room_type_id = null) {
        $query = "SELECT r.*, rt.type_name, rt.base_price, rt.capacity, rt.amenities
                  FROM {$this->table} r
                  LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                  WHERE r.status = 'available'
                  AND r.id NOT IN (
                      SELECT room_id FROM bookings 
                      WHERE (:check_in < check_out AND :check_out > check_in)
                      AND status NOT IN ('cancelled', 'checked_out')
                  )";
        
        $params = [
            ':check_in' => $check_in,
            ':check_out' => $check_out
        ];
        
        if ($room_type_id) {
            $query .= " AND r.room_type_id = :room_type_id";
            $params[':room_type_id'] = $room_type_id;
        }
        
        $query .= " ORDER BY rt.base_price, r.room_number";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse amenities JSON
        foreach ($rooms as &$room) {
            if ($room['amenities']) {
                $room['amenities'] = json_decode($room['amenities'], true);
            }
        }
        
        return $rooms;
    }

    // Kiểm tra phòng có trống không
    public function checkAvailability($room_id, $check_in, $check_out) {
        $query = "SELECT COUNT(*) as count 
                  FROM bookings 
                  WHERE room_id = :room_id
                  AND (:check_in < check_out AND :check_out > check_in)
                  AND status NOT IN ('cancelled', 'checked_out')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    // Cập nhật trạng thái phòng
    public function updateStatus($room_id, $status) {
        $query = "UPDATE {$this->table} SET status = :status WHERE id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Lấy thống kê phòng
    public function getStats() {
        $stats = [];
        
        // Tổng số phòng
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['total_rooms'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Phòng theo trạng thái
        $query = "SELECT status, COUNT(*) as count 
                  FROM {$this->table} 
                  GROUP BY status";
        $stmt = $this->conn->query($query);
        $stats['rooms_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Phòng theo loại
        $query = "SELECT rt.type_name, COUNT(r.id) as count
                  FROM {$this->table} r
                  LEFT JOIN {$this->roomTypeTable} rt ON r.room_type_id = rt.id
                  GROUP BY rt.id, rt.type_name";
        $stmt = $this->conn->query($query);
        $stats['rooms_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Phòng theo tầng
        $query = "SELECT floor, COUNT(*) as count 
                  FROM {$this->table} 
                  WHERE floor IS NOT NULL 
                  GROUP BY floor 
                  ORDER BY floor";
        $stmt = $this->conn->query($query);
        $stats['rooms_by_floor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}