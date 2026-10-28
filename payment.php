<?php
require_once 'config/database.php';
require_once 'config/app.php';
require_once 'includes/vietqr.php';
require_customer();
expire_stale_bookings($pdo);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, rt.name, rt.image FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id WHERE b.id=? AND b.customer_id=?");
$stmt->execute([$id, $_SESSION['customer']['id']]);
$b = $stmt->fetch();
if (!$b) { http_response_code(404); die('Không tìm thấy đơn.'); }
if ($b['payment_status'] === 'paid' || $b['status'] === 'cancelled') redirect(BASE_URL . '/customer/history.php');

$sv = $pdo->prepare("SELECT s.name, s.unit, bs.quantity, bs.total FROM booking_services bs JOIN services s ON s.id=bs.service_id WHERE bs.booking_id=?");
$sv->execute([$id]);
$extras = $sv->fetchAll();
$nights = nights_between($b['check_in'], $b['check_out']);

// Chỉ còn 2 phương thức. VNPay/MoMo đã được gỡ: khóa này cũng là danh sách trắng ở server,
// nên gửi thẳng payment_method=VNPay/MoMo bằng công cụ khác cũng bị từ chối.
$methods = [
    'Tiền mặt'     => ['cash', 'Thanh toán tại resort', 'Tiền mặt / Thẻ (cần lễ tân/quản trị viên xác nhận đã nhận tiền)'],
    'Chuyển khoản' => ['bank', 'Chuyển khoản ngân hàng', 'Chuyển khoản trước khi nhận phòng (cần lễ tân/quản trị viên xác nhận)'],
];
$awaiting = booking_awaiting_payment($b);
// Mặc định chọn lại phương thức khách đã chọn trước đó (nếu đang chờ xác nhận), ngược lại là Chuyển khoản
$selected = $awaiting ? $b['payment_method'] : 'Chuyển khoản';
$error = '';
$bankAccountNo = setting('bank_account_no', '');
$bankQrSvg = $bankAccountNo !== '' ? vietqr_svg(vietqr_payload(
    setting('bank_bin', '970436'), $bankAccountNo, (int)$b['grand_total'],
    'TT ' . $b['booking_code'], setting('bank_account_name', setting('resort_name', 'BlueSea Resort'))
)) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $method = $_POST['payment_method'] ?? '';
    if (!isset($methods[$method])) {
        $error = 'Vui lòng chọn một phương thức thanh toán hợp lệ.';
    } else {
        // Tiền mặt / Chuyển khoản: chỉ GHI NHẬN lựa chọn. Đơn vẫn "chờ thanh toán" cho tới khi lễ tân/quản trị viên
        // nhận được tiền (tại quầy hoặc về tài khoản) và bấm xác nhận (admin/bookings.php) - lúc đó mới tính là đã thanh toán.
        try {
            $pdo->prepare("UPDATE bookings SET payment_method=? WHERE id=? AND customer_id=? AND payment_status='unpaid' AND status='pending'")
                ->execute([$method, $id, $_SESSION['customer']['id']]);
        } catch (Throwable $ex) {
            error_log('[payment][' . $method . '] ' . $ex->getMessage());
            $error = 'Không thể ghi nhận yêu cầu thanh toán. Vui lòng thử lại.';
        }
        if ($error === '') redirect(BASE_URL . '/customer/history.php?awaiting=' . urlencode($b['booking_code']));
    }
    if (isset($methods[$method])) $selected = $method;
}

$page_title = 'Thanh toán';
include 'includes/header.php';
?>
<section class="section-tight" style="padding-top:30px">
    <div class="container">
        <h1 style="font-size:30px;margin-bottom:22px">Thanh toán</h1>
        <ol class="stepper" style="list-style:none;padding:0" aria-label="Các bước đặt phòng">
            <li class="step done"><span class="dot"><?= icon('check') ?></span>Thông tin</li>
            <li class="step active" aria-current="step"><span class="dot">2</span>Thanh toán</li>
            <li class="step"><span class="dot">3</span>Hoàn tất</li>
        </ol>

        <?php if ($error): ?><div class="alert error" role="alert"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
        <?php if ($awaiting): ?>
            <div class="alert" role="status"><?= icon('clock') ?><span>Đơn <b><?= e($b['booking_code']) ?></b> đang <b><?= $b['payment_method'] === 'Tiền mặt' ? 'chờ thanh toán tại resort' : 'chờ xác nhận chuyển khoản' ?></b>. Sau khi lễ tân hoặc quản trị viên xác nhận đã nhận được tiền, đơn sẽ chuyển sang "Đã xác nhận".<?= $b['payment_method'] === 'Tiền mặt' ? ' Bạn thanh toán trực tiếp tại quầy lễ tân khi nhận phòng.' : ' Bạn có thể quét lại mã QR bên dưới nếu chưa chuyển khoản.' ?></span></div>
        <?php endif; ?>

        <form method="post" class="pay-grid">
            <?= csrf_field() ?>
            <div class="panel pay-card">
                <h2>Thông tin đặt phòng</h2>
                <div class="stay">
                    <img src="<?= img($b['image']) ?>" alt="">
                    <div>
                        <h3><?= e($b['name']) ?></h3>
                        <p><?= icon('calendar-days') ?><?= $nights ?> đêm (<?= date_vn($b['check_in'], 'd/m') ?> - <?= date_vn($b['check_out'], 'd/m') ?>)</p>
                        <p><?= icon('user') ?><?= (int)$b['guests'] ?> người</p>
                    </div>
                </div>
                <div class="bill">
                    <div class="bill-row"><span>Tiền phòng (<?= $nights ?> đêm)</span><b><?= money($b['room_total']) ?></b></div>
                </div>
                <?php if ($extras): ?>
                <div class="bill">
                    <h4>Dịch vụ thêm</h4>
                    <?php foreach ($extras as $x): ?>
                        <div class="bill-row"><span><?= icon('sparkles') ?><?= e($x['name']) ?><?= $x['quantity'] > 1 ? ' × ' . (int)$x['quantity'] : '' ?></span><b><?= money($x['total']) ?></b></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($b['discount_amount']) && $b['discount_amount'] > 0): ?>
                <div class="bill"><div class="bill-row"><span><?= icon('ticket-percent') ?>Khuyến mãi <?= e($b['promo_code']) ?></span><b style="color:var(--ok)">−<?= money($b['discount_amount']) ?></b></div></div>
                <?php endif; ?>
                <div class="bill-total"><span>Tổng cộng</span><span class="price"><?= money($b['grand_total']) ?></span></div>
            </div>

            <div class="panel pay-card">
                <h2>Phương thức thanh toán</h2>
                <div class="pay-options" role="radiogroup" aria-label="Phương thức thanh toán">
                    <?php foreach ($methods as $value => [$cls, $title, $desc]): ?>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="<?= e($value) ?>"<?= $selected === $value ? ' checked' : '' ?>>
                        <span class="radio"></span>
                        <span class="pay-logo <?= $cls ?>"><?php
                            if ($cls === 'cash') echo icon('banknote');
                            else echo icon('landmark'); ?></span>
                        <span><b><?= e($title) ?></b><small><?= e($desc) ?></small></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="bank-qr-panel" data-show-for="Chuyển khoản" style="<?= $selected === 'Chuyển khoản' ? '' : 'display:none' ?>">
                    <?php if ($bankQrSvg): ?>
                        <div class="qr-box"><?= $bankQrSvg ?></div>
                        <div class="qr-info">
                            <p>Quét mã bằng app ngân hàng bất kỳ (hỗ trợ VietQR) để tự điền sẵn số tài khoản và số tiền.</p>
                            <dl class="dl">
                                <dt>Ngân hàng</dt><dd><?= e(vietqr_bank_list()[setting('bank_bin', '970436')] ?? setting('bank_bin', '')) ?></dd>
                                <dt>Số tài khoản</dt><dd><b><?= e($bankAccountNo) ?></b></dd>
                                <dt>Chủ tài khoản</dt><dd><?= e(setting('bank_account_name', setting('resort_name', 'BlueSea Resort'))) ?></dd>
                                <dt>Số tiền</dt><dd><b><?= money($b['grand_total']) ?></b></dd>
                                <dt>Nội dung</dt><dd>TT <?= e($b['booking_code']) ?></dd>
                            </dl>
                            <p class="muted" style="font-size:12.5px">Sau khi chuyển khoản, bấm "Thanh toán ngay" để gửi yêu cầu. Đơn sẽ được xác nhận khi lễ tân hoặc quản trị viên kiểm tra và nhận được tiền.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert error"><?= icon('circle-x') ?><span>Resort chưa cấu hình số tài khoản nhận chuyển khoản. Vui lòng chọn phương thức khác hoặc liên hệ lễ tân.</span></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary btn-block" type="submit" style="height:50px;margin-top:22px;font-size:15px">Thanh toán ngay</button>
                <p class="muted" style="font-size:12.5px;margin-top:10px;text-align:center">Dù chọn tiền mặt hay chuyển khoản, đơn chỉ được xác nhận sau khi nhân viên resort xác nhận đã nhận tiền.</p>
            </div>
        </form>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
