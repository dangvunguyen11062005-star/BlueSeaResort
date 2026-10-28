CREATE DATABASE IF NOT EXISTS bluesea_resort CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bluesea_resort;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS contact_messages, login_attempts, api_tokens, reviews, promotions, settings, payments, booking_services, bookings, services, rooms, room_types, customers, admins;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE admins (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','staff') NOT NULL DEFAULT 'admin'
);

CREATE TABLE customers (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(150) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL,
 address VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_types (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 description TEXT,
 price DECIMAL(12,0) NOT NULL,
 capacity INT NOT NULL,
 area INT NOT NULL DEFAULT 25,
 bed_type VARCHAR(60) NOT NULL DEFAULT 'Giường đôi',
 view_type VARCHAR(60) NOT NULL DEFAULT 'Hướng biển',
 amenities VARCHAR(255) NOT NULL DEFAULT '',
 image VARCHAR(255) NOT NULL
);

CREATE TABLE rooms (
 id INT AUTO_INCREMENT PRIMARY KEY,
 room_code VARCHAR(30) UNIQUE NOT NULL,
 room_type_id INT NOT NULL,
 floor INT DEFAULT 1,
 status ENUM('available','maintenance','occupied') DEFAULT 'available',
 CONSTRAINT fk_rooms_room_type FOREIGN KEY (room_type_id) REFERENCES room_types(id)
     ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE services (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 description VARCHAR(255),
 price DECIMAL(12,0) NOT NULL,
 unit VARCHAR(50) DEFAULT 'lần',
 active TINYINT(1) DEFAULT 1
);

CREATE TABLE bookings (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_code VARCHAR(30) UNIQUE NOT NULL,
 customer_id INT NOT NULL,
 room_id INT NOT NULL,
 check_in DATE NOT NULL,
 check_out DATE NOT NULL,
 guests INT NOT NULL DEFAULT 2,
 note TEXT,
 room_total DECIMAL(12,0) NOT NULL,
 service_total DECIMAL(12,0) DEFAULT 0,
 promo_code VARCHAR(30) NULL,
 discount_amount DECIMAL(12,0) NOT NULL DEFAULT 0,
 grand_total DECIMAL(12,0) NOT NULL,
 status ENUM('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
 payment_method VARCHAR(50) DEFAULT 'cash',
 payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_bookings_room_dates (room_id, check_in, check_out),
 INDEX idx_bookings_status_created (status, created_at),
 CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
     ON DELETE RESTRICT ON UPDATE CASCADE,
 CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms(id)
     ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE booking_services (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT NOT NULL,
 service_id INT NOT NULL,
 quantity INT NOT NULL DEFAULT 1,
 total DECIMAL(12,0) NOT NULL,
 CONSTRAINT fk_bsvc_booking FOREIGN KEY (booking_id) REFERENCES bookings(id)
     ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_bsvc_service FOREIGN KEY (service_id) REFERENCES services(id)
     ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE payments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT NOT NULL,
 amount DECIMAL(12,0) NOT NULL,
 method VARCHAR(50) NOT NULL,
 status VARCHAR(30) DEFAULT 'success',
 paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id)
     ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE api_tokens (
 id INT AUTO_INCREMENT PRIMARY KEY,
 customer_id INT NOT NULL,
 token CHAR(64) UNIQUE NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 expires_at TIMESTAMP NOT NULL,
 revoked_at TIMESTAMP NULL,
 CONSTRAINT fk_apitoken_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
     ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO admins(name,email,password,role) VALUES
('Administrator','admin@bluesearesort.vn','$2y$10$99sgV8GBGQ53hfXlxPJNI.iCrFSjZ1fZl.WZhWK.vbFArfCIBjXiq','admin'),
('Nhân viên lễ tân','staff@bluesearesort.vn','$2y$10$vc35WorIwXlwZD8BX.9aKujRIII/eTgJchJEq..uLGMMaq2dai3vu','staff');

INSERT INTO customers(name,phone,email,password,address) VALUES
('Nguyễn Văn A','0987654321','nguyenvana@gmail.com','$2y$10$hMUCSQrApXpJyX8e4nGv2O3BiaS808MPga.zPeA5UV6CfF61OtnXm','Phan Thiết, Bình Thuận');

INSERT INTO room_types(name,description,price,capacity,area,bed_type,view_type,amenities,image) VALUES
('Deluxe Garden View','Phòng hướng vườn, không gian thoáng mát, phù hợp nghỉ dưỡng 2 người. Ban công nhìn ra vườn dừa xanh mát, nội thất gỗ ấm cúng.',1500000,2,28,'Giường đôi','Hướng vườn','wifi,ac,tv,balcony,fridge,hairdryer','room1.svg'),
('Deluxe Ocean View','Phòng hướng biển với ban công riêng và tầm nhìn tuyệt đẹp. Thức dậy cùng tiếng sóng và ánh bình minh trên biển Mũi Né.',2200000,2,35,'Giường đôi','Hướng biển','wifi,ac,tv,balcony,fridge,hairdryer,bathtub','room2.svg'),
('Bungalow','Không gian bungalow riêng tư, gần biển và khu vườn nhiệt đới. Mái lá truyền thống hòa cùng tiện nghi hiện đại.',2800000,2,32,'Giường đôi','Sát biển','wifi,ac,tv,balcony,fridge,bathtub','room3.svg'),
('Family Villa','Villa rộng rãi dành cho gia đình, sức chứa tối đa 4 người. Hai phòng ngủ, phòng khách riêng và sân vườn.',4500000,4,65,'2 giường đôi','Hướng biển','wifi,ac,tv,balcony,fridge,hairdryer,bathtub','room4.svg'),
('Private Pool Villa','Villa cao cấp có hồ bơi riêng, sức chứa tối đa 6 người. Không gian riêng tư tuyệt đối cùng tầm nhìn ra biển.',6500000,6,95,'Giường King + sofa bed','Hướng biển','wifi,ac,tv,balcony,fridge,hairdryer,bathtub,pool','room5.svg');

INSERT INTO rooms(room_code,room_type_id,floor,status) VALUES
('P01',1,1,'available'),('P02',2,1,'available'),('P03',3,1,'available'),
('P04',4,2,'available'),('P05',5,2,'available'),('P06',1,1,'available'),
('P07',2,1,'available'),('P08',3,1,'available');

INSERT INTO services(name,description,price,unit) VALUES
('Spa thư giãn','Massage thư giãn 60 phút',500000,'lần'),
('Đưa đón sân bay','Xe riêng đưa đón theo lịch',700000,'chuyến'),
('Ăn sáng buffet','Buffet sáng tại nhà hàng resort',150000,'người'),
('Thuê xe máy','Xe máy trong 24 giờ',180000,'ngày'),
('Tour Mũi Né','Tour tham quan các điểm nổi bật',900000,'người');

-- ===== Khuyến mãi, đánh giá, cài đặt (phiên bản 2) =====
CREATE TABLE promotions (
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) UNIQUE NOT NULL,
 title VARCHAR(150) NOT NULL,
 description VARCHAR(255),
 discount_type ENUM('percent','amount') NOT NULL DEFAULT 'percent',
 discount_value DECIMAL(12,0) NOT NULL,
 min_total DECIMAL(12,0) NOT NULL DEFAULT 0,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1
);
CREATE TABLE reviews (
 id INT AUTO_INCREMENT PRIMARY KEY,
 booking_id INT NOT NULL UNIQUE,
 customer_id INT NOT NULL,
 room_type_id INT NOT NULL,
 rating TINYINT NOT NULL,
 comment TEXT,
 status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
 FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
 FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
);
CREATE TABLE settings (
 setting_key VARCHAR(50) PRIMARY KEY,
 setting_value TEXT
);

INSERT INTO promotions(code,title,description,discount_type,discount_value,min_total,start_date,end_date) VALUES
('WELCOME10','Chào mừng khách mới','Giảm 10% cho đơn từ 2.000.000đ','percent',10,2000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 180 DAY)),
('SPA500','Ưu đãi nghỉ dưỡng','Giảm 500.000đ cho đơn từ 5.000.000đ','amount',500000,5000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 120 DAY)),
('FAMILY15','Family Escape','Giảm 15% cho đơn từ 9.000.000đ','percent',15,9000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 90 DAY));

INSERT INTO settings(setting_key,setting_value) VALUES
('resort_name','BlueSea Resort'),
('tagline','Nghỉ dưỡng giữa thiên nhiên Phan Thiết – Mũi Né.'),
('phone','0987 654 321'),
('email','hello@bluesearesort.vn'),
('address','Mũi Né, Phan Thiết, Bình Thuận, Việt Nam'),
('support_hours','08:00 – 22:00 hằng ngày'),
('checkin_time','14:00'),
('checkout_time','12:00'),
('bank_bin','970436'),
('bank_account_no','0123456789'),
('bank_account_name','BLUESEA RESORT');

-- ===== Phiên bản 3: tin nhắn liên hệ + chống đoán mật khẩu =====
CREATE TABLE contact_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('new','read') NOT NULL DEFAULT 'new',
 ip VARCHAR(45) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_contact_status (status, created_at)
);

CREATE TABLE login_attempts (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 scope VARCHAR(20) NOT NULL,
 identifier VARCHAR(150) NOT NULL,
 ip VARCHAR(45) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_attempts_identifier (scope, identifier, created_at),
 INDEX idx_attempts_ip (scope, ip, created_at)
);
