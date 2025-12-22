<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
//toi thêm khúc này ===
// Thêm các header CORS chi tiết hơn
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials: true");

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// ==========

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';
require_once 'models/Room.php';
use Models\Room;

$database = new Database();
$db = $database->getConnection();
$roomModel = new Room($db);

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
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
                $filters = [
                    'status' => $_GET['status'] ?? null,
                    'room_type_id' => $_GET['room_type_id'] ?? null,
                    'floor' => $_GET['floor'] ?? null,
                    'view_type' => $_GET['view_type'] ?? null,
                    'search' => $_GET['search'] ?? null,
                ];
                $result = $roomModel->getAll($page, $limit, $filters);
                echo json_encode(['success' => true, 'data' => $result['data']]);
                break;
            }

            if ($action === 'getAvailable') {
                $filters = ['status' => 'available'];
                $result = $roomModel->getAll(1, 200, $filters);
                echo json_encode(['success' => true, 'data' => $result['data']]);
                break;
            }

            if ($action === 'get' && $id) {
                $room = $roomModel->getById($id);
                if ($room) {
                    echo json_encode(['success' => true, 'data' => $room]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Room not found']);
                }
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;

        case 'POST':
            $data = jsonInput();
            if ($action === 'create') {
                try {
                    if ($roomModel->create($data)) {
                        echo json_encode(['success' => true, 'id' => $roomModel->id]);
                    } else {
                        throw new Exception("Thêm phòng thất bại");
                    }
                } catch (Exception $e) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
            }

            if ($action === 'update' && $id) {
                try {
                    if ($roomModel->update($id, $data)) {
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception("Cập nhật thất bại");
                    }
                } catch (Exception $e) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;

        case 'DELETE':
            if ($id) {
                if ($roomModel->delete($id)) {
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

