<?php
// BlueSea Resort - PDO database connection
$host = 'localhost';
$db   = 'bluesea_resort';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('[DB] Không kết nối được MySQL: ' . $e->getMessage());
    http_response_code(503);
    die("Không thể kết nối MySQL. Hãy kiểm tra XAMPP và database 'bluesea_resort'.");
}
?>