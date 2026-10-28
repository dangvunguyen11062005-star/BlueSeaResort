<?php
/**
 * POST /api/register.php
 * Body: { "name": "...", "phone": "...", "email": "...", "password": "..." }
 * 201 -> { "data": { "customer": {...} } }
 * 400/409 -> { "error": {...} }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('POST');

api_run(function () {
    global $pdo;
    $b = api_body();
    $name = trim($b['name'] ?? '');
    $phone = trim($b['phone'] ?? '');
    $email = trim($b['email'] ?? '');
    $password = (string)($b['password'] ?? '');

    if ($name === '' || mb_strlen($name) > 120) api_error('Họ tên không hợp lệ.', 422, 'validation_error');
    if (!valid_phone($phone)) api_error('Số điện thoại không hợp lệ (8-15 số).', 422, 'validation_error');
    if (!valid_email($email)) api_error('Email không đúng định dạng.', 422, 'validation_error');
    if (strlen($password) < 6) api_error('Mật khẩu phải có ít nhất 6 ký tự.', 422, 'validation_error');

    try {
        $pdo->prepare("INSERT INTO customers(name,phone,email,password) VALUES(?,?,?,?)")
            ->execute([$name, $phone, $email, password_make($password)]);
    } catch (PDOException $ex) {
        api_error('Email đã được sử dụng.', 409, 'email_taken');
    }
    $id = $pdo->lastInsertId();
    $st = $pdo->prepare("SELECT id,name,phone,email,created_at FROM customers WHERE id=?");
    $st->execute([$id]);
    api_json(['data' => ['customer' => $st->fetch()]], 201);
});
