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