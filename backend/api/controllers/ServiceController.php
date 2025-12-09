<?php
require_once '../config/database.php';

class ServiceController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllServices() {
        // Get all services
    }

    public function getService($id) {
        // Get service by ID
    }

    public function createService() {
        // Admin: Create new service
    }

    public function updateService($id) {
        // Admin: Update service
    }

    public function deleteService($id) {
        // Admin: Delete service
    }
}