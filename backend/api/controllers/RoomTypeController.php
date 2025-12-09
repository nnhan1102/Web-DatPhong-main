<?php
require_once '../config/database.php';

class RoomTypeController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllRoomTypes() {
        // Get all room types
    }

    public function getRoomType($id) {
        // Get room type by ID
    }

    public function createRoomType() {
        // Admin: Create new room type
    }

    public function updateRoomType($id) {
        // Admin: Update room type
    }

    public function deleteRoomType($id) {
        // Admin: Delete room type
    }
}