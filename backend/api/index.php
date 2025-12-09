<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'routes/api.php';

// Xử lý CORS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($requestUri, '/'));

// Loại bỏ 'api' từ segment đầu tiên
if ($uriSegments[0] === 'api') {
    array_shift($uriSegments);
}

// Định tuyến API
$route = $uriSegments[0] ?? '';

switch ($route) {
    case 'tours':
        require_once 'controllers/TourController.php';
        $controller = new TourController();
        
        $id = $uriSegments[1] ?? null;
        
        if ($requestMethod === 'GET') {
            if ($id) {
                $controller->getTour($id);
            } else {
                $controller->getAllTours();
            }
        } elseif ($requestMethod === 'POST') {
            $controller->createTour();
        } elseif ($requestMethod === 'PUT') {
            if ($id) {
                $controller->updateTour($id);
            }
        } elseif ($requestMethod === 'DELETE') {
            if ($id) {
                $controller->deleteTour($id);
            }
        }
        break;
        
    case 'bookings':
        require_once 'controllers/BookingController.php';
        $controller = new BookingController();
        
        $id = $uriSegments[1] ?? null;
        
        if ($requestMethod === 'GET') {
            if ($id) {
                $controller->getBooking($id);
            } else {
                $controller->getAllBookings();
            }
        } elseif ($requestMethod === 'POST') {
            $controller->createBooking();
        } elseif ($requestMethod === 'PUT') {
            if ($id) {
                $controller->updateBooking($id);
            }
        }
        break;
        
    case 'customers':
        require_once 'controllers/CustomerController.php';
        $controller = new CustomerController();
        
        $id = $uriSegments[1] ?? null;
        
        if ($requestMethod === 'GET') {
            if ($id) {
                $controller->getCustomer($id);
            } else {
                $controller->getAllCustomers();
            }
        } elseif ($requestMethod === 'POST') {
            $controller->createCustomer();
        } elseif ($requestMethod === 'PUT') {
            if ($id) {
                $controller->updateCustomer($id);
            }
        }
        break;
        
    case 'dashboard':
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        
        if ($requestMethod === 'GET') {
            $controller->getDashboardData();
        }
        break;
    case 'admin-dashboard':
    require_once 'controllers/AdminDashboardController.php';
    $controller = new AdminDashboardController();
    
    if ($requestMethod === 'GET') {
        $controller->getDashboardData();
    }
    break;
    
case 'admin-tours':
    require_once 'controllers/AdminTourController.php';
    $controller = new AdminTourController();
    
    $id = $uriSegments[1] ?? null;
    
    if ($requestMethod === 'GET') {
        if ($id) {
            $controller->getTour($id);
        } else {
            $controller->getAllTours();
        }
    } elseif ($requestMethod === 'POST') {
        $controller->createTour();
    } elseif ($requestMethod === 'PUT') {
        if ($id) {
            $controller->updateTour($id);
        }
    } elseif ($requestMethod === 'DELETE') {
        if ($id) {
            $controller->deleteTour($id);
        }
    }
    break;
    
case 'admin-bookings':
    require_once 'controllers/AdminBookingController.php';
    $controller = new AdminBookingController();
    
    $id = $uriSegments[1] ?? null;
    
    if ($requestMethod === 'GET') {
        if ($id) {
            $controller->getBooking($id);
        } else {
            $controller->getAllBookings();
        }
    } elseif ($requestMethod === 'DELETE') {
        if ($id) {
            $controller->deleteBooking($id);
        }
    }
    break;
    
case 'admin-customers':
    require_once 'controllers/AdminCustomerController.php';
    $controller = new AdminCustomerController();
    
    $id = $uriSegments[1] ?? null;
    
    if ($requestMethod === 'GET') {
        if ($id) {
            $controller->getCustomer($id);
        } else {
            $controller->getAllCustomers();
        }
    } elseif ($requestMethod === 'DELETE') {
        if ($id) {
            $controller->deleteCustomer($id);
        }
    }
    break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'API endpoint not found']);
        break;
}