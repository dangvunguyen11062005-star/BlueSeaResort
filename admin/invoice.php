<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT p.*, b.*, p.id payment_id, p.amount paid_amount, p.method pay_method, c.name customer, c.phone, c.email, rt.name room_name, r.room_code
    FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN customers c ON c.id=b.customer_id
    JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id WHERE p.id=?");
$st->execute([$id]);
$inv = $st->fetch();
if (!$inv) { flash_set('Không tìm thấy hóa đơn.', 'error'); redirect(BASE_URL . '/admin/invoices.php'); }

$sv = $pdo->prepare("SELECT s.name, s.unit, bs.quantity, bs.total FROM booking_services bs JOIN services s ON s.id=bs.service_id WHERE bs.booking_id=?");
$sv->execute([$inv['booking_id']]);
$extras = $sv->fetchAll();
$nights = nights_between($inv['check_in'], $inv['check_out']);
$discount = db_is_v2() ? (float)($inv['discount_amount'] ?? 0) : 0;

$page_title = 'Hóa đơn HD' . str_pad($inv['payment_id'], 5, '0', STR_PAD_LEFT);
include '../includes/admin_header.php';
?>
<div class="admin-head no-print">
    <div><h1><?= icon('receipt') ?>Chi tiết hóa đơn</h1></div>
    <div style="display:flex;gap:10px">
        <a class="btn btn-outline" href="invoices.php"><?= icon('chevron-left') ?>Quay lại</a>
        <button class="btn btn-primary" type="button" data-print>In hóa đơn</button>
    </div>
</div>

<article class="invoice">
    <header class="invoice-head">
        <div>
            <div class="brand"><img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name"><?= e(setting('resort_name', 'BlueSea Resort')) ?></span></div>
            <p><?= e(setting('address', 'Mũi Né, Phan Thiết, Bình Thuận')) ?></p>
            <p><?= e(setting('phone', '')) ?> · <?= e(setting('email', '')) ?></p>
        </div>
        <div class="invoice-no">
            <h2>HÓA ĐƠN</h2>
            <p><b>HD<?= str_pad($inv['payment_id'], 5, '0', STR_PAD_LEFT) ?></b></p>
            <p class="muted">Ngày: <?= date_vn($inv['paid_at'], 'd/m/Y H:i') ?></p>
        </div>
    </header>

    <div class="invoice-cols">
        <div>
            <h3>Khách hàng</h3>
            <p><b><?= e($inv['customer']) ?></b></p>
            <p><?= e($inv['phone']) ?></p>
            <p><?= e($inv['email']) ?></p>
        </div>
        <div>
            <h3>Thông tin lưu trú</h3>
            <p>Mã đơn: <b><?= e($inv['booking_code']) ?></b></p>
            <p><?= e($inv['room_name']) ?> (phòng <?= e($inv['room_code']) ?>)</p>
            <p><?= date_vn($inv['check_in']) ?> → <?= date_vn($inv['check_out']) ?> · <?= $nights ?> đêm · <?= (int)$inv['guests'] ?> khách</p>
        </div>
    </div>

    <table class="table invoice-table">
        <tr><th>Nội dung</th><th class="center">Số lượng</th><th class="right">Thành tiền</th></tr>
        <tr><td>Tiền phòng <?= e($inv['room_name']) ?></td><td class="center"><?= $nights ?> đêm</td><td class="right"><?= money($inv['room_total']) ?></td></tr>
        <?php foreach ($extras as $x): ?>
            <tr><td><?= e($x['name']) ?></td><td class="center"><?= (int)$x['quantity'] ?> <?= e($x['unit']) ?></td><td class="right"><?= money($x['total']) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($discount > 0): ?>
            <tr><td>Khuyến mãi <?= e($inv['promo_code']) ?></td><td class="center">—</td><td class="right">−<?= money($discount) ?></td></tr>
        <?php endif; ?>
        <tr class="total"><td colspan="2">Tổng thanh toán</td><td class="right"><?= money($inv['paid_amount']) ?></td></tr>
    </table>

    <p class="muted" style="margin-top:14px">Phương thức thanh toán: <b><?= e($inv['pay_method']) ?></b> · Trạng thái: <b><?= ($inv['payment_status'] ?? '') === 'refunded' ? 'Đã hoàn tiền (đơn đã hủy)' : 'Đã thanh toán' ?></b></p>
    <p class="muted" style="margin-top:22px;text-align:center">Cảm ơn quý khách đã lựa chọn <?= e(setting('resort_name', 'BlueSea Resort')) ?>!</p>
</article>
<?php include '../includes/admin_footer.php'; ?>
