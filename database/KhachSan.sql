-- Tạo database
CREATE DATABASE KhachSan;
USE KhachSan;

-- Bảng users (khách hàng)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255),
    avatar VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng tours
CREATE TABLE tours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    duration_days INT DEFAULT 1,
    duration_nights INT DEFAULT 0,
    departure_location VARCHAR(100),
    destination VARCHAR(100),
    price_adult DECIMAL(12,2) NOT NULL,
    price_child DECIMAL(12,2),
    price_infant DECIMAL(12,2),
    discount_percent INT DEFAULT 0,
    available_slots INT DEFAULT 0,
    featured_image VARCHAR(255),
    gallery JSON,
    itinerary JSON,
    included_services JSON,
    excluded_services JSON,
    status ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng bookings
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    tour_id INT NOT NULL,
    booking_date DATE NOT NULL,
    departure_date DATE NOT NULL,
    num_adults INT DEFAULT 1,
    num_children INT DEFAULT 0,
    num_infants INT DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'banking', 'credit_card', 'momo') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    customer_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

-- Bảng categories (danh mục tour)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50),
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Bảng tour_categories (nhiều-nhiều)
CREATE TABLE tour_categories (
    tour_id INT,
    category_id INT,
    PRIMARY KEY (tour_id, category_id),
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Bảng reviews
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tour_id INT NOT NULL,
    user_id INT,
    user_name VARCHAR(100),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert dữ liệu mẫu
INSERT INTO categories (name, slug, icon) VALUES
('Tour Trong Nước', 'tour-trong-nuoc', 'fa-map-marked-alt'),
('Tour Nước Ngoài', 'tour-nuoc-ngoai', 'fa-globe-asia'),
('Tour Biển Đảo', 'tour-bien-dao', 'fa-umbrella-beach'),
('Tour Du Lịch Tâm Linh', 'tour-du-lich-tam-linh', 'fa-place-of-worship'),
('Tour Khám Phá', 'tour-kham-pha', 'fa-hiking'),
('Tour Nghỉ Dưỡng', 'tour-nghi-duong', 'fa-spa');

INSERT INTO tours (name, slug, short_description, duration_days, departure_location, destination, price_adult, discount_percent, available_slots, featured_image) VALUES
('Tour Đà Lạt 3N2Đ: Thành Phố Ngàn Hoa', 'tour-da-lat-3n2d', 'Khám phá thành phố mộng mơ với vườn hoa, đồi chè và không khí se lạnh', 3, 'TP.HCM', 'Đà Lạt', 2990000, 10, 15, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3'),
('Tour Phú Quốc 4N3Đ: Thiên Đường Biển Đảo', 'tour-phu-quoc-4n3d', 'Trải nghiệm thiên đường biển đảo với bãi cát trắng, nước biển trong xanh', 4, 'Hà Nội', 'Phú Quốc', 5890000, 15, 8, 'https://images.unsplash.com/photo-1552465011-b4e30bf7349d?ixlib=rb-4.0.3'),
('Tour Bali 5N4Đ: Hòn Đảo Thần Thoại', 'tour-bali-5n4d', 'Khám phá văn hóa độc đáo và cảnh quan tuyệt đẹp của hòn đảo thần thoại', 5, 'TP.HCM', 'Bali, Indonesia', 15990000, 20, 6, 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?ixlib=rb-4.0.3'),
('Tour Hạ Long - Sapa 5N4Đ', 'tour-ha-long-sapa-5n4d', 'Kết hợp kỳ quan thiên nhiên với văn hóa vùng cao', 5, 'Hà Nội', 'Hạ Long - Sapa', 7490000, 12, 10, 'https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-4.0.3');