<?php
/**
 * POST /api/login.php
 * Body: { "email": "...", "password": "..." }
 * 200 -> { "data": { "token": "...", "expires_at": "...", "customer": {...} } }
 * 401 -> { "error": {...} }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('POST');

api_run(function () {
    global $pdo;
    $b = api_body();
    $email = trim($b['email'] ?? '');
    $password = (string)($b['password'] ?? '');
    if (!valid_email($email)) api_error('Email không đúng định dạng.', 422, 'validation_error');

    // Cùng bộ đếm với trang đăng nhập web (scope 'customer'): sai quá nhiều lần -> 429
    if (($wait = login_throttle_wait($pdo, 'customer', $email)) > 0) {
        header('Retry-After: ' . $wait);
        api_error(login_locked_message($wait), 429, 'too_many_attempts');
    }

    $st = $pdo->prepare("SELECT * FROM customers WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_matches($password, $u['password'])) {
        login_throttle_fail($pdo, 'customer', $email);
        api_error('Email hoặc mật khẩu không đúng.', 401, 'invalid_credentials');
    }
    login_throttle_clear($pdo, 'customer', $email);
    password_maybe_upgrade($pdo, 'customers', $u['id'], $password, $u['password']);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO api_tokens(customer_id,token,expires_at) VALUES(?,?,?)")->execute([$u['id'], $token, $expires]);

    unset($u['password']);
    api_json(['data' => ['token' => $token, 'expires_at' => $expires, 'customer' => $u]]);
});
