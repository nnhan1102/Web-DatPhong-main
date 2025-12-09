<?php
require_once '../config/database.php';

class ReportController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getReport() {
        // Get report data with filters
    }

    public function exportReport() {
        // Export report to CSV/Excel
    }
}