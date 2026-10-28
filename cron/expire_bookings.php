<?php
/**
 * Tự hủy các đơn "pending" quá hạn giữ phòng (xem includes/booking_service.php).
 * Chạy bằng cron, ví dụ mỗi 15 phút:
 *     *\/15 * * * * php /duong/dan/BlueSeaResort/cron/expire_bookings.php
 * (Hệ thống cũng tự dọn khi có người xem phòng/đặt phòng, cron chỉ giúp dọn đều đặn cả lúc ít khách.)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Chỉ chạy bằng dòng lệnh.'); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$n = expire_stale_bookings($pdo);
echo date('Y-m-d H:i:s') . " - đã hủy $n đơn quá hạn\n";
