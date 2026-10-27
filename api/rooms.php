<?php
/**
 * GET /api/rooms.php?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD&guests=2&min_price=&max_price=
 * Danh sách loại phòng còn phòng hoạt động, lọc theo sức chứa/giá (tham số nào cũng tùy chọn).
 * 200 -> { "data": { "rooms": [ {...} ] } }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('GET');

api_run(function () {
    global $pdo;
    $guests = max(1, (int)($_GET['guests'] ?? 1));
    $min = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $max = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000;

    $st = $pdo->prepare("SELECT rt.*, MIN(r.id) room_id FROM room_types rt
        JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance'
        WHERE rt.capacity >= ? AND rt.price BETWEEN ? AND ?
        GROUP BY rt.id ORDER BY rt.price");
    $st->execute([$guests, $min, $max]);
    $rooms = $st->fetchAll();
    foreach ($rooms as &$r) {
        $r['image_url'] = img($r['image']);
        $r['amenities_list'] = array_keys(room_amenities($r['amenities']));
    }
    api_json(['data' => ['rooms' => $rooms]]);
});
