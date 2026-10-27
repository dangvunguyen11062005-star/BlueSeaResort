<?php
require_once 'config/database.php';
require_once 'config/app.php';
require_customer();

$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, rt.name, rt.price, rt.capacity, rt.image FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id WHERE r.id=?");
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) { http_response_code(404); die('Phòng không tồn tại.'); }

$services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY id")->fetchAll();
// Mỗi loại phòng đại diện bằng một phòng còn hoạt động (dùng cho ô chọn loại phòng)
$types = $pdo->query("SELECT rt.id, rt.name, MIN(r.id) room_id FROM room_types rt JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance' GROUP BY rt.id ORDER BY rt.price")->fetchAll();

$stay = stay_defaults();
$error = '';
$today = date('Y-m-d');
$customer = $_SESSION['customer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $in = $_POST['check_in'] ?? ''; $out = $_POST['check_out'] ?? ''; $guests = (int)($_POST['guests'] ?? 1);
    $stay = ['check_in' => $in, 'check_out' => $out, 'guests' => $guests];
    $validDate = function ($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; };
    if (!$validDate($in) || !$validDate($out)) {
        $error = 'Vui lòng chọn ngày nhận và ngày trả phòng.';
    } else {
        $nights = nights_between($in, $out);
        if ($in < $today) $error = 'Ngày nhận phòng không được ở quá khứ.';
        elseif ($nights < 1 || $out <= $in) $error = 'Ngày trả phòng phải sau ngày nhận phòng.';
        elseif ($guests < 1 || $guests > $room['capacity']) $error = 'Số khách vượt quá sức chứa của loại phòng này.';
        else {
            // Chọn một phòng còn trống cùng loại trong khoảng ngày đã chọn
            $assigned = find_free_room($pdo, $room['room_type_id'], $in, $out, $roomId);
            if (!$assigned) {
                $error = 'Loại phòng này đã hết phòng trong khoảng thời gian bạn chọn. Vui lòng đổi ngày hoặc chọn loại phòng khác.';
            } else {
                $roomTotal = $room['price'] * $nights; $serviceTotal = 0; $selected = [];
                foreach ($services as $s) {
                    $qty = max(0, min(20, (int)($_POST['service_' . $s['id']] ?? 0)));
                    if ($qty > 0) { $total = $qty * $s['price']; $serviceTotal += $total; $selected[] = [$s['id'], $qty, $total]; }
                }
                $subtotal = $roomTotal + $serviceTotal;
                $promo = null; $discount = 0;
                if (trim($_POST['promo_code'] ?? '') !== '') {
                    if (!db_is_v2()) {
                        $error = 'Chức năng khuyến mãi chưa sẵn sàng (cần nâng cấp cơ sở dữ liệu).';
                    } else {
                        [$promo, $promoErr] = promo_lookup($pdo, $_POST['promo_code'], $subtotal);
                        if ($promoErr) $error = $promoErr; else $discount = promo_discount($promo, $subtotal);
                    }
                }
                $grand = $subtotal - $discount;
                $code = 'BS' . date('ymdHis') . rand(10, 99);
                if ($error === '') {
                $pdo->beginTransaction();
                try {
                    if (db_is_v2()) {
                        $ins = $pdo->prepare("INSERT INTO bookings(booking_code,customer_id,room_id,check_in,check_out,guests,note,room_total,service_total,promo_code,discount_amount,grand_total,payment_method) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $ins->execute([$code, $customer['id'], $assigned, $in, $out, $guests, trim($_POST['note'] ?? ''), $roomTotal, $serviceTotal, $promo ? $promo['code'] : null, $discount, $grand, 'Chưa chọn']);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO bookings(booking_code,customer_id,room_id,check_in,check_out,guests,note,room_total,service_total,grand_total,payment_method) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
                        $ins->execute([$code, $customer['id'], $assigned, $in, $out, $guests, trim($_POST['note'] ?? ''), $roomTotal, $serviceTotal, $grand, 'Chưa chọn']);
                    }
                    $bookingId = $pdo->lastInsertId();
                    $bs = $pdo->prepare("INSERT INTO booking_services(booking_id,service_id,quantity,total) VALUES(?,?,?,?)");
                    foreach ($selected as $x) $bs->execute([$bookingId, $x[0], $x[1], $x[2]]);
                    $pdo->commit();
                    redirect(BASE_URL . '/payment.php?id=' . $bookingId);
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $error = 'Có lỗi khi tạo đơn đặt phòng. Vui lòng thử lại.';
                }
                }
            }
        }
    }
}

$page_title = 'Đặt phòng';
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner" style="--bg-img:url('<?= img('beach.svg') ?>');min-height:330px"></div>
</section>
<div class="container">
    <div class="sheet booking-sheet">
        <h1>Đặt phòng</h1>
        <nav class="crumbs" aria-label="Đường dẫn"><a href="<?= BASE_URL ?>/index.php">Trang chủ</a><?= icon('chevron-right') ?><span>Đặt phòng</span></nav>
        <?php if ($error): ?><div class="alert error" role="alert" style="margin-top:16px"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>

        <form method="post" id="booking-form" data-price="<?= (float)$room['price'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="room_id" value="<?= $roomId ?>">
            <div class="booking-grid">
                <div>
                    <h2 class="form-title">Thông tin khách hàng</h2>
                    <div class="form-group"><label for="b-name">Họ và tên <span class="req">*</span></label><input class="input" id="b-name" value="<?= e($customer['name']) ?>" readonly></div>
                    <div class="form-group"><label for="b-phone">Số điện thoại <span class="req">*</span></label><input class="input" id="b-phone" value="<?= e($customer['phone']) ?>" readonly></div>
                    <div class="form-group"><label for="b-email">Email <span class="req">*</span></label><input class="input" id="b-email" value="<?= e($customer['email']) ?>" readonly></div>
                    <div class="form-group"><label for="b-note">Ghi chú (nếu có)</label><textarea class="input" id="b-note" name="note" rows="4" placeholder="Yêu cầu đặc biệt..."><?= e($_POST['note'] ?? '') ?></textarea></div>
                </div>
                <div>
                    <h2 class="form-title">Thông tin đặt phòng</h2>
                    <div class="form-group"><label for="b-type">Loại phòng</label>
                        <select class="input" id="b-type" data-room-switch="<?= BASE_URL ?>/booking.php">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['room_id'] ?>"<?= (int)$t['id'] === (int)$room['room_type_id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label for="b-in">Ngày nhận</label><div class="with-icon"><input class="input" type="date" id="b-in" name="check_in" value="<?= e($stay['check_in']) ?>" min="<?= $today ?>" required></div></div>
                    <div class="form-group"><label for="b-out">Ngày trả</label><div class="with-icon"><input class="input" type="date" id="b-out" name="check_out" value="<?= e($stay['check_out']) ?>" min="<?= $today ?>" required></div></div>
                    <div class="form-group"><label for="b-guests">Số khách</label>
                        <select class="input" id="b-guests" name="guests"><?php for ($i = 1; $i <= $room['capacity']; $i++): ?><option value="<?= $i ?>"<?= $i === (int)$stay['guests'] ? ' selected' : '' ?>><?= $i ?> người</option><?php endfor; ?></select></div>
                </div>
            </div>

            <h2 class="form-title" style="margin-top:8px">Dịch vụ thêm</h2>
            <div class="service-list" style="grid-template-columns:repeat(2,1fr)">
                <?php foreach ($services as $s): ?>
                <div class="service-row">
                    <div><b><?= e($s['name']) ?></b><small><?= money($s['price']) ?>/<?= e($s['unit']) ?></small></div>
                    <div class="qty" data-qty>
                        <button type="button" data-step="-1" aria-label="Giảm số lượng <?= e($s['name']) ?>">−</button>
                        <input type="number" name="service_<?= $s['id'] ?>" value="<?= (int)($_POST['service_' . $s['id']] ?? 0) ?>" min="0" max="20" data-price="<?= (float)$s['price'] ?>" aria-label="Số lượng <?= e($s['name']) ?>">
                        <button type="button" data-step="1" aria-label="Tăng số lượng <?= e($s['name']) ?>">+</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php $activePromos = []; if (db_is_v2()) { $activePromos = $pdo->query("SELECT code FROM promotions WHERE active=1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY id")->fetchAll(PDO::FETCH_COLUMN); } ?>
            <?php if (db_is_v2()): ?>
            <div class="form-group" style="margin-top:18px;max-width:420px">
                <label for="b-promo">Mã khuyến mãi</label>
                <input class="input" id="b-promo" name="promo_code" value="<?= e($_POST['promo_code'] ?? $_GET['promo'] ?? '') ?>" placeholder="Nhập mã nếu có" autocomplete="off" style="text-transform:uppercase">
                <small class="muted">Giảm giá được tính khi bấm "Tiếp tục thanh toán".<?= $activePromos ? ' Đang có: ' . e(implode(' · ', $activePromos)) : '' ?></small>
            </div>
            <?php endif; ?>
            <div class="summary-line" aria-live="polite">
                <span data-summary-text>Tạm tính</span>
                <b data-summary-total><?= money($room['price']) ?></b>
            </div>
            <button class="btn btn-primary btn-block" type="submit" style="height:50px;font-size:15px">Tiếp tục thanh toán <?= icon('arrow-right') ?></button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
