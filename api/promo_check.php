<?php
/**
 * POST /api/promo_check.php
 * Body: { "code": "WELCOME10", "subtotal": 2500000 }
 * 200 -> { "data": { "code":.., "discount_amount":.., "label":.. } }
 * 400 -> { "error": {...} }  (mã không hợp lệ/hết hạn/chưa đủ điều kiện)
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('POST');

api_run(function () {
    global $pdo;
    if (!db_is_v2()) api_error('Chức năng khuyến mãi chưa sẵn sàng (cần nâng cấp cơ sở dữ liệu).', 503, 'feature_disabled');
    $b = api_body();
    $subtotal = (float)($b['subtotal'] ?? 0);
    [$promo, $err] = promo_lookup($pdo, (string)($b['code'] ?? ''), $subtotal);
    if ($err) api_error($err, 400, 'invalid_promo');
    api_json(['data' => [
        'code' => $promo['code'], 'label' => promo_label($promo),
        'discount_amount' => promo_discount($promo, $subtotal),
    ]]);
});
