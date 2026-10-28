-- =====================================================
-- BlueSea Resort - NÂNG CẤP LÊN PHIÊN BẢN 3
-- Dùng khi BẠN ĐÃ có database (từ bluesea_resort.sql hoặc upgrade_v2.sql) và không muốn mất dữ liệu.
-- Chọn database `bluesea_resort` trong phpMyAdmin -> Import file này. Chạy lại nhiều lần không lỗi.
-- Thêm:
--   * bảng contact_messages  : lưu tin nhắn từ form Liên hệ (xem trong Admin -> Tin nhắn liên hệ)
--   * bảng login_attempts    : đếm lần đăng nhập sai / lượt gửi form để khóa tạm (chống đoán mật khẩu, chống spam)
--   * trạng thái thanh toán 'refunded' : đơn đã thu tiền rồi bị hủy sẽ được ghi nhận hoàn tiền, không còn tính vào doanh thu
--   * chỉ mục giúp kiểm tra phòng trống và dọn đơn quá hạn nhanh hơn
-- (Người cài mới hoàn toàn KHÔNG cần file này: bluesea_resort.sql đã bao gồm.)
-- =====================================================
USE bluesea_resort;

CREATE TABLE IF NOT EXISTS contact_messages (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('new','read') NOT NULL DEFAULT 'new',
 ip VARCHAR(45) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_contact_status (status, created_at)
);

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 scope VARCHAR(20) NOT NULL,
 identifier VARCHAR(150) NOT NULL,
 ip VARCHAR(45) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_attempts_identifier (scope, identifier, created_at),
 INDEX idx_attempts_ip (scope, ip, created_at)
);

ALTER TABLE bookings MODIFY payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid';

-- Sửa dữ liệu cũ: đơn đã hủy nhưng vẫn ghi "paid" -> chuyển thành "refunded" để không bị cộng vào doanh thu
UPDATE bookings SET payment_status='refunded' WHERE status='cancelled' AND payment_status='paid';
UPDATE payments p JOIN bookings b ON b.id=p.booking_id SET p.status='refunded' WHERE b.payment_status='refunded' AND p.status='success';

ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_room_dates (room_id, check_in, check_out);
ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_status_created (status, created_at);
