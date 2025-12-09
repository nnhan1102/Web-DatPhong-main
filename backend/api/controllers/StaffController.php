<?php
require_once '../config/database.php';

class StaffController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllStaff() {
        // Admin: Get all staff
    }

    public function getStaff($id) {
        // Admin: Get staff by ID
    }

    public function createStaff() {
        // Admin: Create new staff
    }

    public function updateStaff($id) {
        // Admin: Update staff
    }

    public function deleteStaff($id) {
        // Admin: Delete staff
    }
}