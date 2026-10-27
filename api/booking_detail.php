<?php
/**
 * GET /api/booking_detail.php?id=<booking_id>   (Bearer, chỉ xem được đơn của chính mình)
 * 200 -> { "data": { "booking": {...}, "services": [...] } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('GET');

api_run(function () {
    global $pdo;
    $customer = api_auth_customer();
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT b.*, rt.name room_name FROM bookings b
        JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id
        WHERE b.id=? AND b.customer_id=?");
    $st->execute([$id, $customer['id']]);
    $booking = $st->fetch();
    if (!$booking) api_error('Không tìm thấy đơn hoặc bạn không có quyền xem.', 404, 'not_found');
    $sv = $pdo->prepare("SELECT s.name, s.unit, bs.quantity, bs.total FROM booking_services bs JOIN services s ON s.id=bs.service_id WHERE bs.booking_id=?");
    $sv->execute([$id]);
    api_json(['data' => ['booking' => $booking, 'services' => $sv->fetchAll()]]);
});
