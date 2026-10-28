<?php
/**
 * GET  /api/bookings.php                (Bearer) -> danh sách đơn của chính khách đang đăng nhập
 * POST /api/bookings.php                (Bearer) -> tạo đơn mới
 *   Body: { "room_id":.., "check_in":"YYYY-MM-DD", "check_out":"YYYY-MM-DD", "guests":2,
 *           "note":"", "promo_code":"", "services":[{"id":1,"quantity":2}, ...] }
 * 201 -> { "data": { "booking": {...} } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard(['GET', 'POST']);

api_run(function () {
    global $pdo;
    $customer = api_auth_customer();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $st = $pdo->prepare("SELECT b.*, rt.name room_name FROM bookings b
            JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id
            WHERE b.customer_id=? ORDER BY b.created_at DESC LIMIT 100");
        $st->execute([$customer['id']]);
        api_json(['data' => ['bookings' => $st->fetchAll()]]);
    }

    // ----- POST: tạo đơn mới -----
    $b = api_body();
    $roomId = (int)($b['room_id'] ?? 0);
    $in = (string)($b['check_in'] ?? '');
    $out = (string)($b['check_out'] ?? '');
    $guests = (int)($b['guests'] ?? 1);
    $note = trim((string)($b['note'] ?? ''));
    $validDate = function ($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; };

    $rs = $pdo->prepare("SELECT r.*, rt.price, rt.capacity FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id WHERE r.id=?");
    $rs->execute([$roomId]);
    $room = $rs->fetch();
    if (!$room) api_error('Phòng không tồn tại.', 404, 'not_found');
    if (!$validDate($in) || !$validDate($out)) api_error('Ngày nhận/trả phòng không hợp lệ.', 422, 'validation_error');

    $today = date('Y-m-d');
    $nights = nights_between($in, $out);
    if ($in < $today) api_error('Ngày nhận phòng không được ở quá khứ.', 422, 'validation_error');
    if ($nights < 1 || $out <= $in) api_error('Ngày trả phòng phải sau ngày nhận phòng.', 422, 'validation_error');
    if ($guests < 1 || $guests > $room['capacity']) api_error('Số khách vượt quá sức chứa của loại phòng này.', 422, 'validation_error');

    $roomTotal = $room['price'] * $nights;
    $serviceTotal = 0; $selected = [];
    foreach ((array)($b['services'] ?? []) as $svc) {
        $sid = (int)($svc['id'] ?? 0); $qty = max(0, min(20, (int)($svc['quantity'] ?? 0)));
        if ($sid && $qty > 0) {
            $s = $pdo->prepare("SELECT * FROM services WHERE id=? AND active=1"); $s->execute([$sid]); $srow = $s->fetch();
            if ($srow) { $total = $srow['price'] * $qty; $serviceTotal += $total; $selected[] = [$sid, $qty, $total]; }
        }
    }
    $subtotal = $roomTotal + $serviceTotal;
    $promo = null; $discount = 0;
    if (trim((string)($b['promo_code'] ?? '')) !== '' && db_is_v2()) {
        [$promo, $err] = promo_lookup($pdo, $b['promo_code'], $subtotal);
        if ($err) api_error($err, 400, 'invalid_promo');
        $discount = promo_discount($promo, $subtotal);
    }

    // Chọn phòng trống + ghi đơn trong CÙNG một transaction, có khóa dòng phòng -> không đặt trùng phòng
    $created = create_booking_atomic($pdo, [
        'customer_id' => $customer['id'], 'room_type_id' => $room['room_type_id'], 'preferred_room_id' => $roomId,
        'check_in' => $in, 'check_out' => $out, 'guests' => $guests, 'note' => $note,
        'room_total' => $roomTotal, 'service_total' => $serviceTotal,
        'promo_code' => $promo ? $promo['code'] : null, 'discount' => $discount,
        'grand_total' => $subtotal - $discount, 'services' => $selected,
    ]);
    if (!$created) api_error('Loại phòng này đã hết phòng trong khoảng thời gian đã chọn.', 409, 'no_room_available');

    $st = $pdo->prepare("SELECT * FROM bookings WHERE id=?");
    $st->execute([$created['id']]);
    api_json(['data' => ['booking' => $st->fetch()]], 201);
});
