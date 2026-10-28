<?php
/**
 * Đọc cấu hình theo môi trường. Thứ tự ưu tiên:
 *   1) biến môi trường của máy chủ (getenv)
 *   2) file config/local.php (KHÔNG commit, đã nằm trong .gitignore) - sao chép từ config/local.example.php
 *   3) giá trị mặc định (chạy được ngay trên XAMPP)
 *
 * Các khóa: APP_ENV, BASE_URL, SITE_URL, APP_TIMEZONE, DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
if (!function_exists('env_config')) {
    function env_config($key, $default = null) {
        static $local = null;
        $v = getenv($key);
        if ($v !== false) return $v; // chuỗi rỗng là giá trị hợp lệ (vd. BASE_URL rỗng khi site nằm ở gốc domain)
        if ($local === null) {
            $file = __DIR__ . '/local.php';
            $local = is_file($file) ? require $file : [];
            if (!is_array($local)) $local = [];
        }
        return array_key_exists($key, $local) ? $local[$key] : $default;
    }

    function app_timezone() {
        $tz = (string)env_config('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');
        return in_array($tz, timezone_identifiers_list(), true) ? $tz : 'Asia/Ho_Chi_Minh';
    }

    function app_is_production() {
        return strtolower((string)env_config('APP_ENV', 'development')) === 'production';
    }

    /* Độ lệch múi giờ dạng +07:00, dùng để đồng bộ múi giờ của phiên MySQL với PHP */
    function app_utc_offset() {
        return (new DateTime('now', new DateTimeZone(app_timezone())))->format('P');
    }
}
