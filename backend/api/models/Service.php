<?php
// Service.php - Model cho dịch vụ

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

    // Lấy tất cả dịch vụ với thông tin đầy đủ
    public function getAllServices($filters = []) {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        // Áp dụng filters nếu có
        $whereConditions = [];
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $whereConditions[] = "(service_name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $whereConditions[] = "category = :category";
            $params[':category'] = $filters['category'];
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $whereConditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (count($whereConditions) > 0) {
            $query .= " AND " . implode(" AND ", $whereConditions);
        }

        // Sorting
        $sortField = isset($filters['sort']) ? $filters['sort'] : 'created_at';
        $sortOrder = isset($filters['order']) ? $filters['order'] : 'DESC';
        $query .= " ORDER BY " . $sortField . " " . $sortOrder;

        // Pagination
        if (isset($filters['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
            
            if (isset($filters['offset'])) {
                $query .= " OFFSET :offset";
                $params[':offset'] = (int)$filters['offset'];
            }
        }

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đếm tổng số dịch vụ
    public function countServices($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        $whereConditions = [];
        $params = [];

        if (isset($filters['search']) && !empty($filters['search'])) {
            $whereConditions[] = "(service_name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $whereConditions[] = "category = :category";
            $params[':category'] = $filters['category'];
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $whereConditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (count($whereConditions) > 0) {
            $query .= " AND " . implode(" AND ", $whereConditions);
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // Lấy dịch vụ theo ID
    public function getServiceById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy dịch vụ theo category
    public function getServicesByCategory($category) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE category = :category AND status = 'available'
                  ORDER BY service_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tạo dịch vụ mới
    public function createService($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (service_name, description, price, category, status, created_at) 
                  VALUES 
                  (:service_name, :description, :price, :category, :status, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $result = $stmt->execute([
            ':service_name' => $data['service_name'] ?? '',
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'] ?? 0,
            ':category' => $data['category'] ?? 'other',
            ':status' => $data['status'] ?? 'available'
        ]);
        
        if ($result) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    // Cập nhật dịch vụ
    public function updateService($id, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  service_name = :service_name,
                  description = :description,
                  price = :price,
                  category = :category,
                  status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            ':service_name' => $data['service_name'] ?? '',
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'] ?? 0,
            ':category' => $data['category'] ?? 'other',
            ':status' => $data['status'] ?? 'available',
            ':id' => $id
        ]);
    }

    // Xóa dịch vụ
    public function deleteService($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Cập nhật trạng thái dịch vụ
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Lấy thống kê dịch vụ
    public function getServiceStats() {
        $query = "SELECT 
                    COUNT(*) as total_services,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_services,
                    SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_services,
                    category,
                    COUNT(*) as category_count,
                    AVG(price) as avg_price
                  FROM " . $this->table . "
                  GROUP BY category
                  ORDER BY category_count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tìm kiếm dịch vụ
    public function searchServices($keyword) {
        $query = "SELECT 
                    id,
                    service_name,
                    description,
                    price,
                    category,
                    status,
                    created_at
                  FROM " . $this->table . "
                  WHERE (service_name LIKE :keyword 
                         OR description LIKE :keyword)
                  ORDER BY service_name
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':keyword', '%' . $keyword . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy dịch vụ được đặt nhiều nhất
    public function getPopularServices($limit = 5) {
        $query = "SELECT 
                    s.id,
                    s.service_name,
                    s.price,
                    s.category,
                    COUNT(bs.id) as booking_count,
                    SUM(bs.quantity) as total_quantity
                  FROM " . $this->table . " s
                  LEFT JOIN booking_services bs ON s.id = bs.service_id
                  GROUP BY s.id
                  ORDER BY booking_count DESC, total_quantity DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả categories
    public function getAllCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table . " ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $categories ?: [];
    }
}
?>