-- =====================================================
-- BlueSea Resort - NÂNG CẤP LÊN PHIÊN BẢN 2
-- Dùng khi BẠN ĐÃ IMPORT bluesea_resort.sql (bản cũ) và không muốn mất dữ liệu.
-- Chọn database `bluesea_resort` trong phpMyAdmin -> Import file này (chỉ 1 lần).
-- Có thể chạy lại nhiều lần mà không lỗi.
-- Thêm: bảng promotions, reviews, settings, api_tokens + 2 cột giảm giá cho bookings + vai trò (role) cho admin.
-- (Người cài mới hoàn toàn KHÔNG cần file này: bluesea_resort.sql đã bao gồm.)
-- =====================================================
USE bluesea_resort;

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS promo_code VARCHAR(30) NULL AFTER service_total;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER promo_code;

CREATE TABLE IF NOT EXISTS promotions (
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
CREATE TABLE IF NOT EXISTS reviews (
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
CREATE TABLE IF NOT EXISTS settings (
 setting_key VARCHAR(50) PRIMARY KEY,
 setting_value TEXT
);

INSERT IGNORE INTO promotions(code,title,description,discount_type,discount_value,min_total,start_date,end_date) VALUES
('WELCOME10','Chào mừng khách mới','Giảm 10% cho đơn từ 2.000.000đ','percent',10,2000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 180 DAY)),
('SPA500','Ưu đãi nghỉ dưỡng','Giảm 500.000đ cho đơn từ 5.000.000đ','amount',500000,5000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 120 DAY)),
('FAMILY15','Family Escape','Giảm 15% cho đơn từ 9.000.000đ','percent',15,9000000,DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 90 DAY));

INSERT IGNORE INTO settings(setting_key,setting_value) VALUES
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

-- ===== Phân quyền admin + API token (dùng cho tầng REST API) =====
ALTER TABLE admins ADD COLUMN IF NOT EXISTS role ENUM('admin','staff') NOT NULL DEFAULT 'admin';
INSERT IGNORE INTO admins(name,email,password,role) VALUES
('Nhân viên lễ tân','staff@bluesearesort.vn','$2y$10$vc35WorIwXlwZD8BX.9aKujRIII/eTgJchJEq..uLGMMaq2dai3vu','staff');

CREATE TABLE IF NOT EXISTS api_tokens (
 id INT AUTO_INCREMENT PRIMARY KEY,
 customer_id INT NOT NULL,
 token CHAR(64) UNIQUE NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 expires_at TIMESTAMP NOT NULL,
 revoked_at TIMESTAMP NULL,
 CONSTRAINT fk_apitoken_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
     ON DELETE CASCADE ON UPDATE CASCADE
);

-- ===== DỮ LIỆU DEMO ĐÁNH GIÁ (tùy chọn: chỉ có tác dụng nếu bạn đã import demo_bookings.sql) =====
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Vị trí đẹp, nhân viên thân thiện. Trừ một điểm vì check-in hơi lâu.','pending',DATE_SUB(NOW(), INTERVAL 23 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=118 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Dịch vụ tạm được, phòng cần bảo trì một vài chi tiết nhỏ.','pending',DATE_SUB(NOW(), INTERVAL 81 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=107 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Dịch vụ tạm được, phòng cần bảo trì một vài chi tiết nhỏ.','pending',DATE_SUB(NOW(), INTERVAL 95 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=144 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 123 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=28 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,1,'Trải nghiệm chưa như mong đợi, mong resort cải thiện dịch vụ.','approved',DATE_SUB(NOW(), INTERVAL 130 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=52 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Vị trí đẹp, nhân viên thân thiện. Trừ một điểm vì check-in hơi lâu.','approved',DATE_SUB(NOW(), INTERVAL 97 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=154 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,2,'Điều hòa hoạt động không tốt, đã báo nhưng xử lý hơi chậm.','approved',DATE_SUB(NOW(), INTERVAL 21 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=32 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 38 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=81 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 55 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=92 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Phòng ổn, nhưng cuối tuần khá đông nên hơi ồn.','approved',DATE_SUB(NOW(), INTERVAL 136 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=97 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 164 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=153 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Phòng đẹp, tiện nghi đầy đủ. Bữa sáng có thể đa dạng hơn một chút.','approved',DATE_SUB(NOW(), INTERVAL 70 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=138 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 88 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=74 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Resort đẹp, gần biển, dịch vụ chu đáo. Cả nhà đều hài lòng.','approved',DATE_SUB(NOW(), INTERVAL 23 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=164 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Dịch vụ tạm được, phòng cần bảo trì một vài chi tiết nhỏ.','approved',DATE_SUB(NOW(), INTERVAL 21 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=166 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 13 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=63 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Kỳ nghỉ tuyệt vời cùng gia đình, hồ bơi đẹp và đồ ăn sáng ngon.','approved',DATE_SUB(NOW(), INTERVAL 127 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=26 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 111 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=135 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 97 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=95 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Phòng đẹp, tiện nghi đầy đủ. Bữa sáng có thể đa dạng hơn một chút.','approved',DATE_SUB(NOW(), INTERVAL 164 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=44 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 66 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=48 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Phòng đẹp, tiện nghi đầy đủ. Bữa sáng có thể đa dạng hơn một chút.','approved',DATE_SUB(NOW(), INTERVAL 139 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=158 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Phòng ổn, nhưng cuối tuần khá đông nên hơi ồn.','approved',DATE_SUB(NOW(), INTERVAL 18 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=172 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Resort đẹp, gần biển, dịch vụ chu đáo. Cả nhà đều hài lòng.','approved',DATE_SUB(NOW(), INTERVAL 131 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=86 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Phòng đẹp, tiện nghi đầy đủ. Bữa sáng có thể đa dạng hơn một chút.','approved',DATE_SUB(NOW(), INTERVAL 189 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=70 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Không gian yên tĩnh, thiết kế đẹp, đúng như hình. Rất đáng tiền.','approved',DATE_SUB(NOW(), INTERVAL 84 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=38 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Kỳ nghỉ tuyệt vời cùng gia đình, hồ bơi đẹp và đồ ăn sáng ngon.','approved',DATE_SUB(NOW(), INTERVAL 125 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=46 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 99 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=40 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 51 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=100 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 55 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=119 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Nghỉ dưỡng thoải mái, sẽ giới thiệu bạn bè.','approved',DATE_SUB(NOW(), INTERVAL 166 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=115 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Không gian yên tĩnh, thiết kế đẹp, đúng như hình. Rất đáng tiền.','approved',DATE_SUB(NOW(), INTERVAL 87 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=162 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,2,'Điều hòa hoạt động không tốt, đã báo nhưng xử lý hơi chậm.','approved',DATE_SUB(NOW(), INTERVAL 49 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=121 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Vị trí đẹp, nhân viên thân thiện. Trừ một điểm vì check-in hơi lâu.','approved',DATE_SUB(NOW(), INTERVAL 165 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=142 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Không gian yên tĩnh, thiết kế đẹp, đúng như hình. Rất đáng tiền.','approved',DATE_SUB(NOW(), INTERVAL 58 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=98 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,3,'Dịch vụ tạm được, phòng cần bảo trì một vài chi tiết nhỏ.','approved',DATE_SUB(NOW(), INTERVAL 172 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=45 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Không gian yên tĩnh, thiết kế đẹp, đúng như hình. Rất đáng tiền.','approved',DATE_SUB(NOW(), INTERVAL 135 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=116 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,2,'Điều hòa hoạt động không tốt, đã báo nhưng xử lý hơi chậm.','approved',DATE_SUB(NOW(), INTERVAL 78 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=31 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 123 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=41 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Phòng đẹp, tiện nghi đầy đủ. Bữa sáng có thể đa dạng hơn một chút.','approved',DATE_SUB(NOW(), INTERVAL 72 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=160 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,5,'Phòng sạch sẽ, view biển tuyệt đẹp, nhân viên rất nhiệt tình. Sẽ quay lại!','approved',DATE_SUB(NOW(), INTERVAL 76 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=149 AND b.status='checked_out';
INSERT IGNORE INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status,created_at) SELECT b.id,b.customer_id,r.room_type_id,4,'Vị trí đẹp, nhân viên thân thiện. Trừ một điểm vì check-in hơi lâu.','approved',DATE_SUB(NOW(), INTERVAL 134 DAY) FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.id=19 AND b.status='checked_out';
