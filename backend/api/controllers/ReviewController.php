<?php
require_once '../config/database.php';

class ReviewController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllReviews() {
        // Get all reviews (with filters)
    }

    public function getReview($id) {
        // Get review by ID
    }

    public function createReview() {
        // Customer: Create new review
    }

    public function updateReviewStatus($id) {
        // Admin: Update review status
    }

    public function deleteReview($id) {
        // Admin: Delete review
    }
}