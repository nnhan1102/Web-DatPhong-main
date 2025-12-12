USE hotel_opulent;
-- Thêm bảng hotels nếu chưa có
CREATE TABLE IF NOT EXISTS hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0,
    stars INT DEFAULT 3,
    amenities TEXT, -- JSON format
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '12:00:00',
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_stars (stars)
);

-- Thêm dữ liệu mẫu cho hotels
INSERT INTO hotels (name, description, address, city, rating, stars, amenities, contact_phone) VALUES
('Khách Sạn Sheraton Hà Nội', 'Khách sạn 5 sao với view hồ Tây tuyệt đẹp', '11 Xuan Dieu, Quận Tây Hồ', 'Ha Noi', 4.8, 5, '["wifi","parking","pool","spa","gym","breakfast","restaurant","bar","concierge"]', '024 3719 9000'),
('Vinpearl Resort Nha Trang', 'Resort sang trọng với bãi biển riêng', 'Đảo Hòn Tre, Vĩnh Nguyên', 'Nha Trang', 4.9, 5, '["wifi","parking","pool","spa","gym","breakfast","beach","kids_club","watersports"]', '0258 359 9999'),
('Melia Danang Resort', 'Resort 5 sao với hồ bơi vô cực', 'Trường Sa, Hòa Hải, Ngũ Hành Sơn', 'Da Nang', 4.7, 5, '["wifi","parking","pool","spa","gym","breakfast","beach_access","tennis","bicycle"]', '0236 392 8888'),
('Liberty Central Saigon', 'Khách sạn 4 sao trung tâm thành phố', '59-61 Pasteur, Bến Nghé, Quận 1', 'Ho Chi Minh', 4.5, 4, '["wifi","parking","gym","breakfast","restaurant","business_center"]', '028 3822 2222'),
('La Siesta Classic Ma May', 'Boutique hotel với kiến trúc Pháp cổ', '94 Mã Mây, Hoàn Kiếm', 'Ha Noi', 4.6, 4, '["wifi","breakfast","restaurant","bicycle","tour_desk"]', '024 3926 3641'),
('Phú Quốc Eden Resort', 'Resort bãi biển với villa riêng tư', 'Bãi Trường, Gành Dầu', 'Phu Quoc', 4.8, 5, '["wifi","parking","pool","spa","gym","breakfast","private_beach","villa","bbq"]', '0297 399 8999'),
('InterContinental Danang Sun Peninsula Resort', 'Resort 5 sao siêu sang', 'Bai Bac, Sơn Trà', 'Da Nang', 4.9, 5, '["wifi","parking","pool","spa","gym","breakfast","private_beach","butler","helicopter"]', '0236 393 8888'),
('Rex Hotel Saigon', 'Khách sạn lịch sử 5 sao', '141 Nguyễn Huệ, Quận 1', 'Ho Chi Minh', 4.4, 5, '["wifi","parking","pool","gym","breakfast","historical","rooftop_bar"]', '028 3829 2185');

-- Thêm bảng hotel_rooms (liên kết hotels và rooms)
CREATE TABLE IF NOT EXISTS hotel_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hotel_room (hotel_id, room_id)
);

-- Thêm dữ liệu liên kết hotel_rooms
INSERT INTO hotel_rooms (hotel_id, room_id, price_per_night) VALUES
(1, 1, 3200000),
(1, 2, 3500000),
(2, 3, 4500000),
(3, 4, 3800000),
(4, 5, 1800000),
(5, 1, 1500000),
(6, 2, 5200000);

-- Thêm bảng hotel_images
CREATE TABLE IF NOT EXISTS hotel_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Thêm ảnh mẫu cho khách sạn
INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES
(1, 'images/hotels/sheraton-hanoi-1.jpg', 1),
(1, 'images/hotels/sheraton-hanoi-2.jpg', 0),
(2, 'images/hotels/vinpearl-nhatrang-1.jpg', 1),
(3, 'images/hotels/melia-danang-1.jpg', 1),
(4, 'images/hotels/liberty-saigon-1.jpg', 1),
(5, 'images/hotels/lasiesta-hanoi-1.jpg', 1),
(6, 'images/hotels/eden-phuquoc-1.jpg', 1);