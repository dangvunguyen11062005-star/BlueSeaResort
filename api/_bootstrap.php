<?php
/**
 * Bootstrap chung cho toàn bộ REST API của BlueSea Resort.
 * Mọi endpoint trong thư mục /api đều require file này đầu tiên.
 *
 * Quy ước:
 *  - Request/response dùng JSON (Content-Type: application/json).
 *  - Xác thực bằng Bearer token: header "Authorization: Bearer <token>"
 *    (token lấy từ POST /api/login.php, lưu trong bảng api_tokens, hạn 30 ngày).
 *  - Lỗi trả về {"error":{"code":"...","message":"..."}} kèm HTTP status tương ứng.
 *  - Thành công trả về {"data": ...}.
 *  - Toàn bộ lỗi PHP/SQL không mong muốn đều được bắt và ghi log (logs/app.log),
 *    người gọi API chỉ nhận thông báo chung, không lộ chi tiết hệ thống.
 */
define('API_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function api_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function api_error($message, $status = 400, $code = 'bad_request') {
    api_json(['error' => ['code' => $code, 'message' => $message]], $status);
}

/* Đọc body JSON (ưu tiên) hoặc form-urlencoded $_POST (để dễ test bằng Postman form-data) */
function api_body() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function api_method_guard($allowed) {
    $allowed = (array)$allowed;
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowed, true)) {
        api_error('Phương thức không được hỗ trợ. Chỉ nhận: ' . implode(', ', $allowed) . '.', 405, 'method_not_allowed');
    }
}

/* Lấy token từ header Authorization: Bearer <token> */
function api_bearer_token() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $h, $m)) return $m[1];
    return null;
}

/* Yêu cầu đăng nhập: trả về hàng customers nếu token hợp lệ, hoặc dừng với lỗi 401 */
function api_auth_customer() {
    global $pdo;
    $token = api_bearer_token();
    if (!$token) api_error('Thiếu token xác thực. Gửi header: Authorization: Bearer <token>.', 401, 'unauthorized');
    $st = $pdo->prepare("SELECT c.* FROM api_tokens t JOIN customers c ON c.id = t.customer_id
                         WHERE t.token = ? AND t.revoked_at IS NULL AND t.expires_at > NOW()");
    $st->execute([$token]);
    $customer = $st->fetch();
    if (!$customer) api_error('Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.', 401, 'unauthorized');
    return $customer;
}

/* Dùng để bọc toàn bộ logic của 1 endpoint: bắt mọi lỗi không mong muốn, ghi log, không lộ chi tiết ra ngoài */
function api_run(callable $fn) {
    try {
        $fn();
    } catch (PDOException $ex) {
        error_log('[API][SQL] ' . $ex->getMessage());
        api_error('Lỗi cơ sở dữ liệu, vui lòng thử lại sau.', 500, 'server_error');
    } catch (Throwable $ex) {
        error_log('[API] ' . $ex->getMessage() . ' tại ' . $ex->getFile() . ':' . $ex->getLine());
        api_error('Đã có lỗi xảy ra, vui lòng thử lại sau.', 500, 'server_error');
    }
}
