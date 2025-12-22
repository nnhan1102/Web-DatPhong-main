-- Seed sample data for hotel_opulent
-- Usage: mysql -u root -p hotel_opulent < database/sample_seed.sql

SET NAMES utf8mb4;

-- Room types
INSERT INTO room_types (type_name, description, base_price, capacity, amenities) VALUES
('Deluxe Room', 'Phòng tiện nghi tiêu chuẩn', 1500000, 2, '["WiFi","TV","Minibar","Air Conditioning","Safe"]'),
('Superior Room', 'Phòng tiện nghi nâng cao', 1800000, 2, '["WiFi","TV","Air Conditioning","Balcony"]'),
('Family Suite', 'Phòng gia đình rộng rãi', 2500000, 4, '["WiFi","TV","Minibar","Kitchenette","Sofa bed"]'),
('Executive Suite', 'Phòng cao cấp cho doanh nhân', 3500000, 2, '["WiFi","Smart TV","Minibar","Jacuzzi","Balcony"]');

-- Rooms
INSERT INTO rooms (room_number, room_type_id, floor, view_type, status, image_url) VALUES
('101', 1, 1, 'city', 'available', NULL),
('102', 1, 1, 'garden', 'occupied', NULL),
('201', 2, 2, 'city', 'available', NULL),
('301', 3, 3, 'sea', 'available', NULL),
('401', 4, 4, 'sea', 'occupied', NULL);

-- Customers (users)
INSERT INTO users (username, email, password, full_name, phone, address, user_type, status) VALUES
('customer1', 'customer1@example.com', PASSWORD('123456'), 'Nguyen Van A', '0900000001', 'HN', 'customer', 'active'),
('customer2', 'customer2@example.com', PASSWORD('123456'), 'Tran Thi B', '0900000002', 'HCM', 'customer', 'active');

-- Staff
INSERT INTO staff (staff_code, full_name, email, phone, position, department, hire_date, status) VALUES
('NV001', 'Le Van Nhan', 'nhan@example.com', '090900900', 'Lễ tân', 'reception', CURDATE(), 'active'),
('NV002', 'Pham Thi Mai', 'mai@example.com', '0908111222', 'Buồng phòng', 'housekeeping', CURDATE(), 'active');

-- Services
INSERT INTO services (service_name, description, price, category, status) VALUES
('Đưa đón sân bay', 'Xe 4 chỗ đón/tiễn', 500000, 'transport', 'available'),
('Buffet sáng', 'Buffet tại nhà hàng', 150000, 'food', 'available'),
('Massage 60 phút', 'Massage thư giãn', 400000, 'spa', 'available');

-- Bookings (dates within current month)
INSERT INTO bookings (booking_code, customer_id, room_id, check_in, check_out, num_guests, total_price, status, special_requests, payment_method, payment_status, created_at) VALUES
('BK20251210A', 1, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 3000000, 'checked_in', 'Phòng yên tĩnh', 'cash', 'paid', NOW()),
('BK20251205B', 2, 4, DATE_ADD(CURDATE(), INTERVAL -5 DAY), DATE_ADD(CURDATE(), INTERVAL -3 DAY), 3, 5000000, 'checked_out', 'Thêm gối', 'credit_card', 'paid', DATE_ADD(NOW(), INTERVAL -5 DAY));

-- Booking services (optional)
INSERT INTO booking_services (booking_id, service_id, quantity, price, service_date) VALUES
(1, 1, 1, 500000, CURDATE()),
(1, 2, 2, 150000, CURDATE()),
(2, 3, 1, 400000, DATE_ADD(CURDATE(), INTERVAL -4 DAY));

