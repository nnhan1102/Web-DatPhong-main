<?php
require_once '../config/database.php';
require_once '../models/Tour.php';

class TourController {
    private $db;
    private $tour;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->tour = new Tour($this->db);
    }

    // Lấy tất cả tours
    public function getAllTours() {
        try {
            // Lấy parameters từ query string
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
            $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
            $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : false;
            
            // Tính offset
            $offset = ($page - 1) * $limit;
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = "(t.name LIKE :search OR t.destination LIKE :search OR t.short_description LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            if (!empty($category)) {
                $conditions[] = "c.slug = :category";
                $params[':category'] = $category;
            }
            
            if (!empty($destination)) {
                $conditions[] = "t.destination LIKE :destination";
                $params[':destination'] = "%$destination%";
            }
            
            if ($minPrice !== null) {
                $conditions[] = "t.price_adult >= :min_price";
                $params[':min_price'] = $minPrice;
            }
            
            if ($maxPrice !== null) {
                $conditions[] = "t.price_adult <= :max_price";
                $params[':max_price'] = $maxPrice;
            }
            
            if (!empty($status)) {
                $conditions[] = "t.status = :status";
                $params[':status'] = $status;
            }
            
            if ($featured) {
                $conditions[] = "t.featured = 1";
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Query tổng số records
            $countQuery = "SELECT COUNT(DISTINCT t.id) as total 
                          FROM tours t
                          LEFT JOIN tour_categories tc ON t.id = tc.tour_id
                          LEFT JOIN categories c ON tc.category_id = c.id
                          $whereClause";
            
            $stmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            // Query dữ liệu tours
            $query = "SELECT t.*, 
                             GROUP_CONCAT(DISTINCT c.name) as categories,
                             GROUP_CONCAT(DISTINCT c.slug) as category_slugs,
                             AVG(r.rating) as average_rating,
                             COUNT(r.id) as review_count
                      FROM tours t
                      LEFT JOIN tour_categories tc ON t.id = tc.tour_id
                      LEFT JOIN categories c ON tc.category_id = c.id
                      LEFT JOIN reviews r ON t.id = r.tour_id
                      $whereClause
                      GROUP BY t.id
                      ORDER BY t.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($tours as &$tour) {
                $tour['gallery'] = json_decode($tour['gallery'], true) ?: [];
                $tour['itinerary'] = json_decode($tour['itinerary'], true) ?: [];
                $tour['included_services'] = json_decode($tour['included_services'], true) ?: [];
                $tour['excluded_services'] = json_decode($tour['excluded_services'], true) ?: [];
                
                // Tính giá sau giảm
                $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
                $tour['final_price_child'] = $tour['price_child'] * (1 - $tour['discount_percent'] / 100);
                $tour['final_price_infant'] = $tour['price_infant'] * (1 - $tour['discount_percent'] / 100);
                
                // Parse categories
                $tour['categories'] = $tour['categories'] ? explode(',', $tour['categories']) : [];
                $tour['category_slugs'] = $tour['category_slugs'] ? explode(',', $tour['category_slugs']) : [];
                
                // Format rating
                $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
                $tour['review_count'] = (int)$tour['review_count'];
                
                // Remove sensitive fields
                unset($tour['deleted_at']);
            }
            
            // Tính tổng số trang
            $totalPages = ceil($total / $limit);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $tours,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy tour theo ID
    public function getTour($id) {
        try {
            $tourId = (int)$id;
            
            $query = "SELECT t.*, 
                             GROUP_CONCAT(DISTINCT c.id) as category_ids,
                             GROUP_CONCAT(DISTINCT c.name) as categories,
                             GROUP_CONCAT(DISTINCT c.slug) as category_slugs,
                             AVG(r.rating) as average_rating,
                             COUNT(r.id) as review_count
                      FROM tours t
                      LEFT JOIN tour_categories tc ON t.id = tc.tour_id
                      LEFT JOIN categories c ON tc.category_id = c.id
                      LEFT JOIN reviews r ON t.id = r.tour_id
                      WHERE t.id = :id
                      GROUP BY t.id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $tour = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Format dữ liệu
                $tour['gallery'] = json_decode($tour['gallery'], true) ?: [];
                $tour['itinerary'] = json_decode($tour['itinerary'], true) ?: [];
                $tour['included_services'] = json_decode($tour['included_services'], true) ?: [];
                $tour['excluded_services'] = json_decode($tour['excluded_services'], true) ?: [];
                
                // Tính giá sau giảm
                $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
                $tour['final_price_child'] = $tour['price_child'] * (1 - $tour['discount_percent'] / 100);
                $tour['final_price_infant'] = $tour['price_infant'] * (1 - $tour['discount_percent'] / 100);
                
                // Parse categories
                $tour['category_ids'] = $tour['category_ids'] ? array_map('intval', explode(',', $tour['category_ids'])) : [];
                $tour['categories'] = $tour['categories'] ? explode(',', $tour['categories']) : [];
                $tour['category_slugs'] = $tour['category_slugs'] ? explode(',', $tour['category_slugs']) : [];
                
                // Format rating
                $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
                $tour['review_count'] = (int)$tour['review_count'];
                
                // Lấy reviews
                $reviews = $this->getTourReviews($tourId);
                $tour['reviews'] = $reviews;
                
                // Lấy related tours
                $relatedTours = $this->getRelatedTours($tourId, $tour['category_ids']);
                $tour['related_tours'] = $relatedTours;
                
                // Remove sensitive fields
                unset($tour['deleted_at']);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $tour
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Tạo tour mới
    public function createTour() {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['name', 'destination', 'price_adult', 'available_slots'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ]);
                    return;
                }
            }
            
            // Prepare tour data
            $tourData = [
                'name' => trim($data['name']),
                'slug' => $this->generateSlug($data['name']),
                'description' => $data['description'] ?? '',
                'short_description' => $data['short_description'] ?? substr($data['description'] ?? '', 0, 200),
                'duration_days' => (int)($data['duration_days'] ?? 1),
                'duration_nights' => (int)($data['duration_nights'] ?? 0),
                'departure_location' => $data['departure_location'] ?? '',
                'destination' => trim($data['destination']),
                'price_adult' => (float)$data['price_adult'],
                'price_child' => (float)($data['price_child'] ?? 0),
                'price_infant' => (float)($data['price_infant'] ?? 0),
                'discount_percent' => (int)($data['discount_percent'] ?? 0),
                'available_slots' => (int)$data['available_slots'],
                'featured_image' => $data['featured_image'] ?? '',
                'gallery' => json_encode($data['gallery'] ?? []),
                'itinerary' => json_encode($data['itinerary'] ?? []),
                'included_services' => json_encode($data['included_services'] ?? []),
                'excluded_services' => json_encode($data['excluded_services'] ?? []),
                'status' => $data['status'] ?? 'active',
                'featured' => (bool)($data['featured'] ?? false),
                'meta_title' => $data['meta_title'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'meta_keywords' => $data['meta_keywords'] ?? ''
            ];
            
            // Insert vào database
            $columns = implode(', ', array_keys($tourData));
            $placeholders = ':' . implode(', :', array_keys($tourData));
            
            $query = "INSERT INTO tours ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($query);
            
            foreach ($tourData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            if ($stmt->execute()) {
                $tourId = $this->db->lastInsertId();
                
                // Thêm categories nếu có
                if (!empty($data['categories'])) {
                    $this->addTourCategories($tourId, $data['categories']);
                }
                
                // Lấy tour vừa tạo
                $createdTour = $this->getTourById($tourId);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Tour created successfully',
                    'data' => $createdTour
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create tour'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Cập nhật tour
    public function updateTour($id) {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            $tourId = (int)$id;
            
            // Kiểm tra tour tồn tại
            if (!$this->tourExists($tourId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
                return;
            }
            
            // Lấy dữ liệu từ request
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Prepare update data
            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = trim($data['name']);
                $updateData['slug'] = $this->generateSlug($data['name']);
            }
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['short_description'])) $updateData['short_description'] = $data['short_description'];
            if (isset($data['duration_days'])) $updateData['duration_days'] = (int)$data['duration_days'];
            if (isset($data['duration_nights'])) $updateData['duration_nights'] = (int)$data['duration_nights'];
            if (isset($data['departure_location'])) $updateData['departure_location'] = $data['departure_location'];
            if (isset($data['destination'])) $updateData['destination'] = trim($data['destination']);
            if (isset($data['price_adult'])) $updateData['price_adult'] = (float)$data['price_adult'];
            if (isset($data['price_child'])) $updateData['price_child'] = (float)$data['price_child'];
            if (isset($data['price_infant'])) $updateData['price_infant'] = (float)$data['price_infant'];
            if (isset($data['discount_percent'])) $updateData['discount_percent'] = (int)$data['discount_percent'];
            if (isset($data['available_slots'])) $updateData['available_slots'] = (int)$data['available_slots'];
            if (isset($data['featured_image'])) $updateData['featured_image'] = $data['featured_image'];
            if (isset($data['gallery'])) $updateData['gallery'] = json_encode($data['gallery']);
            if (isset($data['itinerary'])) $updateData['itinerary'] = json_encode($data['itinerary']);
            if (isset($data['included_services'])) $updateData['included_services'] = json_encode($data['included_services']);
            if (isset($data['excluded_services'])) $updateData['excluded_services'] = json_encode($data['excluded_services']);
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['featured'])) $updateData['featured'] = (bool)$data['featured'];
            if (isset($data['meta_title'])) $updateData['meta_title'] = $data['meta_title'];
            if (isset($data['meta_description'])) $updateData['meta_description'] = $data['meta_description'];
            if (isset($data['meta_keywords'])) $updateData['meta_keywords'] = $data['meta_keywords'];
            
            // Cập nhật updated_at
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data to update'
                ]);
                return;
            }
            
            // Build update query
            $setClause = [];
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            
            $query = "UPDATE tours SET " . implode(', ', $setClause) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            foreach ($updateData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $tourId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Cập nhật categories nếu có
                if (isset($data['categories'])) {
                    $this->updateTourCategories($tourId, $data['categories']);
                }
                
                // Lấy tour đã cập nhật
                $updatedTour = $this->getTourById($tourId);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Tour updated successfully',
                    'data' => $updatedTour
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update tour'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Xóa tour
    public function deleteTour($id) {
        try {
            // Kiểm tra quyền admin
            $this->checkAdminAuth();
            
            $tourId = (int)$id;
            
            // Kiểm tra tour tồn tại
            if (!$this->tourExists($tourId)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tour not found'
                ]);
                return;
            }
            
            // Kiểm tra xem tour có booking nào không
            $bookingCount = $this->getTourBookingCount($tourId);
            if ($bookingCount > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete tour with existing bookings'
                ]);
                return;
            }
            
            // Soft delete (cập nhật deleted_at)
            $query = "UPDATE tours SET deleted_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Tour deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete tour'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy featured tours
    public function getFeaturedTours() {
        try {
            $query = "SELECT t.*, 
                             AVG(r.rating) as average_rating,
                             COUNT(r.id) as review_count
                      FROM tours t
                      LEFT JOIN reviews r ON t.id = r.tour_id
                      WHERE t.featured = 1 AND t.status = 'active' AND t.deleted_at IS NULL
                      GROUP BY t.id
                      ORDER BY t.created_at DESC
                      LIMIT 6";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($tours as &$tour) {
                $tour['gallery'] = json_decode($tour['gallery'], true) ?: [];
                $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
                $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
                $tour['review_count'] = (int)$tour['review_count'];
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $tours
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // Lấy popular tours (nhiều booking nhất)
    public function getPopularTours() {
        try {
            $query = "SELECT t.*, 
                             COUNT(b.id) as booking_count,
                             AVG(r.rating) as average_rating
                      FROM tours t
                      LEFT JOIN bookings b ON t.id = b.tour_id AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      LEFT JOIN reviews r ON t.id = r.tour_id
                      WHERE t.status = 'active' AND t.deleted_at IS NULL
                      GROUP BY t.id
                      ORDER BY booking_count DESC, average_rating DESC
                      LIMIT 8";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($tours as &$tour) {
                $tour['gallery'] = json_decode($tour['gallery'], true) ?: [];
                $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
                $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
                $tour['booking_count'] = (int)$tour['booking_count'];
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $tours
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    // ===== HELPER METHODS =====

    private function checkAdminAuth() {
        // Kiểm tra admin authentication
        // Trong thực tế, bạn sẽ kiểm tra JWT token hoặc session
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: No token provided'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Validate token (đơn giản hóa - trong thực tế cần validate JWT)
        if ($token !== 'admin-secret-token') { // Thay bằng logic validate thật
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden: Invalid token'
            ]);
            exit;
        }
    }

    private function generateSlug($string) {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        $slug = strtolower(trim($slug, '-'));
        
        // Thêm timestamp để đảm bảo unique
        $timestamp = time();
        return $slug . '-' . $timestamp;
    }

    private function addTourCategories($tourId, $categories) {
        // Xóa categories cũ
        $deleteQuery = "DELETE FROM tour_categories WHERE tour_id = :tour_id";
        $stmt = $this->db->prepare($deleteQuery);
        $stmt->bindParam(':tour_id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Thêm categories mới
        if (is_array($categories) && !empty($categories)) {
            $insertQuery = "INSERT INTO tour_categories (tour_id, category_id) VALUES ";
            $values = [];
            $params = [];
            
            foreach ($categories as $index => $categoryId) {
                $values[] = "(:tour_id, :category_id_$index)";
                $params[":category_id_$index"] = (int)$categoryId;
            }
            
            $insertQuery .= implode(', ', $values);
            $stmt = $this->db->prepare($insertQuery);
            $stmt->bindParam(':tour_id', $tourId, PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            
            $stmt->execute();
        }
    }

    private function updateTourCategories($tourId, $categories) {
        $this->addTourCategories($tourId, $categories);
    }

    private function getTourById($tourId) {
        $query = "SELECT t.*, 
                         GROUP_CONCAT(DISTINCT c.id) as category_ids,
                         GROUP_CONCAT(DISTINCT c.name) as categories,
                         AVG(r.rating) as average_rating,
                         COUNT(r.id) as review_count
                  FROM tours t
                  LEFT JOIN tour_categories tc ON t.id = tc.tour_id
                  LEFT JOIN categories c ON tc.category_id = c.id
                  LEFT JOIN reviews r ON t.id = r.tour_id
                  WHERE t.id = :id
                  GROUP BY t.id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $tour = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            $tour['gallery'] = json_decode($tour['gallery'], true) ?: [];
            $tour['itinerary'] = json_decode($tour['itinerary'], true) ?: [];
            $tour['included_services'] = json_decode($tour['included_services'], true) ?: [];
            $tour['excluded_services'] = json_decode($tour['excluded_services'], true) ?: [];
            
            $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
            $tour['category_ids'] = $tour['category_ids'] ? array_map('intval', explode(',', $tour['category_ids'])) : [];
            $tour['categories'] = $tour['categories'] ? explode(',', $tour['categories']) : [];
            $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
            $tour['review_count'] = (int)$tour['review_count'];
            
            return $tour;
        }
        
        return null;
    }

    private function tourExists($tourId) {
        $query = "SELECT id FROM tours WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function getTourBookingCount($tourId) {
        $query = "SELECT COUNT(*) as count FROM bookings WHERE tour_id = :tour_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':tour_id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    private function getTourReviews($tourId) {
        $query = "SELECT r.*, u.full_name, u.avatar
                  FROM reviews r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.tour_id = :tour_id AND r.approved = 1
                  ORDER BY r.created_at DESC
                  LIMIT 10";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':tour_id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRelatedTours($tourId, $categoryIds) {
        if (empty($categoryIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        
        $query = "SELECT DISTINCT t.*, 
                         AVG(r.rating) as average_rating
                  FROM tours t
                  LEFT JOIN tour_categories tc ON t.id = tc.tour_id
                  LEFT JOIN reviews r ON t.id = r.tour_id
                  WHERE t.id != ? 
                    AND tc.category_id IN ($placeholders)
                    AND t.status = 'active'
                    AND t.deleted_at IS NULL
                  GROUP BY t.id
                  ORDER BY RAND()
                  LIMIT 4";
        
        $params = array_merge([$tourId], $categoryIds);
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tours as &$tour) {
            $tour['final_price'] = $tour['price_adult'] * (1 - $tour['discount_percent'] / 100);
            $tour['average_rating'] = $tour['average_rating'] ? round($tour['average_rating'], 1) : 0;
        }
        
        return $tours;
    }
}