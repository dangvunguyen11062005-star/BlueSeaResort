<?php
/**
 * Giới hạn tần suất (chống đoán mật khẩu, chống spam form) dựa trên bảng login_attempts.
 *
 * - Đăng nhập: đếm lần SAI theo email (chặn dò 1 tài khoản từ nhiều IP) và theo IP (chặn dò nhiều tài khoản từ 1 IP).
 *   Quá ngưỡng trong cửa sổ thời gian thì khóa tạm; hết cửa sổ tự mở lại. Đăng nhập đúng sẽ xóa bộ đếm của email đó.
 * - Dùng chung scope 'customer' cho cả trang đăng nhập khách lẫn api/login.php (cùng bảng customers),
 *   scope 'admin' cho trang quản trị, scope 'contact' cho form liên hệ.
 * - Nếu bảng chưa được tạo (chưa import database/upgrade_v3.sql) thì ghi log và không chặn, để site vẫn chạy.
 */

if (!defined('LOGIN_MAX_PER_ACCOUNT')) define('LOGIN_MAX_PER_ACCOUNT', 5);    // số lần sai tối đa / email
if (!defined('LOGIN_MAX_PER_IP'))      define('LOGIN_MAX_PER_IP', 20);        // số lần sai tối đa / IP
if (!defined('LOGIN_WINDOW_SECONDS'))  define('LOGIN_WINDOW_SECONDS', 900);   // 15 phút

/* IP của người dùng. Chỉ tin X-Forwarded-For khi đã khai báo TRUST_PROXY=1 (site đứng sau proxy/CDN của bạn). */
function client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (env_config('TRUST_PROXY', '') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) $ip = $first;
    }
    return substr((string)$ip, 0, 45);
}

function throttle_key($s) {
    return mb_substr(mb_strtolower(trim((string)$s), 'UTF-8'), 0, 150, 'UTF-8');
}

/* Số giây còn phải chờ nếu (scope, cột) đã có >= $max bản ghi trong $window giây gần nhất; 0 nếu chưa bị khóa */
function throttle_wait(PDO $pdo, $scope, $column, $value, $max, $window) {
    if (!in_array($column, ['identifier', 'ip'], true)) return 0;
    $st = $pdo->prepare("SELECT COUNT(*) n, TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) age
                         FROM login_attempts WHERE scope=? AND $column=? AND created_at > (NOW() - INTERVAL " . (int)$window . " SECOND)");
    $st->execute([$scope, $value]);
    $r = $st->fetch();
    if ((int)$r['n'] < $max) return 0;
    return max(1, (int)$window - (int)$r['age']);
}

function throttle_record(PDO $pdo, $scope, $identifier) {
    $pdo->prepare("INSERT INTO login_attempts(scope, identifier, ip) VALUES(?,?,?)")
        ->execute([$scope, throttle_key($identifier), client_ip()]);
    if (mt_rand(1, 100) === 1) { // thỉnh thoảng dọn bản ghi cũ
        $pdo->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 2 DAY)");
    }
}

function throttle_log_missing(Throwable $ex) {
    static $logged = false;
    if (!$logged) { error_log('[rate_limit] Không dùng được bảng login_attempts (đã import database/upgrade_v3.sql chưa?): ' . $ex->getMessage()); $logged = true; }
}

/* ----- Đăng nhập ----- */

/* Trả về số giây phải chờ (0 = được phép thử) */
function login_throttle_wait(PDO $pdo, $scope, $email) {
    try {
        return max(
            throttle_wait($pdo, $scope, 'identifier', throttle_key($email), LOGIN_MAX_PER_ACCOUNT, LOGIN_WINDOW_SECONDS),
            throttle_wait($pdo, $scope, 'ip', client_ip(), LOGIN_MAX_PER_IP, LOGIN_WINDOW_SECONDS)
        );
    } catch (Throwable $ex) { throttle_log_missing($ex); return 0; }
}

function login_throttle_fail(PDO $pdo, $scope, $email) {
    try { throttle_record($pdo, $scope, $email); } catch (Throwable $ex) { throttle_log_missing($ex); }
}

function login_throttle_clear(PDO $pdo, $scope, $email) {
    try {
        $pdo->prepare("DELETE FROM login_attempts WHERE scope=? AND identifier=?")->execute([$scope, throttle_key($email)]);
    } catch (Throwable $ex) { throttle_log_missing($ex); }
}

function login_locked_message($seconds) {
    return 'Bạn đã thử đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ' . max(1, (int)ceil($seconds / 60)) . ' phút.';
}

/* ----- Giới hạn chung theo IP (vd. form liên hệ): true nếu ĐƯỢC phép, đồng thời ghi nhận 1 lượt ----- */
function rate_limit_allow(PDO $pdo, $scope, $max, $window) {
    try {
        if (throttle_wait($pdo, $scope, 'ip', client_ip(), $max, $window) > 0) return false;
        throttle_record($pdo, $scope, client_ip());
        return true;
    } catch (Throwable $ex) { throttle_log_missing($ex); return true; }
}
