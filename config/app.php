<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/BlueSeaResort');
define('APP_LOG_DIR', __DIR__ . '/../logs');

require_once __DIR__ . '/../includes/icons.php';

/* =====================================================
   XỬ LÝ NGOẠI LỆ TOÀN CỤC
   - Không hiển thị lỗi PHP/SQL thô ra trình duyệt (display_errors=0).
   - Mọi lỗi/ngoại lệ được ghi vào logs/app.log để nhà phát triển xem.
   - Người dùng chỉ thấy một trang thông báo lỗi thân thiện.
   Bỏ qua khi chạy ở tầng API (mode=json): lỗi được trả về dạng JSON thay vì HTML.
   ===================================================== */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
if (!is_dir(APP_LOG_DIR)) @mkdir(APP_LOG_DIR, 0775, true);
ini_set('error_log', APP_LOG_DIR . '/app.log');

function app_is_api_request() {
    return defined('API_MODE') && API_MODE === true;
}

function app_friendly_error_page($message = 'Rất tiếc, hệ thống gặp sự cố. Vui lòng thử lại sau.') {
    if (app_is_api_request()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => ['code' => 'server_error', 'message' => $message]], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(500);
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Có lỗi xảy ra</title>'
       . '<style>body{font-family:system-ui,Arial,sans-serif;background:#eaf3fa;display:grid;place-items:center;min-height:100vh;margin:0}'
       . '.box{background:#fff;padding:36px 42px;border-radius:18px;box-shadow:0 10px 30px rgba(12,74,120,.12);max-width:440px;text-align:center}'
       . 'h1{font-size:20px;color:#12324f;margin:0 0 10px}p{color:#3d5a73;font-size:14.5px;margin:0 0 18px}'
       . 'a{display:inline-block;background:#045a92;color:#fff;text-decoration:none;padding:10px 22px;border-radius:9px;font-weight:600}</style></head>'
       . '<body><div class="box"><h1>Rất tiếc, có lỗi xảy ra</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
       . '<a href="' . BASE_URL . '/index.php">Về trang chủ</a></div></body></html>';
    exit;
}

set_error_handler(function ($level, $message, $file, $line) {
    error_log("[PHP $level] $message tại $file:$line");
    if (in_array($level, [E_ERROR, E_USER_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_friendly_error_page();
    }
    return true; // không in cảnh báo/notice ra trang
});

set_exception_handler(function ($ex) {
    error_log('[Uncaught] ' . $ex->getMessage() . ' tại ' . $ex->getFile() . ':' . $ex->getLine() . "\n" . $ex->getTraceAsString());
    app_friendly_error_page();
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[Fatal] ' . $err['message'] . ' tại ' . $err['file'] . ':' . $err['line']);
        if (!headers_sent()) app_friendly_error_page();
    }
});

/* ---------- Hàm cơ bản ---------- */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function is_customer_logged_in() {
    return isset($_SESSION['customer']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin']);
}

function require_customer() {
    if (!is_customer_logged_in()) {
        redirect(BASE_URL . '/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

/* $role: null = chỉ cần đăng nhập; 'admin' = chỉ Administrator mới được vào (chặn Staff) */
function require_admin($role = null) {
    if (!is_admin_logged_in()) redirect(BASE_URL . '/admin/login.php');
    if ($role !== null && ($_SESSION['admin']['role'] ?? 'admin') !== $role) {
        flash_set('Tài khoản của bạn không có quyền truy cập chức năng này. Vui lòng liên hệ Administrator.', 'error');
        redirect(BASE_URL . '/admin/index.php');
    }
}

function is_admin_role($role) {
    return ($_SESSION['admin']['role'] ?? 'admin') === $role;
}

/* ---------- Mật khẩu: chuẩn password_hash(), tự nâng cấp mật khẩu cũ (sha256) khi đăng nhập thành công ---------- */
function password_make($plain) {
    return password_hash($plain, PASSWORD_BCRYPT);
}

/* Trả về true nếu đúng mật khẩu. Hỗ trợ cả hash cũ (sha256 hex 64 ký tự, từ các bản trước) lẫn hash chuẩn bcrypt. */
function password_matches($plain, $hashFromDb) {
    if (preg_match('/^\$2y\$/', (string)$hashFromDb)) {
        return password_verify($plain, $hashFromDb);
    }
    // Hash kiểu cũ: SHA2(...,256) tạo ra chuỗi hex 64 ký tự
    return hash_equals((string)$hashFromDb, hash('sha256', $plain));
}

/* Gọi sau khi xác thực mật khẩu thành công: nếu đang dùng hash cũ, tự nâng cấp lên bcrypt */
function password_maybe_upgrade(PDO $pdo, $table, $id, $plain, $hashFromDb) {
    if (!preg_match('/^\$2y\$/', (string)$hashFromDb)) {
        $pdo->prepare("UPDATE `$table` SET password=? WHERE id=?")->execute([password_make($plain), $id]);
    }
}

/* ---------- Kiểm tra định dạng email phía server (không chỉ dựa vào type="email" của trình duyệt) ---------- */
function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 150;
}

/* =====================================================
   CSRF: chống giả mạo yêu cầu (Cross-Site Request Forgery)
   Mỗi form POST phải kèm csrf_field(); mọi xử lý POST phải gọi require_csrf() đầu tiên.
   ===================================================== */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/* Dừng xử lý và quay lại trang trước nếu token sai/thiếu (hết hạn phiên, submit trùng lặp, hoặc bị giả mạo) */
function require_csrf() {
    $ok = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
    if (!$ok) {
        flash_set('Yêu cầu không hợp lệ hoặc đã hết hạn (có thể do tải lại trang). Vui lòng thử lại.', 'error');
        redirect($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php'));
    }
}

/* Chỉ cho phép chuyển hướng nội bộ sau khi đăng nhập */
function safe_next($next, $default) {
    $next = (string)$next;
    return (strpos($next, BASE_URL . '/') === 0 && strpos($next, '//') === false) ? $next : $default;
}

/* ---------- Định dạng ---------- */
function money($v) {
    return number_format((float)$v, 0, ',', '.') . 'đ';
}

function date_vn($d, $fmt = 'd/m/Y') {
    return $d ? date($fmt, strtotime($d)) : '';
}

function nights_between($a, $b) {
    return max(0, (int)(new DateTime($a))->diff(new DateTime($b))->days);
}

function img($file) {
    return BASE_URL . '/assets/images/' . $file;
}

function initials($name) {
    $parts = preg_split('/\s+/u', trim((string)$name));
    $last = end($parts);
    return mb_strtoupper(mb_substr($last ?: 'K', 0, 1, 'UTF-8'), 'UTF-8');
}

function weekday_vn($ts = null) {
    $names = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    return $names[(int)date('w', $ts ?? time())];
}

/* ---------- Nhãn trạng thái ---------- */
function booking_status($s) {
    $map = [
        'pending'     => ['Chờ thanh toán', 'warn'],
        'confirmed'   => ['Đã xác nhận', 'ok'],
        'checked_in'  => ['Đang lưu trú', 'warn'],
        'checked_out' => ['Đã trả phòng', 'info'],
        'cancelled'   => ['Đã hủy', 'bad'],
    ];
    return $map[$s] ?? [$s, 'info'];
}

function room_status($s) {
    $map = [
        'available'   => ['Còn trống', 'ok'],
        'occupied'    => ['Đang thuê', 'bad'],
        'maintenance' => ['Bảo trì', 'warn'],
    ];
    return $map[$s] ?? [$s, 'info'];
}

/* ---------- Tiện nghi & dịch vụ ---------- */
function amenity_defs() {
    return [
        'wifi'      => ['WiFi miễn phí', 'wifi'],
        'ac'        => ['Điều hòa', 'snowflake'],
        'tv'        => ['Tivi', 'tv'],
        'balcony'   => ['Ban công', 'door-open'],
        'fridge'    => ['Tủ lạnh', 'refrigerator'],
        'hairdryer' => ['Máy sấy tóc', 'wind'],
        'bathtub'   => ['Bồn tắm', 'bath'],
        'pool'      => ['Hồ bơi riêng', 'waves'],
    ];
}

function room_amenities($csv) {
    $defs = amenity_defs();
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', (string)$csv))) as $key) {
        if (isset($defs[$key])) $out[$key] = $defs[$key];
    }
    return $out;
}

function service_icon($name) {
    $n = mb_strtolower((string)$name, 'UTF-8');
    if (strpos($n, 'spa') !== false || strpos($n, 'massage') !== false) return 'flower-2';
    if (strpos($n, 'đưa đón') !== false || strpos($n, 'sân bay') !== false) return 'plane';
    if (strpos($n, 'ăn') !== false || strpos($n, 'nhà hàng') !== false || strpos($n, 'buffet') !== false) return 'utensils';
    if (strpos($n, 'xe') !== false) return 'bike';
    if (strpos($n, 'tour') !== false) return 'compass';
    if (strpos($n, 'bơi') !== false) return 'waves';
    return 'sparkles';
}

/* ---------- Ngày ở (giữ nguyên khi chuyển trang) ---------- */
function stay_defaults() {
    $valid = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; };
    $today = date('Y-m-d');
    $in = $_GET['check_in'] ?? '';
    $out = $_GET['check_out'] ?? '';
    if (!$valid($in) || $in < $today) $in = date('Y-m-d', strtotime('+1 day'));
    if (!$valid($out) || $out <= $in) $out = date('Y-m-d', strtotime($in . ' +3 day'));
    $guests = (int)($_GET['guests'] ?? 2);
    if ($guests < 1 || $guests > 6) $guests = 2;
    return ['check_in' => $in, 'check_out' => $out, 'guests' => $guests];
}

/* Chỉ mang theo tham số mà người dùng đã chọn */
function stay_params() {
    $s = stay_defaults();
    $p = [];
    foreach (['check_in', 'check_out', 'guests'] as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') $p[$k] = $s[$k];
    }
    return $p;
}

function url_with($path, array $params = []) {
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
    return BASE_URL . '/' . ltrim($path, '/') . ($params ? '?' . http_build_query($params) : '');
}

/* ---------- Chọn phòng còn trống theo loại phòng ---------- */
function find_free_room(PDO $pdo, $typeId, $in, $out, $preferred = null) {
    $st = $pdo->prepare("SELECT id FROM rooms WHERE room_type_id=? AND status<>'maintenance' ORDER BY id");
    $st->execute([$typeId]);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if ($preferred && in_array((int)$preferred, $ids, true)) {
        $ids = array_merge([(int)$preferred], array_diff($ids, [(int)$preferred]));
    }
    $chk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status<>'cancelled' AND check_in < ? AND check_out > ?");
    foreach ($ids as $id) {
        $chk->execute([$id, $out, $in]);
        if (!$chk->fetchColumn()) return $id;
    }
    return null;
}

/* ---------- Thông báo nhanh (hiện 1 lần sau khi chuyển trang) ---------- */
function flash_set($msg, $type = 'success') {
    $_SESSION['flash'] = [$type, $msg];
}
function flash_get() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Kiểm tra database đã nâng cấp lên phiên bản 2 chưa ---------- */
function db_is_v2() {
    static $v2 = null;
    if ($v2 !== null) return $v2;
    global $pdo;
    if (!isset($pdo)) { $v2 = false; return $v2; }
    try {
        $v2 = (bool)$pdo->query("SHOW COLUMNS FROM bookings LIKE 'discount_amount'")->fetch()
           && (bool)$pdo->query("SHOW TABLES LIKE 'promotions'")->fetch()
           && (bool)$pdo->query("SHOW TABLES LIKE 'reviews'")->fetch()
           && (bool)$pdo->query("SHOW TABLES LIKE 'settings'")->fetch();
    } catch (Exception $e) { $v2 = false; }
    return $v2;
}

/* ---------- Cài đặt resort (bảng settings), có giá trị mặc định nếu chưa nâng cấp ---------- */
function setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            global $pdo;
            if (!isset($pdo)) { require __DIR__ . '/database.php'; }
            foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) { /* chưa có bảng settings: dùng mặc định */ }
    }
    return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}

/* ---------- Khuyến mãi ---------- */
function promo_lookup(PDO $pdo, $code, $subtotal) {
    $code = strtoupper(trim((string)$code));
    if ($code === '') return [null, null];
    try {
        $st = $pdo->prepare("SELECT * FROM promotions WHERE code=?");
        $st->execute([$code]);
        $p = $st->fetch();
    } catch (Exception $e) { return [null, 'Chức năng khuyến mãi chưa sẵn sàng.']; }
    $today = date('Y-m-d');
    if (!$p || !$p['active']) return [null, 'Mã khuyến mãi không hợp lệ.'];
    if ($today < $p['start_date']) return [null, 'Mã khuyến mãi chưa đến thời gian áp dụng.'];
    if ($today > $p['end_date']) return [null, 'Mã khuyến mãi đã hết hạn.'];
    if ($subtotal < $p['min_total']) return [null, 'Đơn hàng cần tối thiểu ' . money($p['min_total']) . ' để dùng mã này.'];
    return [$p, null];
}
function promo_discount($p, $subtotal) {
    if (!$p) return 0;
    $d = $p['discount_type'] === 'percent' ? round($subtotal * $p['discount_value'] / 100) : $p['discount_value'];
    return (int)min($d, $subtotal);
}
function promo_label($p) {
    return $p['discount_type'] === 'percent' ? 'Giảm ' . (int)$p['discount_value'] . '%' : 'Giảm ' . money($p['discount_value']);
}
function promo_state($p) {
    $today = date('Y-m-d');
    if (!$p['active']) return ['Tạm dừng', 'warn'];
    if ($today < $p['start_date']) return ['Sắp diễn ra', 'info'];
    if ($today > $p['end_date']) return ['Đã hết hạn', 'bad'];
    return ['Đang chạy', 'ok'];
}

/* ---------- Đánh giá ---------- */
function rating_stars($rating) {
    $n = (int)round($rating);
    $out = '<span class="rating" role="img" aria-label="' . e($n) . ' trên 5 sao">';
    for ($i = 1; $i <= 5; $i++) $out .= icon('star', $i <= $n ? '' : 'off');
    return $out . '</span>';
}
function review_status($s) {
    $map = ['pending' => ['Chờ duyệt', 'warn'], 'approved' => ['Đã hiển thị', 'ok'], 'hidden' => ['Đã ẩn', 'bad']];
    return $map[$s] ?? [$s, 'info'];
}
function payment_method_label($m) { return $m ?: 'Chưa chọn'; }

?>
