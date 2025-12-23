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
require_once 'models/Staff.php';
require_once 'models/User.php';
use Models\Staff;
use Models\User;

$db = (new Database())->getConnection();
$model = new Staff($db);
$userModel = new User($db);

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
                    'department' => $_GET['department'] ?? null,
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
                $db->beginTransaction();
                try {
                    $userId = null;
                    
                    // Check if user exists by email
                    if (!empty($data['email'])) {
                        $existingUser = $userModel->getByEmail($data['email']);
                        if ($existingUser) {
                            $userId = $existingUser['id'];
                        } else {
                            // Create new user
                            $userData = [
                                'full_name' => $data['full_name'],
                                'email' => $data['email'],
                                'phone' => $data['phone'] ?? null,
                                'address' => $data['address'] ?? null,
                                'password' => '123456', // Default password
                                'user_type' => 'staff',
                                'status' => 'active'
                            ];
                            if ($userModel->register($userData)) {
                                $userId = $userModel->id;
                            } else {
                                throw new Exception("Failed to create user account");
                            }
                        }
                    } else {
                        throw new Exception("Email is required");
                    }
                    
                    $data['user_id'] = $userId;
                    // Ensure optional fields are present for Staff model
                    $data['emergency_contact'] = $data['emergency_contact'] ?? null;
                    $data['notes'] = $data['notes'] ?? null;
                    $data['salary'] = $data['salary'] ?? 0;
                    
                    if ($model->create($data)) {
                        $db->commit();
                        echo json_encode(['success' => true, 'id' => $model->id]);
                    } else {
                        throw new Exception("Create staff failed");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;
            }
            if ($action === 'update' && $id) {
                $db->beginTransaction();
                try {
                    // Get current staff to find user_id
                    $currentStaff = $model->getById($id);
                    if (!$currentStaff) {
                        throw new Exception("Staff not found");
                    }
                    
                    // Update user info
                    if (!empty($currentStaff['user_id'])) {
                        $userUpdateData = [];
                        if (isset($data['full_name'])) $userUpdateData['full_name'] = $data['full_name'];
                        if (isset($data['email'])) $userUpdateData['email'] = $data['email'];
                        if (isset($data['phone'])) $userUpdateData['phone'] = $data['phone'];
                        if (isset($data['address'])) $userUpdateData['address'] = $data['address'];
                        
                        if (!empty($userUpdateData)) {
                             $userModel->update($currentStaff['user_id'], $userUpdateData);
                        }
                    }
                    
                    // Update staff info
                    if ($model->update($id, $data)) {
                        $db->commit();
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception("Update staff failed");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
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

