-- =====================================================
-- BlueSea Resort - NÂNG CẤP LÊN PHIÊN BẢN 4 (tích hợp AI)
-- Chọn database `bluesea_resort` trong phpMyAdmin -> Import. Chạy lại nhiều lần không lỗi.
-- Thêm bảng ai_logs: lưu câu hỏi/câu trả lời của chatbot, gợi ý phòng và trợ lý quản trị
-- để quản lý xem khách hay hỏi gì (Admin -> Trợ lý AI). Không lưu tên/email/số điện thoại.
-- Website vẫn chạy bình thường nếu chưa import (chỉ không ghi nhật ký).
-- =====================================================
USE bluesea_resort;

CREATE TABLE IF NOT EXISTS ai_logs (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 channel VARCHAR(20) NOT NULL,            -- chat | recommend | admin
 session_hash CHAR(16) NOT NULL,          -- băm 1 chiều của session, để gom cuộc hội thoại
 question TEXT NOT NULL,
 answer TEXT NULL,
 used_ai TINYINT(1) NOT NULL DEFAULT 1,   -- 0 = trả lời bằng luật dự phòng (chưa cấu hình/AI lỗi)
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_ai_logs_created (created_at),
 INDEX idx_ai_logs_channel (channel, created_at)
);
