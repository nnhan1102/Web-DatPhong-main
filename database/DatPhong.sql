-- Tạo database
CREATE DATABASE IF NOT EXISTS hotel_opulent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_opulent;

-- Bảng người dùng (cho admin và khách hàng đăng ký)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng loại phòng
CREATE TABLE room_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    amenities TEXT, -- JSON format
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng phòng
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    room_type_id INT NOT NULL,
    floor INT,
    view_type ENUM('city', 'sea', 'garden', 'pool') DEFAULT 'city',
    status ENUM('available', 'occupied', 'maintenance', 'cleaning') DEFAULT 'available',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
);

-- Bảng đặt phòng
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    num_guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    payment_method ENUM('cash', 'credit_card', 'momo', 'vnpay', 'zalopay') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Bảng dịch vụ bổ sung
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('transport', 'food', 'spa', 'other') DEFAULT 'other',
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng đặt dịch vụ
CREATE TABLE booking_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    service_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Bảng đánh giá
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    room_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    images TEXT, -- JSON array of image URLs
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Bảng khuyến mãi
CREATE TABLE promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    promo_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_amount DECIMAL(10,2),
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    usage_limit INT,
    used_count INT DEFAULT 0,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng nhân viên (chi tiết)
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    staff_code VARCHAR(20) UNIQUE NOT NULL,
    position VARCHAR(50) NOT NULL,
    department ENUM('reception', 'housekeeping', 'management', 'support') DEFAULT 'reception',
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    emergency_contact VARCHAR(20),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dữ liệu mẫu
INSERT INTO users (username, email, password, full_name, phone, user_type) VALUES
('admin', 'admin@opulent.com', '$2y$10$YourHashedPasswordHere', 'Admin System', '0123456789', 'admin'),
('reception1', 'reception@opulent.com', '$2y$10$YourHashedPasswordHere', 'Nguyễn Văn A', '0987654321', 'staff'),
('customer1', 'customer@example.com', '$2y$10$YourHashedPasswordHere', 'Trần Thị B', '0912345678', 'customer');

INSERT INTO room_types (type_name, description, base_price, capacity, amenities) VALUES
('Deluxe Room', 'Phòng sang trọng với view thành phố, trang bị đầy đủ tiện nghi', 150.00, 2, '["WiFi", "TV", "Minibar", "Air Conditioning", "Safe"]'),
('Superior Room', 'Phòng tiêu chuẩn chất lượng cao', 120.00, 2, '["WiFi", "TV", "Air Conditioning"]'),
('Family Suite', 'Phòng gia đình rộng rãi, phù hợp 4 người', 250.00, 4, '["WiFi", "TV", "Minibar", "Kitchenette", "Sofa"]'),
('Executive Suite', 'Phòng hạng sang với không gian riêng biệt', 350.00, 2, '["WiFi", "Smart TV", "Minibar", "Jacuzzi", "Balcony"]');

INSERT INTO rooms (room_number, room_type_id, floor, view_type, status) VALUES
('101', 1, 1, 'city', 'available'),
('102', 1, 1, 'garden', 'available'),
('201', 2, 2, 'city', 'available'),
('301', 3, 3, 'sea', 'available'),
('401', 4, 4, 'sea', 'available');

INSERT INTO services (service_name, description, price, category) VALUES
('Đưa đón sân bay', 'Dịch vụ đưa đón từ sân bay đến khách sạn', 30.00, 'transport'),
('Buffet sáng', 'Buffet ăn sáng đa dạng', 15.00, 'food'),
('Massage thư giãn', 'Massage toàn thân 60 phút', 50.00, 'spa'),
('Thuê xe máy', 'Thuê xe máy theo ngày', 10.00, 'transport');

INSERT INTO promotions (promo_code, description, discount_type, discount_value, valid_from, valid_to, usage_limit) VALUES
('WELCOME10', 'Giảm 10% cho lần đặt đầu tiên', 'percentage', 10.00, '2025-01-01', '2025-12-31', 1000),
('SUMMER20', 'Giảm 20% mùa hè', 'percentage', 20.00, '2025-06-01', '2025-08-31', 500);

-- Thêm dữ liệu mẫu cho bookings
INSERT INTO bookings (booking_code, customer_id, room_id, check_in, check_out, num_guests, total_price, status, special_requests, payment_method, payment_status, created_at) VALUES
('BK20250001', 3, 1, '2025-12-20', '2025-12-22', 2, 300.00, 'confirmed', 'Yêu cầu giường đôi', 'cash', 'paid', '2025-12-15 10:30:00'),
('BK20250002', 3, 2, '2025-12-18', '2025-12-19', 2, 150.00, 'checked_in', 'Check-in sớm', 'credit_card', 'paid', '2025-12-16 14:20:00'),
('BK20250003', 3, 3, '2025-12-25', '2025-12-27', 2, 240.00, 'pending', 'Phòng không hút thuốc', 'momo', 'pending', '2025-12-17 09:15:00'),
('BK20250004', 3, 4, '2025-12-22', '2025-12-24', 4, 500.00, 'confirmed', 'Thêm giường phụ', 'vnpay', 'paid', '2025-12-18 11:45:00'),
('BK20250005', 3, 5, '2026-01-01', '2026-01-03', 2, 700.00, 'checked_out', 'Yêu cầu hoa quả', 'cash', 'refunded', '2025-12-19 16:30:00'),
('BK20250006', 3, 1, '2025-12-28', '2025-12-30', 2, 300.00, 'cancelled', '', 'zalopay', 'failed', '2025-12-20 13:10:00');

-- Thêm dữ liệu booking_services
INSERT INTO booking_services (booking_id, service_id, quantity, price, service_date) VALUES
(1, 1, 1, 30.00, '2025-12-20'),
(1, 2, 2, 30.00, '2025-12-21'),
(2, 3, 1, 50.00, '2025-12-18'),
(4, 4, 2, 20.00, '2025-12-23');

-- Update một số phòng thành occupied
UPDATE rooms SET status = 'occupied' WHERE id IN (1, 2);
UPDATE rooms SET status = 'cleaning' WHERE id = 5;