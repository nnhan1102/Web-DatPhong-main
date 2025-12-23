<?php
// models/Room.php - FIXED VERSION WITH updateStatus()

class Room {
    private $conn;
    private $table_name = "rooms";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get room by ID
    public function getById($id) {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.type_name as room_type_name,
                        rt.base_price,
                        rt.capacity,
                        rt.description,
                        rt.amenities
                      FROM " . $this->table_name . " r
                      LEFT JOIN room_types rt ON r.room_type_id = rt.id
                      WHERE r.id = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $room = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format the data
                $room['room_type'] = $room['room_type_name'];
                $room['price_per_night'] = $room['base_price'];
                unset($room['room_type_name']);
                unset($room['base_price']);
                
                // Parse amenities JSON if exists
                if (!empty($room['amenities'])) {
                    $room['amenities'] = json_decode($room['amenities'], true);
                } else {
                    $room['amenities'] = [];
                }
                
                // Add default image if empty
                if (empty($room['image_url'])) {
                    $room['image_url'] = $this->getDefaultImage($room['view_type']);
                }
                
                return $room;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error in getById($id): " . $e->getMessage());
            return null;
        }
    }

<<<<<<< HEAD
    // THÊM PHƯƠNG THỨC updateStatus() - QUAN TRỌNG
    public function updateStatus($room_id, $status) {
        try {
            // Validate status
            $valid_statuses = ['available', 'occupied', 'maintenance', 'cleaning', 'reserved'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception("Invalid room status: $status");
            }
            
            $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $status);
            $stmt->bindParam(2, $room_id);
            
            $result = $stmt->execute();
            
            // Log for debugging
            file_put_contents(__DIR__ . '/../controllers/booking_debug.log', 
                date('Y-m-d H:i:s') . " - Room::updateStatus() called: Room $room_id -> $status, Result: " . ($result ? 'success' : 'failed') . "\n",
                FILE_APPEND
            );
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updateStatus($room_id, $status): " . $e->getMessage());
            
            // Log for debugging
            file_put_contents(__DIR__ . '/../controllers/booking_debug.log', 
                date('Y-m-d H:i:s') . " - Room::updateStatus() ERROR: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            
            return false;
        }
=======
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
        // Validate data
        if (empty($data['room_number']) || empty($data['room_type_id'])) {
            throw new \Exception("Vui lòng nhập đầy đủ thông tin bắt buộc");
        }

        if ($this->roomNumberExists($data['room_number'])) {
            throw new \Exception("Số phòng đã tồn tại");
        }

        $query = "INSERT INTO {$this->table} 
                  (room_number, room_type_id, floor, view_type, status, image_url) 
                  VALUES (:room_number, :room_type_id, :floor, :view_type, :status, :image_url)";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $room_number = htmlspecialchars(strip_tags($data['room_number']));
        $room_type_id = (int)$data['room_type_id'];
        $floor = !empty($data['floor']) ? (int)$data['floor'] : null;
        $view_type = htmlspecialchars(strip_tags($data['view_type']));
        $status = htmlspecialchars(strip_tags($data['status'] ?? 'available'));
        $image_url = !empty($data['image_url']) ? htmlspecialchars(strip_tags($data['image_url'])) : null;

        $stmt->bindParam(':room_number', $room_number);
        $stmt->bindParam(':room_type_id', $room_type_id, PDO::PARAM_INT);
        $stmt->bindParam(':floor', $floor, PDO::PARAM_INT);
        $stmt->bindParam(':view_type', $view_type);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':image_url', $image_url);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Cập nhật phòng
    public function update($id, $data) {
        // Validate duplicates if room_number is being changed
        if (isset($data['room_number'])) {
            if ($this->roomNumberExists($data['room_number'], $id)) {
                throw new \Exception("Số phòng đã tồn tại");
            }
        }

        $setClause = [];
        $params = [':id' => $id];
        
        if (isset($data['room_number'])) {
            $setClause[] = "room_number = :room_number";
            $params[':room_number'] = htmlspecialchars(strip_tags($data['room_number']));
        }
        
        if (isset($data['room_type_id'])) {
            $setClause[] = "room_type_id = :room_type_id";
            $params[':room_type_id'] = (int)$data['room_type_id'];
        }
        
        if (isset($data['floor'])) {
            $setClause[] = "floor = :floor";
            $params[':floor'] = !empty($data['floor']) ? (int)$data['floor'] : null;
        }
        
        if (isset($data['view_type'])) {
            $setClause[] = "view_type = :view_type";
            $params[':view_type'] = htmlspecialchars(strip_tags($data['view_type']));
        }
        
        if (isset($data['status'])) {
            $setClause[] = "status = :status";
            $params[':status'] = htmlspecialchars(strip_tags($data['status']));
        }
        
        if (isset($data['image_url'])) {
            $setClause[] = "image_url = :image_url";
            $params[':image_url'] = !empty($data['image_url']) ? htmlspecialchars(strip_tags($data['image_url'])) : null;
        }
        
        if (empty($setClause)) {
            return false;
        }
        
        $query = "UPDATE {$this->table} 
                  SET " . implode(', ', $setClause) . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value); // bindValue works for both string and int based on value type if not specified
        }
        
        return $stmt->execute();
>>>>>>> 582f04a39e270fe9b49fa2236a67353f94b15850
    }

    // THÊM PHƯƠNG THỨC getByStatus()
    public function getByStatus($status) {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.type_name as room_type_name,
                        rt.base_price,
                        rt.capacity,
                        rt.description,
                        rt.amenities
                      FROM " . $this->table_name . " r
                      LEFT JOIN room_types rt ON r.room_type_id = rt.id
                      WHERE r.status = ? 
                      ORDER BY r.floor, r.room_number";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $status);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data
            foreach ($rooms as &$room) {
                $room['room_type'] = $room['room_type_name'];
                $room['price_per_night'] = $room['base_price'];
                unset($room['room_type_name']);
                unset($room['base_price']);
                
                // Parse amenities JSON if exists
                if (!empty($room['amenities'])) {
                    $room['amenities'] = json_decode($room['amenities'], true);
                } else {
                    $room['amenities'] = [];
                }
                
                // Add default image if empty
                if (empty($room['image_url'])) {
                    $room['image_url'] = $this->getDefaultImage($room['view_type']);
                }
            }
            
            return $rooms;
        } catch (Exception $e) {
            error_log("Error in getByStatus($status): " . $e->getMessage());
            return [];
        }
    }

    // Get available rooms
    public function getAvailableRooms() {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.type_name as room_type_name,
                        rt.base_price,
                        rt.capacity,
                        rt.description,
                        rt.amenities
                      FROM " . $this->table_name . " r
                      LEFT JOIN room_types rt ON r.room_type_id = rt.id
                      WHERE r.status = 'available' 
                      ORDER BY r.floor, r.room_number";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data
            foreach ($rooms as &$room) {
                $room['room_type'] = $room['room_type_name'];
                $room['price_per_night'] = $room['base_price'];
                unset($room['room_type_name']);
                unset($room['base_price']);
                
                // Parse amenities JSON if exists
                if (!empty($room['amenities'])) {
                    $room['amenities'] = json_decode($room['amenities'], true);
                } else {
                    $room['amenities'] = [];
                }
                
                // Add default image if empty
                if (empty($room['image_url'])) {
                    $room['image_url'] = $this->getDefaultImage($room['view_type']);
                }
            }
            
            return $rooms;
        } catch (Exception $e) {
            error_log("Error in getAvailableRooms(): " . $e->getMessage());
            return [];
        }
    }

    // Get room statistics
    public function getStatistics() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_rooms,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                        SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning_rooms
                      FROM " . $this->table_name;

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getStatistics(): " . $e->getMessage());
            return [
                'total_rooms' => 0,
                'available_rooms' => 0,
                'occupied_rooms' => 0,
                'maintenance_rooms' => 0,
                'cleaning_rooms' => 0
            ];
        }
    }

    // Get all rooms with room type details
    public function getAll() {
        try {
            $query = "SELECT 
                        r.*, 
                        rt.type_name as room_type_name,
                        rt.base_price,
                        rt.capacity,
                        rt.description,
                        rt.amenities
                      FROM " . $this->table_name . " r
                      LEFT JOIN room_types rt ON r.room_type_id = rt.id
                      ORDER BY r.floor, r.room_number";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data for frontend
            foreach ($rooms as &$room) {
                $room['room_type'] = $room['room_type_name'];
                $room['price_per_night'] = $room['base_price'];
                unset($room['room_type_name']);
                unset($room['base_price']);
                
                // Parse amenities JSON if exists
                if (!empty($room['amenities'])) {
                    $room['amenities'] = json_decode($room['amenities'], true);
                } else {
                    $room['amenities'] = [];
                }
                
                // Add default image if empty
                if (empty($room['image_url'])) {
                    $room['image_url'] = $this->getDefaultImage($room['view_type']);
                }
            }
            
            return $rooms;
        } catch (Exception $e) {
            error_log("Error in getAll(): " . $e->getMessage());
            return [];
        }
    }

    // Helper function for default images
    private function getDefaultImage($view_type) {
        $images = [
            'city' => 'image/anh49.jpg',
            'sea' => 'image/anh50.jpg',
            'garden' => 'image/anh51.jpg',
            'pool' => 'image/anh52.jpg'
        ];
        
        return $images[$view_type] ?? 'image/anh49.jpg';
    }

    // THÊM: Kiểm tra room có sẵn sàng cho booking không
    public function isRoomAvailableForBooking($room_id, $check_in, $check_out) {
        try {
            $query = "SELECT COUNT(*) as count 
                      FROM bookings 
                      WHERE room_id = ? 
                      AND status NOT IN ('cancelled', 'completed')
                      AND (
                          (check_in <= ? AND check_out >= ?) OR
                          (check_in <= ? AND check_out >= ?) OR
                          (check_in >= ? AND check_out <= ?)
                      )";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $room_id);
            $stmt->bindParam(2, $check_out);
            $stmt->bindParam(3, $check_in);
            $stmt->bindParam(4, $check_out);
            $stmt->bindParam(5, $check_out);
            $stmt->bindParam(6, $check_in);
            $stmt->bindParam(7, $check_out);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0;
        } catch (Exception $e) {
            error_log("Error in isRoomAvailableForBooking(): " . $e->getMessage());
            return false;
        }
    }
}
?>