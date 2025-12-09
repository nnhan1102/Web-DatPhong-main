<?php
require_once __DIR__ . '/../config/database.php';

class Booking {
    private $conn;
    private $table_name = "bookings";

    // Booking properties
    public $id;
    public $booking_code;
    public $user_id;
    public $tour_id;
    public $booking_date;
    public $departure_date;
    public $num_adults;
    public $num_children;
    public $num_infants;
    public $total_price;
    public $payment_method;
    public $payment_status;
    public $booking_status;
    public $customer_notes;
    public $admin_notes;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Generate unique booking code
    private function generateBookingCode() {
        return 'BK' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    // Create new booking
    public function create() {
        try {
            // Generate booking code
            $this->booking_code = $this->generateBookingCode();
            
            // Validate tour availability
            $tourModel = new Tour();
            $tourModel->id = $this->tour_id;
            $availability = $tourModel->checkAvailability($this->departure_date);
            
            if (!$availability['available']) {
                throw new Exception("Tour is fully booked for the selected date");
            }
            
            if (($this->num_adults + $this->num_children + $this->num_infants) > $availability['available_slots']) {
                throw new Exception("Not enough available slots for the selected date");
            }
            
            // Calculate total price
            $tourModel->readOne();
            $this->total_price = ($this->num_adults * $tourModel->price_adult) +
                               ($this->num_children * $tourModel->price_child) +
                               ($this->num_infants * $tourModel->price_infant);
            
            $query = "INSERT INTO " . $this->table_name . "
                     (booking_code, user_id, tour_id, booking_date, departure_date,
                      num_adults, num_children, num_infants, total_price, 
                      payment_method, payment_status, booking_status, 
                      customer_notes, admin_notes)
                     VALUES
                     (:booking_code, :user_id, :tour_id, :booking_date, :departure_date,
                      :num_adults, :num_children, :num_infants, :total_price,
                      :payment_method, :payment_status, :booking_status,
                      :customer_notes, :admin_notes)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->customer_notes = htmlspecialchars(strip_tags($this->customer_notes));
            $this->admin_notes = htmlspecialchars(strip_tags($this->admin_notes));
            $this->payment_method = $this->payment_method ?: 'cash';
            $this->payment_status = $this->payment_status ?: 'pending';
            $this->booking_status = $this->booking_status ?: 'pending';
            $this->booking_date = $this->booking_date ?: date('Y-m-d');
            
            // Bind parameters
            $stmt->bindParam(':booking_code', $this->booking_code);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':tour_id', $this->tour_id);
            $stmt->bindParam(':booking_date', $this->booking_date);
            $stmt->bindParam(':departure_date', $this->departure_date);
            $stmt->bindParam(':num_adults', $this->num_adults);
            $stmt->bindParam(':num_children', $this->num_children);
            $stmt->bindParam(':num_infants', $this->num_infants);
            $stmt->bindParam(':total_price', $this->total_price);
            $stmt->bindParam(':payment_method', $this->payment_method);
            $stmt->bindParam(':payment_status', $this->payment_status);
            $stmt->bindParam(':booking_status', $this->booking_status);
            $stmt->bindParam(':customer_notes', $this->customer_notes);
            $stmt->bindParam(':admin_notes', $this->admin_notes);
            
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Update tour booking count
                $tourModel->updateBookingCount($this->num_adults + $this->num_children + $this->num_infants);
                
                return $this->id;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Create booking failed: " . $e->getMessage());
        }
    }

    // Read all bookings with pagination and filters
    public function readAll($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['booking_status'])) {
                $whereClause .= " AND b.booking_status = :booking_status";
                $params[':booking_status'] = $filters['booking_status'];
            }
            
            if (!empty($filters['payment_status'])) {
                $whereClause .= " AND b.payment_status = :payment_status";
                $params[':payment_status'] = $filters['payment_status'];
            }
            
            if (!empty($filters['user_id'])) {
                $whereClause .= " AND b.user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['tour_id'])) {
                $whereClause .= " AND b.tour_id = :tour_id";
                $params[':tour_id'] = $filters['tour_id'];
            }
            
            if (!empty($filters['booking_code'])) {
                $whereClause .= " AND b.booking_code LIKE :booking_code";
                $params[':booking_code'] = '%' . $filters['booking_code'] . '%';
            }
            
            if (!empty($filters['start_date'])) {
                $whereClause .= " AND DATE(b.created_at) >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereClause .= " AND DATE(b.created_at) <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (b.booking_code LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search OR t.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total 
                          FROM " . $this->table_name . " b
                          LEFT JOIN users u ON b.user_id = u.id
                          LEFT JOIN tours t ON b.tour_id = t.id
                          " . $whereClause;
            
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get bookings with limit and offset
            $query = "SELECT b.*, 
                     u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                     t.name as tour_name, t.destination as tour_destination, t.price_adult, t.price_child, t.price_infant
                     FROM " . $this->table_name . " b
                     LEFT JOIN users u ON b.user_id = u.id
                     LEFT JOIN tours t ON b.tour_id = t.id
                     " . $whereClause . "
                     ORDER BY b.created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'bookings' => $bookings,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            throw new Exception("Read bookings failed: " . $e->getMessage());
        }
    }

    // Read single booking by ID
    public function readOne() {
        try {
            $query = "SELECT b.*, 
                     u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address,
                     t.name as tour_name, t.description as tour_description, t.destination as tour_destination,
                     t.duration_days, t.duration_nights, t.departure_point, t.departure_time, t.return_time,
                     t.price_adult, t.price_child, t.price_infant, t.included_services, t.excluded_services,
                     t.itinerary, t.images
                     FROM " . $this->table_name . " b
                     LEFT JOIN users u ON b.user_id = u.id
                     LEFT JOIN tours t ON b.tour_id = t.id
                     WHERE b.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $this->booking_code = $row['booking_code'];
                $this->user_id = $row['user_id'];
                $this->tour_id = $row['tour_id'];
                $this->booking_date = $row['booking_date'];
                $this->departure_date = $row['departure_date'];
                $this->num_adults = $row['num_adults'];
                $this->num_children = $row['num_children'];
                $this->num_infants = $row['num_infants'];
                $this->total_price = $row['total_price'];
                $this->payment_method = $row['payment_method'];
                $this->payment_status = $row['payment_status'];
                $this->booking_status = $row['booking_status'];
                $this->customer_notes = $row['customer_notes'];
                $this->admin_notes = $row['admin_notes'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                return $row; // Return full data with joins
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Read booking failed: " . $e->getMessage());
        }
    }

    // Update booking
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET departure_date = :departure_date,
                         num_adults = :num_adults,
                         num_children = :num_children,
                         num_infants = :num_infants,
                         total_price = :total_price,
                         payment_method = :payment_method,
                         payment_status = :payment_status,
                         booking_status = :booking_status,
                         customer_notes = :customer_notes,
                         admin_notes = :admin_notes,
                         updated_at = NOW()
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->customer_notes = htmlspecialchars(strip_tags($this->customer_notes));
            $this->admin_notes = htmlspecialchars(strip_tags($this->admin_notes));
            
            // Bind parameters
            $stmt->bindParam(':departure_date', $this->departure_date);
            $stmt->bindParam(':num_adults', $this->num_adults);
            $stmt->bindParam(':num_children', $this->num_children);
            $stmt->bindParam(':num_infants', $this->num_infants);
            $stmt->bindParam(':total_price', $this->total_price);
            $stmt->bindParam(':payment_method', $this->payment_method);
            $stmt->bindParam(':payment_status', $this->payment_status);
            $stmt->bindParam(':booking_status', $this->booking_status);
            $stmt->bindParam(':customer_notes', $this->customer_notes);
            $stmt->bindParam(':admin_notes', $this->admin_notes);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update booking failed: " . $e->getMessage());
        }
    }

    // Cancel booking
    public function cancel() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET booking_status = 'cancelled',
                         updated_at = NOW()
                     WHERE id = :id AND booking_status != 'cancelled'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                // Update tour booking count (subtract)
                $bookingData = $this->readOne();
                if ($bookingData) {
                    $tourModel = new Tour();
                    $tourModel->id = $this->tour_id;
                    $totalPassengers = $bookingData['num_adults'] + $bookingData['num_children'] + $bookingData['num_infants'];
                    $tourModel->updateBookingCount(-$totalPassengers);
                }
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Cancel booking failed: " . $e->getMessage());
        }
    }

    // Update payment status
    public function updatePaymentStatus($status) {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET payment_status = :status,
                         updated_at = NOW()
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $this->id);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            throw new Exception("Update payment status failed: " . $e->getMessage());
        }
    }

    // Get booking by booking code
    public function getByBookingCode($bookingCode) {
        try {
            $query = "SELECT b.*, 
                     u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                     t.name as tour_name, t.destination as tour_destination
                     FROM " . $this->table_name . " b
                     LEFT JOIN users u ON b.user_id = u.id
                     LEFT JOIN tours t ON b.tour_id = t.id
                     WHERE b.booking_code = :booking_code";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':booking_code', $bookingCode);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get booking by code failed: " . $e->getMessage());
        }
    }

    // Get user's bookings
    public function getUserBookings($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT b.*, 
                     t.name as tour_name, t.destination as tour_destination, t.images as tour_images
                     FROM " . $this->table_name . " b
                     LEFT JOIN tours t ON b.tour_id = t.id
                     WHERE b.user_id = :user_id
                     ORDER BY b.created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get user bookings failed: " . $e->getMessage());
        }
    }

    // Get booking statistics
    public function getStatistics($startDate = null, $endDate = null) {
        try {
            $whereClause = "WHERE booking_status != 'cancelled'";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND DATE(created_at) >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND DATE(created_at) <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            $query = "SELECT 
                     COUNT(*) as total_bookings,
                     SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue,
                     AVG(CASE WHEN payment_status = 'paid' THEN total_price END) as avg_booking_value,
                     SUM(num_adults + num_children + num_infants) as total_passengers,
                     COUNT(DISTINCT user_id) as unique_customers,
                     SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                     SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                     SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
                     FROM " . $this->table_name . " 
                     $whereClause";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get booking statistics failed: " . $e->getMessage());
        }
    }

    // Get upcoming departures
    public function getUpcomingDepartures($limit = 10) {
        try {
            $currentDate = date('Y-m-d');
            
            $query = "SELECT b.*, 
                     u.full_name as customer_name, u.phone as customer_phone,
                     t.name as tour_name, t.destination as tour_destination
                     FROM " . $this->table_name . " b
                     LEFT JOIN users u ON b.user_id = u.id
                     LEFT JOIN tours t ON b.tour_id = t.id
                     WHERE b.departure_date >= :current_date
                     AND b.booking_status IN ('confirmed', 'pending')
                     ORDER BY b.departure_date ASC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':current_date', $currentDate);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Get upcoming departures failed: " . $e->getMessage());
        }
    }

    // Check booking status by code
    public function checkStatus($bookingCode) {
        try {
            $booking = $this->getByBookingCode($bookingCode);
            
            if (!$booking) {
                return [
                    'exists' => false,
                    'message' => 'Booking not found'
                ];
            }
            
            return [
                'exists' => true,
                'booking_code' => $booking['booking_code'],
                'booking_status' => $booking['booking_status'],
                'payment_status' => $booking['payment_status'],
                'tour_name' => $booking['tour_name'],
                'departure_date' => $booking['departure_date'],
                'total_price' => $booking['total_price']
            ];
            
        } catch (Exception $e) {
            throw new Exception("Check booking status failed: " . $e->getMessage());
        }
    }
}
?>