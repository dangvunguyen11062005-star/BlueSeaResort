<?php
/**
 * GET /api/bank_qr.php?booking_id=<id>   (Bearer, chỉ xem được đơn của chính mình)
 * Trả về nội dung chuỗi VietQR (EMV QR) để tự vẽ mã QR ở phía client,
 * cộng thông tin tài khoản để hiển thị dự phòng khi không quét được.
 * 200 -> { "data": { "payload": "...", "bank_name":.., "account_no":.., "account_name":.., "amount":.. } }
 * 404 -> không tìm thấy đơn / không có quyền
 * 503 -> resort chưa cấu hình số tài khoản (Cài đặt > Tài khoản nhận chuyển khoản)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/vietqr.php';
api_method_guard('GET');

api_run(function () {
    global $pdo;
    $customer = api_auth_customer();
    $id = (int)($_GET['booking_id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND customer_id=?");
    $st->execute([$id, $customer['id']]);
    $b = $st->fetch();
    if (!$b) api_error('Không tìm thấy đơn hoặc bạn không có quyền xem.', 404, 'not_found');

    $accountNo = setting('bank_account_no', '');
    if ($accountNo === '') api_error('Resort chưa cấu hình tài khoản nhận chuyển khoản.', 503, 'feature_disabled');

    $bin = setting('bank_bin', '970436');
    $accountName = setting('bank_account_name', setting('resort_name', 'BlueSea Resort'));
    $payload = vietqr_payload($bin, $accountNo, (int)$b['grand_total'], 'TT ' . $b['booking_code'], $accountName);

    api_json(['data' => [
        'payload' => $payload,
        'bank_bin' => $bin,
        'bank_name' => vietqr_bank_list()[$bin] ?? $bin,
        'account_no' => $accountNo,
        'account_name' => $accountName,
        'amount' => (int)$b['grand_total'],
        'message' => 'TT ' . $b['booking_code'],
    ]]);
});
