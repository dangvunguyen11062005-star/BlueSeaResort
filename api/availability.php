<?php
/**
 * GET /api/availability.php?room_type_id=2&check_in=2026-12-10&check_out=2026-12-13
 * Không cần token. Cho biết loại phòng còn bao nhiêu phòng trống trong khoảng ngày [check_in, check_out).
 *
 * 200 -> { "data": { "room_type_id": 2, "check_in": "...", "check_out": "...", "nights": 3,
 *                    "available": true, "free_rooms": 1, "total_rooms": 2,
 *                    "suggestions": [ { "check_in": "...", "check_out": "..." } ] } }
 *   - "suggestions" chỉ có phần tử khi available=false: các khoảng ngày gần nhất còn phòng, cùng số đêm.
 * 404 -> loại phòng không tồn tại hoặc không có phòng hoạt động
 * 422 -> ngày không hợp lệ / ở quá khứ / ngày trả không sau ngày nhận
 */
require_once __DIR__ . '/_bootstrap.php';
api_method_guard('GET');

api_run(function () {
    global $pdo;
    $typeId = (int)($_GET['room_type_id'] ?? 0);
    $in = (string)($_GET['check_in'] ?? '');
    $out = (string)($_GET['check_out'] ?? '');

    if ($typeId < 1) api_error('Thiếu hoặc sai room_type_id.', 422, 'validation_error');
    if ($err = stay_range_error($in, $out)) api_error($err, 422, 'validation_error');
    if (nights_between($in, $out) > 60) api_error('Thời gian lưu trú tối đa 60 đêm.', 422, 'validation_error');

    $av = room_type_availability($pdo, $typeId, $in, $out);
    if ($av['total'] < 1) api_error('Loại phòng không tồn tại hoặc hiện không có phòng hoạt động.', 404, 'not_found');

    $available = $av['free'] > 0;
    api_json(['data' => [
        'room_type_id' => $typeId,
        'check_in'     => $in,
        'check_out'    => $out,
        'nights'       => nights_between($in, $out),
        'available'    => $available,
        'free_rooms'   => $av['free'],
        'total_rooms'  => $av['total'],
        'suggestions'  => $available ? [] : suggest_free_ranges($pdo, $typeId, $in, $out),
    ]]);
});
