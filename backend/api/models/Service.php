<?php
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
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE status = 'available'";
        $params = [];
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (service_name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['min_price'])) {
            $whereClause .= " AND price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $whereClause .= " AND price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
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
                  ORDER BY category, service_name
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $services,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy tất cả dịch vụ cho admin (bao gồm cả unavailable)
    public function getAllForAdmin($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (service_name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
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
                  ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $services,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy dịch vụ theo ID
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tạo dịch vụ mới
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (service_name, description, price, category, status) 
                  VALUES (:service_name, :description, :price, :category, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':service_name', $data['service_name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':status', $data['status'] ?? 'available');
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Cập nhật dịch vụ
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['service_name'])) {
            $setClause[] = "service_name = :service_name";
            $params[':service_name'] = $data['service_name'];
        }
        
        if (isset($data['description'])) {
            $setClause[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['price'])) {
            $setClause[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        
        if (isset($data['category'])) {
            $setClause[] = "category = :category";
            $params[':category'] = $data['category'];
        }
        
        if (isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = $data['status'];
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

    // Xóa dịch vụ (soft delete)
    public function delete($id) {
        $query = "UPDATE {$this->table} 
                  SET status = 'unavailable' 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Kiểm tra tên dịch vụ đã tồn tại
    public function serviceNameExists($service_name, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE service_name = :service_name";
        $params = [':service_name' => $service_name];
        
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

    // Lấy dịch vụ theo category
    public function getByCategory($category, $limit = null) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE category = :category AND status = 'available' 
                  ORDER BY price";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy thống kê dịch vụ
    public function getStats($start_date = null, $end_date = null) {
        $stats = [];
        
        $whereClause = "";
        $params = [];
        
        if ($start_date && $end_date) {
            $whereClause = "WHERE bs.created_at BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        // Tổng số dịch vụ
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'available'";
        $stmt = $this->conn->query($query);
        $stats['total_services'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Dịch vụ theo category
        $query = "SELECT category, COUNT(*) as count 
                  FROM {$this->table} 
                  WHERE status = 'available' 
                  GROUP BY category";
        $stmt = $this->conn->query($query);
        $stats['services_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dịch vụ phổ biến nhất
        $query = "SELECT s.id, s.service_name, s.category,
                         SUM(bs.quantity) as total_quantity,
                         SUM(bs.price * bs.quantity) as total_revenue,
                         COUNT(DISTINCT bs.booking_id) as booking_count
                  FROM services s
                  LEFT JOIN booking_services bs ON s.id = bs.service_id
                  LEFT JOIN bookings b ON bs.booking_id = b.id
                  WHERE b.payment_status = 'paid'
                  $whereClause
                  GROUP BY s.id
                  ORDER BY total_quantity DESC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['popular_services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Doanh thu theo category
        $query = "SELECT s.category,
                         SUM(bs.price * bs.quantity) as revenue,
                         COUNT(DISTINCT bs.booking_id) as booking_count
                  FROM services s
                  LEFT JOIN booking_services bs ON s.id = bs.service_id
                  LEFT JOIN bookings b ON bs.booking_id = b.id
                  WHERE b.payment_status = 'paid'
                  $whereClause
                  GROUP BY s.category
                  ORDER BY revenue DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats['revenue_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    // Tìm kiếm dịch vụ
    public function search($keyword, $limit = 10) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE (service_name LIKE :keyword OR description LIKE :keyword) 
                  AND status = 'available'
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':keyword', "%$keyword%");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy giá trị trung bình, min, max
    public function getPriceStats() {
        $query = "SELECT 
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    COUNT(*) as total_services
                  FROM {$this->table} 
                  WHERE status = 'available'";
        
        $stmt = $this->conn->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}