<?php
/**
 * POST /api/logout.php   (Header: Authorization: Bearer <token>)
 * Vô hiệu hóa token hiện tại.
 * 200 -> { "data": { "ok": true } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('POST');

api_run(function () {
    global $pdo;
    $token = api_bearer_token();
    if (!$token) api_error('Thiếu token xác thực.', 401, 'unauthorized');
    $pdo->prepare("UPDATE api_tokens SET revoked_at=NOW() WHERE token=?")->execute([$token]);
    api_json(['data' => ['ok' => true]]);
});
