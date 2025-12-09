<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';

// Xử lý CORS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($requestUri, '/'));

// Loại bỏ segment đầu tiên nếu là 'api'
if (isset($uriSegments[0]) && $uriSegments[0] === 'api') {
    array_shift($uriSegments);
}

// Route chính
$route = $uriSegments[0] ?? '';
$id = $uriSegments[1] ?? null;
$action = $uriSegments[2] ?? null;

// ===== ADMIN ROUTES =====
if (strpos($route, 'admin') === 0) {
    // Kiểm tra session admin
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: Admin privileges required']);
        exit();
    }
    
    switch ($route) {
        case 'admin-dashboard':
            require_once 'controllers/DashboardController.php';
            $controller = new DashboardController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'getDashboardData':
                            $controller->getDashboardData();
                            break;
                        case 'getChartData':
                            $controller->getChartData();
                            break;
                        case 'getQuickStats':
                            $controller->getQuickStats();
                            break;
                        case 'exportData':
                            $controller->exportData();
                            break;
                        case 'refreshDashboard':
                            $controller->refreshDashboard();
                            break;
                        default:
                            $controller->getDashboardData();
                    }
                } else {
                    $controller->getDashboardData();
                }
            }
            break;
            
        case 'admin-rooms':
            require_once 'controllers/RoomController.php';
            $controller = new RoomController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getRoom($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Room ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllRooms();
                            break;
                        case 'getAvailable':
                            $controller->getAvailableRooms();
                            break;
                        default:
                            $controller->getAllRooms();
                    }
                } else {
                    $controller->getAllRooms();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createRoom();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateRoom($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Room ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createRoom();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteRoom($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Room ID required']);
                }
            }
            break;
            
        case 'admin-room-types':
            require_once 'controllers/RoomTypeController.php';
            $controller = new RoomTypeController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getRoomType($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Room Type ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllRoomTypes();
                            break;
                        default:
                            $controller->getAllRoomTypes();
                    }
                } else {
                    $controller->getAllRoomTypes();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createRoomType();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateRoomType($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Room Type ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createRoomType();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteRoomType($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Room Type ID required']);
                }
            }
            break;
            
        case 'admin-bookings':
            require_once 'controllers/BookingController.php';
            $controller = new BookingController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getBooking($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllBookings();
                            break;
                        case 'getByCode':
                            if (isset($_GET['booking_code'])) {
                                $controller->getBookingByCode($_GET['booking_code']);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Booking code required']);
                            }
                            break;
                        default:
                            $controller->getAllBookings();
                    }
                } else {
                    $controller->getAllBookings();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createBooking();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateBooking($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createBooking();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteBooking($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                }
            }
            break;
            
        case 'admin-customers':
            require_once 'controllers/CustomerController.php';
            $controller = new CustomerController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getCustomer($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllCustomers();
                            break;
                        case 'stats':
                            $controller->getCustomerStats();
                            break;
                        case 'export':
                            $controller->exportCustomers();
                            break;
                        default:
                            $controller->getAllCustomers();
                    }
                } else {
                    $controller->getAllCustomers();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createCustomer();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateCustomer($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createCustomer();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteCustomer($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                }
            }
            break;
            
        case 'admin-services':
            require_once 'controllers/ServiceController.php';
            $controller = new ServiceController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getService($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Service ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllServices();
                            break;
                        default:
                            $controller->getAllServices();
                    }
                } else {
                    $controller->getAllServices();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createService();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateService($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Service ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createService();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteService($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Service ID required']);
                }
            }
            break;
            
        case 'admin-staff':
            require_once 'controllers/StaffController.php';
            $controller = new StaffController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'get':
                            if ($id) {
                                $controller->getStaff($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Staff ID required']);
                            }
                            break;
                        case 'getAll':
                            $controller->getAllStaff();
                            break;
                        default:
                            $controller->getAllStaff();
                    }
                } else {
                    $controller->getAllStaff();
                }
            } elseif ($requestMethod === 'POST') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'create':
                            $controller->createStaff();
                            break;
                        case 'update':
                            if ($id) {
                                $controller->updateStaff($id);
                            } else {
                                http_response_code(400);
                                echo json_encode(['success' => false, 'message' => 'Staff ID required']);
                            }
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->createStaff();
                }
            } elseif ($requestMethod === 'DELETE') {
                if ($id) {
                    $controller->deleteStaff($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Staff ID required']);
                }
            }
            break;
            
        case 'admin-reports':
            require_once 'controllers/ReportController.php';
            $controller = new ReportController();
            
            if ($requestMethod === 'GET') {
                if (isset($_GET['action'])) {
                    switch ($_GET['action']) {
                        case 'getReport':
                            $controller->getReport();
                            break;
                        case 'exportReport':
                            $controller->exportReport();
                            break;
                        default:
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    }
                } else {
                    $controller->getReport();
                }
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Admin API endpoint not found']);
            break;
    }
    
// ===== PUBLIC ROUTES =====
} else {
    switch ($route) {
        case 'auth':
            require_once 'controllers/CustomerController.php';
            $controller = new CustomerController();
            
            if ($action === 'login') {
                if ($requestMethod === 'POST') {
                    $controller->login();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                }
            } elseif ($action === 'register') {
                if ($requestMethod === 'POST') {
                    $controller->createCustomer();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                }
            } elseif ($action === 'logout') {
                if ($requestMethod === 'POST') {
                    $controller->logout();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                }
            } elseif ($action === 'profile') {
                if ($requestMethod === 'GET') {
                    $controller->getProfile();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Auth endpoint not found']);
            }
            break;
            
        case 'rooms':
            require_once 'controllers/RoomController.php';
            $controller = new RoomController();
            
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getRoom($id);
                } else {
                    if (isset($_GET['action']) && $_GET['action'] === 'getAvailable') {
                        $controller->getAvailableRooms();
                    } else {
                        $controller->getAllRoomsPublic();
                    }
                }
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'room-types':
            require_once 'controllers/RoomTypeController.php';
            $controller = new RoomTypeController();
            
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getRoomType($id);
                } else {
                    $controller->getAllRoomTypes();
                }
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'bookings':
            require_once 'controllers/BookingController.php';
            $controller = new BookingController();
            
            if ($requestMethod === 'POST') {
                $controller->createBooking();
            } elseif ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getBooking($id);
                } elseif (isset($_GET['booking_code'])) {
                    $controller->getBookingByCode($_GET['booking_code']);
                } elseif (isset($_GET['action']) && $_GET['action'] === 'checkAvailability') {
                    $controller->checkRoomAvailability();
                } else {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'services':
            require_once 'controllers/ServiceController.php';
            $controller = new ServiceController();
            
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getService($id);
                } else {
                    $controller->getAllServices();
                }
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'promotions':
            require_once 'controllers/PromotionController.php';
            $controller = new PromotionController();
            
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getPromotion($id);
                } else {
                    $controller->getAllPromotions();
                }
            } elseif ($requestMethod === 'POST' && isset($_GET['action']) && $_GET['action'] === 'validate') {
                $controller->validatePromoCode();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'customers':
            require_once 'controllers/CustomerController.php';
            $controller = new CustomerController();
            
            // Chỉ cho phép GET customer của chính mình
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getCustomer($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                }
            } elseif ($requestMethod === 'PUT') {
                if ($id) {
                    $controller->updateCustomer($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'reviews':
            require_once 'controllers/ReviewController.php';
            $controller = new ReviewController();
            
            if ($requestMethod === 'GET') {
                if ($id) {
                    $controller->getReview($id);
                } else {
                    $controller->getAllReviews();
                }
            } elseif ($requestMethod === 'POST') {
                $controller->createReview();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case '':
            // Home/health check endpoint
            if ($requestMethod === 'GET') {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Hotel Opulent API',
                    'version' => '1.0.0',
                    'endpoints' => [
                        'auth' => ['login', 'register', 'logout', 'profile'],
                        'rooms' => ['getAll', 'getAvailable', 'getById'],
                        'room-types' => ['getAll', 'getById'],
                        'bookings' => ['create', 'getByCode', 'checkAvailability'],
                        'services' => ['getAll', 'getById'],
                        'promotions' => ['getAll', 'getById', 'validate'],
                        'reviews' => ['getAll', 'getById', 'create']
                    ]
                ]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
            break;
    }
}