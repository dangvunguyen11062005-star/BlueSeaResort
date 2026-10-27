<?php
/**
 * GET /api/room_detail.php?id=<room_type_id>
 * 200 -> { "data": { "room": {...}, "reviews": { "avg":..,"count":..,"items":[...] } } }
 * 404 -> { "error": {...} }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('GET');

api_run(function () {
    global $pdo;
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT rt.*, MIN(r.id) room_id FROM room_types rt
        JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance' WHERE rt.id=? GROUP BY rt.id");
    $st->execute([$id]);
    $room = $st->fetch();
    if (!$room) api_error('Không tìm thấy loại phòng này.', 404, 'not_found');
    $room['image_url'] = img($room['image']);
    $room['amenities_list'] = array_keys(room_amenities($room['amenities']));

    $reviews = ['avg' => 0, 'count' => 0, 'items' => []];
    if (db_is_v2()) {
        $rs = $pdo->prepare("SELECT COUNT(*) n, COALESCE(AVG(rating),0) a FROM reviews WHERE room_type_id=? AND status='approved'");
        $rs->execute([$id]);
        $row = $rs->fetch();
        $reviews['count'] = (int)$row['n']; $reviews['avg'] = round((float)$row['a'], 1);
        $rl = $pdo->prepare("SELECT rv.rating, rv.comment, rv.created_at, c.name FROM reviews rv JOIN customers c ON c.id=rv.customer_id
            WHERE rv.room_type_id=? AND rv.status='approved' ORDER BY rv.created_at DESC LIMIT 10");
        $rl->execute([$id]);
        $reviews['items'] = $rl->fetchAll();
    }
    api_json(['data' => ['room' => $room, 'reviews' => $reviews]]);
});
