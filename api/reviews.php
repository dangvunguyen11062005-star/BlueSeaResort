<?php
/**
 * GET  /api/reviews.php?room_type_id=<id>     (công khai) -> đánh giá đã duyệt của loại phòng đó
 * POST /api/reviews.php                        (Bearer) -> gửi đánh giá cho đơn đã trả phòng của chính mình
 *   Body: { "booking_id":.., "rating":1-5, "comment":"" }
 * 201 -> { "data": { "review": {...} } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard(['GET', 'POST']);

api_run(function () {
    global $pdo;
    if (!db_is_v2()) api_error('Chức năng đánh giá chưa sẵn sàng (cần nâng cấp cơ sở dữ liệu).', 503, 'feature_disabled');

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $roomTypeId = (int)($_GET['room_type_id'] ?? 0);
        if (!$roomTypeId) api_error('Thiếu tham số room_type_id.', 422, 'validation_error');
        $st = $pdo->prepare("SELECT rv.rating, rv.comment, rv.created_at, c.name FROM reviews rv
            JOIN customers c ON c.id=rv.customer_id WHERE rv.room_type_id=? AND rv.status='approved' ORDER BY rv.created_at DESC LIMIT 50");
        $st->execute([$roomTypeId]);
        api_json(['data' => ['reviews' => $st->fetchAll()]]);
    }

    $customer = api_auth_customer();
    $b = api_body();
    $bookingId = (int)($b['booking_id'] ?? 0);
    $rating = (int)($b['rating'] ?? 0);
    $comment = mb_substr(trim((string)($b['comment'] ?? '')), 0, 1000);
    if ($rating < 1 || $rating > 5) api_error('Số sao phải từ 1 đến 5.', 422, 'validation_error');

    $chk = $pdo->prepare("SELECT b.id, r.room_type_id FROM bookings b JOIN rooms r ON r.id=b.room_id
        WHERE b.id=? AND b.customer_id=? AND b.status='checked_out' AND NOT EXISTS (SELECT 1 FROM reviews rv WHERE rv.booking_id=b.id)");
    $chk->execute([$bookingId, $customer['id']]);
    $row = $chk->fetch();
    if (!$row) api_error('Không thể đánh giá đơn này (chưa trả phòng, không thuộc về bạn, hoặc đã được đánh giá).', 409, 'not_eligible');

    $pdo->prepare("INSERT INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status) VALUES(?,?,?,?,?,'pending')")
        ->execute([$bookingId, $customer['id'], $row['room_type_id'], $rating, $comment]);
    api_json(['data' => ['review' => ['booking_id' => $bookingId, 'rating' => $rating, 'comment' => $comment, 'status' => 'pending']]], 201);
});
