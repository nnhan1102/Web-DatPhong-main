<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';
require_once 'models/Service.php';
use Models\Service;

$db = (new Database())->getConnection();
$model = new Service($db);

$action = $_GET['action'] ?? 'getAll';
$id = $_GET['id'] ?? null;

function jsonInput() {
    $raw = file_get_contents("php://input");
    return $raw ? json_decode($raw, true) : [];
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'getAll') {
                $filters = [
                    'category' => $_GET['category'] ?? null,
                    'status' => $_GET['status'] ?? null,
                    'search' => $_GET['search'] ?? null,
                ];
                $result = $model->getAll(1, 200, $filters);
                echo json_encode(['success' => true, 'data' => $result['data']]);
                break;
            }
            if ($action === 'get' && $id) {
                $record = $model->getById($id);
                if ($record) {
                    echo json_encode(['success' => true, 'data' => $record]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Not found']);
                }
                break;
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;

        case 'POST':
            $data = jsonInput();
            if ($action === 'create') {
                if ($model->create($data)) {
                    echo json_encode(['success' => true, 'id' => $model->id]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Create failed']);
                }
                break;
            }
            if ($action === 'update' && $id) {
                if ($model->update($id, $data)) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Update failed']);
                }
                break;
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;

        case 'DELETE':
            if ($id) {
                if ($model->delete($id)) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Delete failed']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID required']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

