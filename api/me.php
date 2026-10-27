<?php
/**
 * GET /api/me.php   (Header: Authorization: Bearer <token>)
 * 200 -> { "data": { "customer": {...} } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('GET');

api_run(function () {
    $c = api_auth_customer();
    unset($c['password']);
    api_json(['data' => ['customer' => $c]]);
});
