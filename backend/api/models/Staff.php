<?php
class Staff {
    private $conn;
    private $table = 'staff';
    private $usersTable = 'users';

    public $id;
    public $user_id;
    public $staff_code;
    public $position;
    public $department;
    public $hire_date;
    public $salary;
    public $emergency_contact;
    public $notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Tạo mã staff
    private function generateStaffCode() {
        $prefix = 'STAFF';
        $date = date('ym');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }

    // Lấy tất cả staff
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND s.department = :department";
            $params[':department'] = $filters['department'];
        }
        
        if (!empty($filters['position'])) {
            $whereClause .= " AND s.position LIKE :position";
            $params[':position'] = "%{$filters['position']}%";
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (u.full_name LIKE :search OR s.staff_code LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        // Query tổng số records
        $countQuery = "SELECT COUNT(*) as total 
                      FROM {$this->table} s
                      LEFT JOIN {$this->usersTable} u ON s.user_id = u.id
                      $whereClause";
        
        $stmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $totalResult['total'];
        
        // Query dữ liệu
        $query = "SELECT s.*, u.full_name, u.email, u.phone, u.address
                  FROM {$this->table} s
                  LEFT JOIN {$this->usersTable} u ON s.user_id = u.id
                  $whereClause
                  ORDER BY s.hire_date DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $staffList,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Lấy staff theo ID
    public function getById($id) {
        $query = "SELECT s.*, u.full_name, u.email, u.phone, u.address, u.user_type, u.status as user_status
                  FROM {$this->table} s
                  LEFT JOIN {$this->usersTable} u ON s.user_id = u.id
                  WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy staff theo user_id
    public function getByUserId($user_id) {
        $query = "SELECT s.*, u.full_name, u.email, u.phone, u.address
                  FROM {$this->table} s
                  LEFT JOIN {$this->usersTable} u ON s.user_id = u.id
                  WHERE s.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tạo staff mới
    public function create($data) {
        // Tạo staff code
        $staff_code = $this->generateStaffCode();
        
        $query = "INSERT INTO {$this->table} 
                  (user_id, staff_code, position, department, hire_date, salary, emergency_contact, notes) 
                  VALUES (:user_id, :staff_code, :position, :department, :hire_date, :salary, :emergency_contact, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':staff_code', $staff_code);
        $stmt->bindParam(':position', $data['position']);
        $stmt->bindParam(':department', $data['department']);
        $stmt->bindParam(':hire_date', $data['hire_date']);
        $stmt->bindParam(':salary', $data['salary']);
        $stmt->bindParam(':emergency_contact', $data['emergency_contact']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->staff_code = $staff_code;
            return true;
        }
        return false;
    }

    // Cập nhật staff
    public function update($id, $data) {
        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['position'])) {
            $setClause[] = "position = :position";
            $params[':position'] = $data['position'];
        }
        
        if (isset($data['department'])) {
            $setClause[] = "department = :department";
            $params[':department'] = $data['department'];
        }
        
        if (isset($data['salary'])) {
            $setClause[] = "salary = :salary";
            $params[':salary'] = $data['salary'];
        }
        
        if (isset($data['emergency_contact'])) {
            $setClause[] = "emergency_contact = :emergency_contact";
            $params[':emergency_contact'] = $data['emergency_contact'];
        }
        
        if (isset($data['notes'])) {
            $setClause[] = "notes = :notes";
            $params[':notes'] = $data['notes'];
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

    // Xóa staff
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Kiểm tra staff code đã tồn tại
    public function staffCodeExists($staff_code, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE staff_code = :staff_code";
        $params = [':staff_code' => $staff_code];
        
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

    // Lấy thống kê staff
    public function getStats() {
        $stats = [];
        
        // Tổng số staff
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['total_staff'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Staff theo department
        $query = "SELECT department, COUNT(*) as count 
                  FROM {$this->table} 
                  GROUP BY department";
        $stmt = $this->conn->query($query);
        $stats['staff_by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Staff theo position
        $query = "SELECT position, COUNT(*) as count 
                  FROM {$this->table} 
                  GROUP BY position 
                  ORDER BY count DESC";
        $stmt = $this->conn->query($query);
        $stats['staff_by_position'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tổng lương
        $query = "SELECT SUM(salary) as total_salary, 
                         AVG(salary) as avg_salary,
                         MIN(salary) as min_salary,
                         MAX(salary) as max_salary
                  FROM {$this->table}";
        $stmt = $this->conn->query($query);
        $stats['salary_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    // Tìm staff theo tên hoặc mã
    public function search($keyword) {
        $query = "SELECT s.*, u.full_name, u.email, u.phone
                  FROM {$this->table} s
                  LEFT JOIN {$this->usersTable} u ON s.user_id = u.id
                  WHERE (u.full_name LIKE :keyword OR s.staff_code LIKE :keyword OR s.position LIKE :keyword)
                  ORDER BY u.full_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':keyword', "%$keyword%");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}