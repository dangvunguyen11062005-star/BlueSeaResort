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

    $st = $pdo->prepare("SELECT * FROM customers WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_matches($password, $u['password'])) {
        api_error('Email hoặc mật khẩu không đúng.', 401, 'invalid_credentials');
    }
    password_maybe_upgrade($pdo, 'customers', $u['id'], $password, $u['password']);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $pdo->prepare("INSERT INTO api_tokens(customer_id,token,expires_at) VALUES(?,?,?)")->execute([$u['id'], $token, $expires]);

    unset($u['password']);
    api_json(['data' => ['token' => $token, 'expires_at' => $expires, 'customer' => $u]]);
});
