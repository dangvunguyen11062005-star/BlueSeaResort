<?php
// BlueSea Resort - PDO database connection
// Thông tin kết nối đọc từ biến môi trường hoặc config/local.php (xem config/env.php), không ghi cứng trong mã nguồn.
require_once __DIR__ . '/env.php';

$host = env_config('DB_HOST', 'localhost');
$db   = env_config('DB_NAME', 'bluesea_resort');
$user = env_config('DB_USER', 'root');   // mặc định chỉ để chạy thử trên XAMPP
$pass = env_config('DB_PASS', '');
$charset = 'utf8mb4';

// Trên môi trường thật, tuyệt đối không chạy bằng tài khoản root không mật khẩu
if (app_is_production() && ($user === 'root' || $pass === '')) {
    error_log('[DB] APP_ENV=production nhưng đang dùng DB_USER=root hoặc DB_PASS rỗng. Hãy cấu hình config/local.php.');
    http_response_code(500);
    die('Hệ thống chưa được cấu hình an toàn. Vui lòng liên hệ quản trị viên.');
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Cùng múi giờ với PHP để CURDATE()/NOW() trong SQL khớp với date() trong PHP (hosting thường để UTC)
    $pdo->exec("SET time_zone = '" . app_utc_offset() . "'");
} catch (PDOException $e) {
    error_log('[DB] Không kết nối được MySQL: ' . $e->getMessage());
    http_response_code(503);
    die(app_is_production()
        ? 'Hệ thống đang bảo trì, vui lòng quay lại sau.'
        : "Không thể kết nối MySQL. Hãy kiểm tra XAMPP và database 'bluesea_resort'.");
}
?>
