<?php
require_once '../config/database.php';

class PromotionController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllPromotions() {
        // Get all active promotions
    }

    public function getPromotion($id) {
        // Get promotion by ID
    }

    public function validatePromoCode() {
        // Validate promo code
    }

    public function createPromotion() {
        // Admin: Create new promotion
    }

    public function updatePromotion($id) {
        // Admin: Update promotion
    }

    public function deletePromotion($id) {
        // Admin: Delete promotion
    }
}