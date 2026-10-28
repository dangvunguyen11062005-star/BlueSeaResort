<?php
/**
 * Nghiệp vụ đặt phòng dùng chung cho booking.php (web) và api/bookings.php (REST).
 */

/* Thời gian giữ phòng cho đơn CHƯA được xác nhận thanh toán (đơn trạng thái 'pending'):
   - chưa chọn phương thức thanh toán  : PENDING_HOLD_HOURS_UNCHOSEN giờ
   - chọn Chuyển khoản mà chưa xác nhận: PENDING_HOLD_HOURS_TRANSFER giờ
   - chọn Tiền mặt (trả tại resort)    : giữ tới hết ngày nhận phòng (khách trả khi check-in)
   Mọi đơn pending mà ngày nhận phòng đã qua cũng bị hủy. */
if (!defined('PENDING_HOLD_HOURS_UNCHOSEN')) define('PENDING_HOLD_HOURS_UNCHOSEN', 24);
if (!defined('PENDING_HOLD_HOURS_TRANSFER')) define('PENDING_HOLD_HOURS_TRANSFER', 48);

/* Tự hủy các đơn pending quá hạn để nhả phòng. Rẻ (một câu UPDATE) nên gọi được ở nhiều nơi; mỗi request chỉ chạy một lần.
   Cũng có thể chạy định kỳ bằng cron: php cron/expire_bookings.php */
function expire_stale_bookings(PDO $pdo) {
    static $done = false;
    if ($done) return 0;
    $done = true;
    try {
        $st = $pdo->prepare("UPDATE bookings SET status='cancelled'
            WHERE status='pending' AND payment_status='unpaid' AND (
                check_in < CURDATE()
                OR (payment_method NOT IN ('Tiền mặt','Chuyển khoản') AND created_at < (NOW() - INTERVAL " . (int)PENDING_HOLD_HOURS_UNCHOSEN . " HOUR))
                OR (payment_method = 'Chuyển khoản' AND created_at < (NOW() - INTERVAL " . (int)PENDING_HOLD_HOURS_TRANSFER . " HOUR))
            )");
        $st->execute();
        return $st->rowCount();
    } catch (Throwable $ex) {
        error_log('[expire_stale_bookings] ' . $ex->getMessage());
        return 0;
    }
}

/* Mã đơn: ngày + 32 bit ngẫu nhiên mật mã (BS261027 + 8 ký tự hex). Cột booking_code có UNIQUE làm chốt chặn cuối. */
function new_booking_code() {
    return 'BS' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
}

/* Các bước chuyển trạng thái hợp lệ của đơn. Xác nhận (pending -> confirmed) CHỈ đi qua "Xác nhận đã nhận tiền". */
function booking_allowed_transitions() {
    return [
        'pending'     => ['cancelled'],
        'confirmed'   => ['checked_in', 'cancelled'],
        'checked_in'  => ['checked_out'],
        'checked_out' => [],
        'cancelled'   => [],
    ];
}

/**
 * Chọn phòng + ghi đơn trong MỘT transaction, có khóa dòng phòng (xem find_free_room) nên không thể đặt trùng phòng.
 *
 * $d: customer_id, room_type_id, preferred_room_id, check_in, check_out, guests, note,
 *     room_total, service_total, promo_code (hoặc null), discount, grand_total,
 *     services => [[service_id, quantity, total], ...]
 * Trả về ['id' => ..., 'code' => ..., 'room_id' => ...], hoặc null nếu loại phòng đã hết phòng.
 * Lỗi khác (DB...) được ném ra để nơi gọi xử lý.
 */
function create_booking_atomic(PDO $pdo, array $d) {
    expire_stale_bookings($pdo);
    $v2 = db_is_v2();

    $pdo->beginTransaction();
    try {
        $roomId = find_free_room($pdo, $d['room_type_id'], $d['check_in'], $d['check_out'], $d['preferred_room_id'] ?? null, true);
        if (!$roomId) {
            $pdo->rollBack();
            return null;
        }

        $bookingId = null; $code = null;
        for ($try = 0; $try < 5; $try++) {
            $code = new_booking_code();
            try {
                if ($v2) {
                    $ins = $pdo->prepare("INSERT INTO bookings(booking_code,customer_id,room_id,check_in,check_out,guests,note,room_total,service_total,promo_code,discount_amount,grand_total,payment_method) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $ins->execute([$code, $d['customer_id'], $roomId, $d['check_in'], $d['check_out'], $d['guests'], $d['note'], $d['room_total'], $d['service_total'], $d['promo_code'], $d['discount'], $d['grand_total'], 'Chưa chọn']);
                } else {
                    $ins = $pdo->prepare("INSERT INTO bookings(booking_code,customer_id,room_id,check_in,check_out,guests,note,room_total,service_total,grand_total,payment_method) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
                    $ins->execute([$code, $d['customer_id'], $roomId, $d['check_in'], $d['check_out'], $d['guests'], $d['note'], $d['room_total'], $d['service_total'], $d['grand_total'], 'Chưa chọn']);
                }
                $bookingId = (int)$pdo->lastInsertId();
                break;
            } catch (PDOException $ex) {
                // 1062 = trùng khóa duy nhất (hiếm khi trùng mã đơn) -> sinh mã khác và thử lại
                if ((int)($ex->errorInfo[1] ?? 0) !== 1062 || $try === 4) throw $ex;
            }
        }

        $bs = $pdo->prepare("INSERT INTO booking_services(booking_id,service_id,quantity,total) VALUES(?,?,?,?)");
        foreach ($d['services'] as $x) $bs->execute([$bookingId, $x[0], $x[1], $x[2]]);

        $pdo->commit();
        return ['id' => $bookingId, 'code' => $code, 'room_id' => $roomId];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $ex;
    }
}
