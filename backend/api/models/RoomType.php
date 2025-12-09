<?php
class RoomType {
    private $conn;
    private $table = 'room_types';

    public $id;
    public $type_name;
    public $description;
    public $base_price;
    public $capacity;
    public $amenities;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy tất cả loại phòng
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND type_name LIKE :search";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['min_price'])) {
            $whereClause .= " AND base_price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $whereClause .= " AND base_price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['min_capacity'])) {
            $whereClause .= " AND capacity >= :min_capacity";
            $params[':min_capacity'] = $filters['min_capacity'];
        }
        
        // Query tổng số records
        $countQuery = "SELECT COUNT(*) as total FROM {$this->table} $whereClause";
        $stmt = $this->conn->prepare($countQuery);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Query dữ liệu
        $query = "SELECT * FROM {$this->table} 
                  $whereClause
                  ORDER BY base_price
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse amenities JSON
        foreach ($roomTypes as &$roomType) {
            if ($roomType['amenities']) {
                $roomType['amenities'] = json_decode($roomType['amenities'], true);
            }
        }
        
        return [
            'data' => $roomTypes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy loại phòng theo ID
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $roomType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($roomType && $roomType['amenities']) {
            $roomType['amenities'] = json_decode($roomType['amenities'], true);
        }
        
        return $roomType;
    }

    // Tạo loại phòng mới
    public function create($data) {
        // Chuyển amenities thành JSON nếu là array
        if (isset($data['amenities']) && is_array($data['amenities'])) {
            $data['amenities'] = json_encode($data['amenities']);
        }
        
        $query = "INSERT INTO {$this->table} 
                  (type_name, description, base_price, capacity, amenities) 
                  VALUES (:type_name, :description, :base_price, :capacity, :amenities)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':type_name', $data['type_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':base_price', $data['base_price']);
        $stmt->bindParam(':capacity', $data['capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':amenities', $data['amenities']);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Cập nhật loại phòng
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['type_name'])) {
            $setClause[] = "type_name = :type_name";
            $params[':type_name'] = $data['type_name'];
        }
        
        if (isset($data['description'])) {
            $setClause[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['base_price'])) {
            $setClause[] = "base_price = :base_price";
            $params[':base_price'] = $data['base_price'];
        }
        
        if (isset($data['capacity'])) {
            $setClause[] = "capacity = :capacity";
            $params[':capacity'] = $data['capacity'];
        }
        
        if (isset($data['amenities'])) {
            // Chuyển amenities thành JSON nếu là array
            if (is_array($data['amenities'])) {
                $data['amenities'] = json_encode($data['amenities']);
            }
            $setClause[] = "amenities = :amenities";
            $params[':amenities'] = $data['amenities'];
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

    // Xóa loại phòng
    public function delete($id) {
        // Kiểm tra xem có phòng nào đang sử dụng loại phòng này không
        $checkQuery = "SELECT COUNT(*) as count FROM rooms WHERE room_type_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return false; // Không thể xóa vì có phòng đang sử dụng
        }
        
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Kiểm tra tên loại phòng đã tồn tại
    public function typeNameExists($type_name, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE type_name = :type_name";
        $params = [':type_name' => $type_name];
        
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

    // Lấy thống kê loại phòng
    public function getStats() {
        $stats = [];
        
        // Tổng số loại phòng
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['total_room_types'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Loại phòng phổ biến nhất (dựa trên số phòng)
        $query = "SELECT rt.id, rt.type_name, COUNT(r.id) as room_count
                  FROM {$this->table} rt
                  LEFT JOIN rooms r ON rt.id = r.room_type_id
                  GROUP BY rt.id, rt.type_name
                  ORDER BY room_count DESC";
        $stmt = $this->conn->query($query);
        $stats['popular_room_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Giá trung bình
        $query = "SELECT AVG(base_price) as avg_price, 
                         MIN(base_price) as min_price, 
                         MAX(base_price) as max_price 
                  FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['price_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Sức chứa trung bình
        $query = "SELECT AVG(capacity) as avg_capacity, 
                         MIN(capacity) as min_capacity, 
                         MAX(capacity) as max_capacity 
                  FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['capacity_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    // Lấy amenities phổ biến
    public function getPopularAmenities() {
        $query = "SELECT amenities FROM {$this->table} WHERE amenities IS NOT NULL";
        $stmt = $this->conn->query($query);
        $amenitiesList = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $amenitiesCount = [];
        
        foreach ($amenitiesList as $amenitiesJson) {
            $amenities = json_decode($amenitiesJson, true);
            if (is_array($amenities)) {
                foreach ($amenities as $amenity) {
                    if (!isset($amenitiesCount[$amenity])) {
                        $amenitiesCount[$amenity] = 0;
                    }
                    $amenitiesCount[$amenity]++;
                }
            }
        }
        
        arsort($amenitiesCount);
        return array_slice($amenitiesCount, 0, 10, true);
    }
}