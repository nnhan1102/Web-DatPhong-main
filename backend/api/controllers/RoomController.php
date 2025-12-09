<?php
require_once '../config/database.php';

class RoomController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllRooms() {
        // Admin: Get all rooms with filters
    }

    public function getAllRoomsPublic() {
        // Public: Get only available rooms
    }

    public function getAvailableRooms() {
        // Get available rooms for specific dates
    }

    public function getRoom($id) {
        // Get room by ID
    }

    public function createRoom() {
        // Admin: Create new room
    }

    public function updateRoom($id) {
        // Admin: Update room
    }

    public function deleteRoom($id) {
        // Admin: Delete room
    }
}