<?php
require_once '../config/database.php';

class PromotionController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllPromotions() {
        try {
            $query = "SELECT * FROM promotions WHERE status = 'active' 
                     AND (start_date <= NOW() OR start_date IS NULL) 
                     AND (end_date >= NOW() OR end_date IS NULL)
                     ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $promotions,
                'count' => count($promotions)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching promotions: ' . $e->getMessage()
            ]);
        }
    }

    public function getPromotion($id) {
        try {
            $query = "SELECT * FROM promotions WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promotion) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $promotion
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Promotion not found'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching promotion: ' . $e->getMessage()
            ]);
        }
    }

    public function validatePromoCode() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['promo_code']) || !isset($data['total_amount'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Promo code and total amount are required'
                ]);
                return;
            }
            
            $promo_code = $data['promo_code'];
            $total_amount = $data['total_amount'];
            
            $query = "SELECT * FROM promotions 
                     WHERE promo_code = :promo_code 
                     AND status = 'active'
                     AND (start_date <= NOW() OR start_date IS NULL)
                     AND (end_date >= NOW() OR end_date IS NULL)
                     AND (min_order_amount <= :total_amount OR min_order_amount IS NULL)
                     AND (max_usage_limit > used_count OR max_usage_limit IS NULL)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':promo_code', $promo_code);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->execute();
            
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promotion) {
                // Calculate discount
                $discount = $this->calculateDiscount($promotion, $total_amount);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'valid' => true,
                    'promotion' => $promotion,
                    'discount_amount' => $discount,
                    'final_amount' => $total_amount - $discount
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'valid' => false,
                    'message' => 'Invalid or expired promo code'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error validating promo code: ' . $e->getMessage()
            ]);
        }
    }

    private function calculateDiscount($promotion, $total_amount) {
        $discount = 0;
        
        if ($promotion['discount_type'] === 'percentage') {
            $discount = ($total_amount * $promotion['discount_value']) / 100;
            
            // Check max discount limit
            if ($promotion['max_discount_amount'] && $discount > $promotion['max_discount_amount']) {
                $discount = $promotion['max_discount_amount'];
            }
        } elseif ($promotion['discount_type'] === 'fixed') {
            $discount = $promotion['discount_value'];
        }
        
        return $discount;
    }

    public function createPromotion() {
        try {
            // Check admin permission
            session_start();
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied: Admin privileges required'
                ]);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Validation
            $required_fields = ['name', 'promo_code', 'discount_type', 'discount_value'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Missing required field: ' . $field
                    ]);
                    return;
                }
            }
            
            // Generate unique promo code if not provided
            if (empty($data['promo_code'])) {
                $data['promo_code'] = $this->generatePromoCode();
            }
            
            // Check if promo code already exists
            $check_query = "SELECT id FROM promotions WHERE promo_code = :promo_code";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':promo_code', $data['promo_code']);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Promo code already exists'
                ]);
                return;
            }
            
            // Prepare SQL query
            $query = "INSERT INTO promotions (
                name, description, promo_code, discount_type, discount_value,
                max_discount_amount, min_order_amount, start_date, end_date,
                max_usage_limit, used_count, status, created_by, created_at
            ) VALUES (
                :name, :description, :promo_code, :discount_type, :discount_value,
                :max_discount_amount, :min_order_amount, :start_date, :end_date,
                :max_usage_limit, :used_count, :status, :created_by, NOW()
            )";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description'] ?? '');
            $stmt->bindParam(':promo_code', $data['promo_code']);
            $stmt->bindParam(':discount_type', $data['discount_type']);
            $stmt->bindParam(':discount_value', $data['discount_value']);
            $stmt->bindParam(':max_discount_amount', $data['max_discount_amount'] ?? null);
            $stmt->bindParam(':min_order_amount', $data['min_order_amount'] ?? null);
            $stmt->bindParam(':start_date', $data['start_date'] ?? null);
            $stmt->bindParam(':end_date', $data['end_date'] ?? null);
            $stmt->bindParam(':max_usage_limit', $data['max_usage_limit'] ?? null);
            $stmt->bindParam(':used_count', $data['used_count'] ?? 0);
            $stmt->bindParam(':status', $data['status'] ?? 'active');
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $promotion_id = $this->db->lastInsertId();
                
                // Get created promotion
                $get_query = "SELECT * FROM promotions WHERE id = :id";
                $get_stmt = $this->db->prepare($get_query);
                $get_stmt->bindParam(':id', $promotion_id, PDO::PARAM_INT);
                $get_stmt->execute();
                $promotion = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Promotion created successfully',
                    'data' => $promotion
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create promotion'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating promotion: ' . $e->getMessage()
            ]);
        }
    }

    public function updatePromotion($id) {
        try {
            // Check admin permission
            session_start();
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied: Admin privileges required'
                ]);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Check if promotion exists
            $check_query = "SELECT * FROM promotions WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if (!$check_stmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Promotion not found'
                ]);
                return;
            }
            
            // Check if new promo code already exists (if being changed)
            if (isset($data['promo_code'])) {
                $code_query = "SELECT id FROM promotions WHERE promo_code = :promo_code AND id != :id";
                $code_stmt = $this->db->prepare($code_query);
                $code_stmt->bindParam(':promo_code', $data['promo_code']);
                $code_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $code_stmt->execute();
                
                if ($code_stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Promo code already exists'
                    ]);
                    return;
                }
            }
            
            // Build update query dynamically
            $fields = [];
            $params = [':id' => $id];
            
            $allowed_fields = [
                'name', 'description', 'promo_code', 'discount_type', 'discount_value',
                'max_discount_amount', 'min_order_amount', 'start_date', 'end_date',
                'max_usage_limit', 'used_count', 'status'
            ];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            $fields[] = "updated_at = NOW()";
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data to update'
                ]);
                return;
            }
            
            $query = "UPDATE promotions SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            // Bind all parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                // Get updated promotion
                $get_query = "SELECT * FROM promotions WHERE id = :id";
                $get_stmt = $this->db->prepare($get_query);
                $get_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $get_stmt->execute();
                $promotion = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Promotion updated successfully',
                    'data' => $promotion
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update promotion'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating promotion: ' . $e->getMessage()
            ]);
        }
    }

    public function deletePromotion($id) {
        try {
            // Check admin permission
            session_start();
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied: Admin privileges required'
                ]);
                return;
            }
            
            // Check if promotion exists
            $check_query = "SELECT * FROM promotions WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            $promotion = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$promotion) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Promotion not found'
                ]);
                return;
            }
            
            // Soft delete (change status to deleted) or hard delete
            $query = "DELETE FROM promotions WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Promotion deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete promotion'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting promotion: ' . $e->getMessage()
            ]);
        }
    }

    private function generatePromoCode() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $length = 8;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }

    // Admin: Get all promotions including inactive
    public function getAllPromotionsAdmin() {
        try {
            // Check admin permission
            session_start();
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied: Admin privileges required'
                ]);
                return;
            }
            
            $query = "SELECT * FROM promotions ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $promotions,
                'count' => count($promotions)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching promotions: ' . $e->getMessage()
            ]);
        }
    }

    // Admin: Get promotion statistics
    public function getPromotionStats() {
        try {
            // Check admin permission
            session_start();
            if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied: Admin privileges required'
                ]);
                return;
            }
            
            $query = "SELECT 
                        COUNT(*) as total_promotions,
                        SUM(used_count) as total_usage,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_promotions,
                        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_promotions,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_promotions
                      FROM promotions";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching promotion stats: ' . $e->getMessage()
            ]);
        }
    }
}
?>