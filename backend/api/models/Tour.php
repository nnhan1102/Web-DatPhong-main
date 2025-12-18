<?php
require_once __DIR__ . '/../config/database.php';

class Tour {
    private $conn;
    private $table_name = "tours";

    // Tour properties
    public $id;
    public $name;
    public $description;
    public $destination;
    public $duration_days;
    public $duration_nights;
    public $departure_point;
    public $departure_time;
    public $return_time;
    public $price_adult;
    public $price_child;
    public $price_infant;
    public $included_services;
    public $excluded_services;
    public $itinerary;
    public $images;
    public $status;
    public $max_capacity;
    public $current_bookings;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new tour
    public function create() {
        try {
            // Check if tour with same name exists
            $checkQuery = "SELECT id FROM " . $this->table_name . " 
                          WHERE name = :name AND deleted_at IS NULL";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':name', $this->name);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                throw new Exception("Tour with this name already exists");
            }

            $query = "INSERT INTO " . $this->table_name . "
                     (name, description, destination, duration_days, duration_nights,
                      departure_point, departure_time, return_time, price_adult,
                      price_child, price_infant, included_services, excluded_services,
                      itinerary, images, status, max_capacity, current_bookings)
                     VALUES
                     (:name, :description, :destination, :duration_days, :duration_nights,
                      :departure_point, :departure_time, :return_time, :price_adult,
                      :price_child, :price_infant, :included_services, :excluded_services,
                      :itinerary, :images, :status, :max_capacity, :current_bookings)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->destination = htmlspecialchars(strip_tags($this->destination));
            $this->departure_point = htmlspecialchars(strip_tags($this->departure_point));
            $this->included_services = htmlspecialchars(strip_tags($this->included_services));
            $this->excluded_services = htmlspecialchars(strip_tags($this->excluded_services));
            $this->itinerary = htmlspecialchars(strip_tags($this->itinerary));
            $this->status = $this->status ?: 'active';
            $this->current_bookings = $this->current_bookings ?: 0;
            
            // Bind parameters
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':destination', $this->destination);
            $stmt->bindParam(':duration_days', $this->duration_days);
            $stmt->bindParam(':duration_nights', $this->duration_nights);
            $stmt->bindParam(':departure_point', $this->departure_point);
            $stmt->bindParam(':departure_time', $this->departure_time);
            $stmt->bindParam(':return_time', $this->return_time);
            $stmt->bindParam(':price_adult', $this->price_adult);
            $stmt->bindParam(':price_child', $this->price_child);
            $stmt->bindParam(':price_infant', $this->price_infant);
            $stmt->bindParam(':included_services', $this->included_services);
            $stmt->bindParam(':excluded_services', $this->excluded_services);
            $stmt->bindParam(':itinerary', $this->itinerary);
            $stmt->bindParam(':images', $this->images);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':max_capacity', $this->max_capacity);
            $stmt->bindParam(':current_bookings', $this->current_bookings);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return $this->id;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Create tour failed: " . $e->getMessage());
        }
    }

    // Read all tours with pagination
    public function readAll($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE deleted_at IS NULL";
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereClause .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['destination'])) {
                $whereClause .= " AND destination LIKE :destination";
                $params[':destination'] = '%' . $filters['destination'] . '%';
            }
            
            if (!empty($filters['min_price'])) {
                $whereClause .= " AND price_adult >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $whereClause .= " AND price_adult <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (name LIKE :search OR description LIKE :search OR destination LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $whereClause;
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get tours with limit and offset
            $query = "SELECT * FROM " . $this->table_name . " 
                     $whereClause 
                     ORDER BY created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'tours' => $tours,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            throw new Exception("Read tours failed: " . $e->getMessage());
        }
    }

    // Read single tour by ID
    public function readOne() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE id = :id AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->name = $row['name'];
                $this->description = $row['description'];
                $this->destination = $row['destination'];
                $this->duration_days = $row['duration_days'];
                $this->duration_nights = $row['duration_nights'];
                $this->departure_point = $row['departure_point'];
                $this->departure_time = $row['departure_time'];
                $this->return_time = $row['return_time'];
                $this->price_adult = $row['price_adult'];
                $this->price_child = $row['price_child'];
                $this->price_infant = $row['price_infant'];
                $this->included_services = $row['included_services'];
                $this->excluded_services = $row['excluded_services'];
                $this->itinerary = $row['itinerary'];
                $this->images = $row['images'];
                $this->status = $row['status'];
                $this->max_capacity = $row['max_capacity'];
                $this->current_bookings = $row['current_bookings'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Read tour failed: " . $e->getMessage());
        }
    }

    // Update tour
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET name = :name,
                         description = :description,
                         destination = :destination,
                         duration_days = :duration_days,
                         duration_nights = :duration_nights,
                         departure_point = :departure_point,
                         departure_time = :departure_time,
                         return_time = :return_time,
                         price_adult = :price_adult,
                         price_child = :price_child,
                         price_infant = :price_infant,
                         included_services = :included_services,
                         excluded_services = :excluded_services,
                         itinerary = :itinerary,
                         images = :images,
                         status = :status,
                         max_capacity = :max_capacity,
                         current_bookings = :current_bookings,
                         updated_at = NOW()
                     WHERE id = :id AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->destination = htmlspecialchars(strip_tags($this->destination));
            $this->departure_point = htmlspecialchars(strip_tags($this->departure_point));
            $this->included_services = htmlspecialchars(strip_tags($this->included_services));
            $this->excluded_services = htmlspecialchars(strip_tags($this->excluded_services));
            $this->itinerary = htmlspecialchars(strip_tags($this->itinerary));
            
            // Bind parameters
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':destination', $this->destination);
            $stmt->bindParam(':duration_days', $this->duration_days);
            $stmt->bindParam(':duration_nights', $this->duration_nights);
            $stmt->bindParam(':departure_point', $this->departure_point);
            $stmt->bindParam(':departure_time', $this->departure_time);
            $stmt->bindParam(':return_time', $this->return_time);
            $stmt->bindParam(':price_adult', $this->price_adult);
            $stmt->bindParam(':price_child', $this->price_child);
            $stmt->bindParam(':price_infant', $this->price_infant);
            $stmt->bindParam(':included_services', $this->included_services);
            $stmt->bindParam(':excluded_services', $this->excluded_services);
            $stmt->bindParam(':itinerary', $this->itinerary);
            $stmt->bindParam(':images', $this->images);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':max_capacity', $this->max_capacity);
            $stmt->bindParam(':current_bookings', $this->current_bookings);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update tour failed: " . $e->getMessage());
        }
    }

    // Soft delete tour
    public function delete() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET deleted_at = NOW(), status = 'inactive'
                     WHERE id = :id AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Delete tour failed: " . $e->getMessage());
        }
    }

    // Permanently delete tour
    public function forceDelete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Force delete tour failed: " . $e->getMessage());
        }
    }

    // Check if tour has available slots
    public function checkAvailability($date) {
        try {
            // Check if tour is active and not deleted
            $query = "SELECT max_capacity, current_bookings, status 
                     FROM " . $this->table_name . "
                     WHERE id = :id AND deleted_at IS NULL AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $tour = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tour) {
                return ['available' => false, 'message' => 'Tour not found or inactive'];
            }
            
            // Check for bookings on specific date
            $bookingQuery = "SELECT SUM(num_adults + num_children + num_infants) as total_booked
                           FROM bookings 
                           WHERE tour_id = :tour_id 
                           AND departure_date = :departure_date 
                           AND booking_status IN ('confirmed', 'pending')";
            
            $bookingStmt = $this->conn->prepare($bookingQuery);
            $bookingStmt->bindParam(':tour_id', $this->id);
            $bookingStmt->bindParam(':departure_date', $date);
            $bookingStmt->execute();
            
            $bookedCount = $bookingStmt->fetch(PDO::FETCH_ASSOC)['total_booked'] ?? 0;
            $availableSlots = $tour['max_capacity'] - $bookedCount;
            
            return [
                'available' => $availableSlots > 0,
                'available_slots' => $availableSlots,
                'max_capacity' => $tour['max_capacity'],
                'booked_count' => $bookedCount
            ];
            
        } catch (Exception $e) {
            throw new Exception("Check availability failed: " . $e->getMessage());
        }
    }

    // Get tours by destination
    public function getByDestination($destination, $limit = 10) {
        try {
            $query = "SELECT * FROM " . $this->table_name . "
                     WHERE destination LIKE :destination 
                     AND status = 'active' 
                     AND deleted_at IS NULL
                     ORDER BY created_at DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $destination = '%' . $destination . '%';
            $stmt->bindParam(':destination', $destination);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get tours by destination failed: " . $e->getMessage());
        }
    }

    // Update booking count
    public function updateBookingCount($change) {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET current_bookings = current_bookings + :change,
                         updated_at = NOW()
                     WHERE id = :id AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':change', $change, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update booking count failed: " . $e->getMessage());
        }
    }

    // Search tours
    public function search($keyword, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM " . $this->table_name . "
                     WHERE (name LIKE :keyword 
                     OR description LIKE :keyword 
                     OR destination LIKE :keyword 
                     OR included_services LIKE :keyword)
                     AND status = 'active' 
                     AND deleted_at IS NULL
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $keyword = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $keyword);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Search tours failed: " . $e->getMessage());
        }
    }

    // Get popular tours
    public function getPopularTours($limit = 5) {
        try {
            $query = "SELECT t.*, COUNT(b.id) as booking_count
                     FROM " . $this->table_name . " t
                     LEFT JOIN bookings b ON t.id = b.tour_id 
                     AND b.booking_status != 'cancelled'
                     WHERE t.status = 'active' 
                     AND t.deleted_at IS NULL
                     GROUP BY t.id
                     ORDER BY booking_count DESC, t.created_at DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get popular tours failed: " . $e->getMessage());
        }
    }

    // Get tours with filters for frontend
    public function getFilteredTours($filters = []) {
        try {
            $whereClause = "WHERE status = 'active' AND deleted_at IS NULL";
            $params = [];
            $orderBy = "ORDER BY created_at DESC";
            
            if (!empty($filters['destination'])) {
                $whereClause .= " AND destination LIKE :destination";
                $params[':destination'] = '%' . $filters['destination'] . '%';
            }
            
            if (!empty($filters['min_price'])) {
                $whereClause .= " AND price_adult >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $whereClause .= " AND price_adult <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            if (!empty($filters['duration'])) {
                $whereClause .= " AND duration_days = :duration";
                $params[':duration'] = $filters['duration'];
            }
            
            if (!empty($filters['sort_by'])) {
                switch ($filters['sort_by']) {
                    case 'price_asc':
                        $orderBy = "ORDER BY price_adult ASC";
                        break;
                    case 'price_desc':
                        $orderBy = "ORDER BY price_adult DESC";
                        break;
                    case 'newest':
                        $orderBy = "ORDER BY created_at DESC";
                        break;
                    case 'popular':
                        $orderBy = "ORDER BY current_bookings DESC";
                        break;
                }
            }
            
            $query = "SELECT * FROM " . $this->table_name . " 
                     $whereClause 
                     $orderBy";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get filtered tours failed: " . $e->getMessage());
        }
    }
}
?>