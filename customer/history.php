<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_customer();
expire_stale_bookings($pdo);
$page_title = 'Lịch sử đặt phòng';
$account_active = 'history';

$stmt = $pdo->prepare("SELECT b.*, rt.name, rt.image FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id WHERE b.customer_id=? ORDER BY b.created_at DESC");
$stmt->execute([$_SESSION['customer']['id']]);
$items = $stmt->fetchAll();

$tabs = [
    'all'         => 'Tất cả',
    'confirmed'   => 'Đã xác nhận',
    'checked_in'  => 'Đang lưu trú',
    'checked_out' => 'Đã trả phòng',
    'cancelled'   => 'Đã hủy',
];
$active = $_GET['tab'] ?? 'all';
if (!isset($tabs[$active])) $active = 'all';
$filtered = $active === 'all' ? $items : array_values(array_filter($items, function ($b) use ($active) { return $b['status'] === $active; }));

include '../includes/header.php';
?>
<?php include '../includes/account_top.php'; ?>
<h1>Lịch sử đặt phòng</h1>
<?php if (isset($_GET['success'])): ?>
    <div class="alert success" role="status" style="margin-top:16px"><?= icon('circle-check') ?><span>Đặt phòng <b><?= e($_GET['success']) ?></b> đã được xác nhận và thanh toán thành công.</span></div>
<?php endif; ?>
<?php if (isset($_GET['awaiting'])):
    $awMethod = '';
    foreach ($items as $it) { if ($it['booking_code'] === $_GET['awaiting']) { $awMethod = $it['payment_method']; break; } } ?>
    <div class="alert" role="status" style="margin-top:16px"><?= icon('clock') ?><span>Đã ghi nhận <?= $awMethod === 'Tiền mặt' ? 'phương thức thanh toán tiền mặt tại resort' : 'yêu cầu chuyển khoản' ?> cho đơn <b><?= e($_GET['awaiting']) ?></b>. Đơn sẽ được xác nhận sau khi lễ tân hoặc quản trị viên xác nhận đã nhận được tiền.</span></div>
<?php endif; ?>

<div class="tabs" role="tablist" aria-label="Lọc theo trạng thái">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab<?= $active === $key ? ' active' : '' ?>" href="?tab=<?= $key ?>" role="tab" aria-selected="<?= $active === $key ? 'true' : 'false' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$filtered): ?>
    <div class="panel empty">
        <?= icon('file-text') ?>
        <p><b>Chưa có đơn đặt phòng nào</b></p>
        <p>Hãy bắt đầu kỳ nghỉ tiếp theo của bạn tại BlueSea Resort.</p>
        <p style="margin-top:14px"><a class="btn btn-primary btn-small" href="<?= BASE_URL ?>/rooms.php">Xem phòng nghỉ</a></p>
    </div>
<?php else: foreach ($filtered as $b):
    [$label, $tone] = booking_status_for($b);
    $nights = nights_between($b['check_in'], $b['check_out']); ?>
    <div class="history-card">
        <img src="<?= img($b['image']) ?>" alt="">
        <div class="history-body">
            <div class="history-top">
                <div>
                    <h3><?= e($b['name']) ?></h3>
                    <p class="dates"><?= date_vn($b['check_in']) ?> - <?= date_vn($b['check_out']) ?> · <?= $nights ?> đêm</p>
                </div>
                <span class="badge badge-<?= $tone ?>"><?= $label ?></span>
            </div>
            <div class="history-foot">
                <div>
                    <div class="price"><?= money($b['grand_total']) ?></div>
                    <small>Mã đơn: <?= e($b['booking_code']) ?></small>
                </div>
                <?php if ($b['status'] === 'pending' || ($b['payment_status'] === 'unpaid' && $b['status'] !== 'cancelled')): ?>
                    <a class="btn btn-primary btn-small" href="<?= BASE_URL ?>/payment.php?id=<?= (int)$b['id'] ?>"><?= booking_awaiting_payment($b) ? 'Xem thông tin thanh toán' : 'Thanh toán' ?></a>
                <?php else: ?>
                    <div style="display:flex;gap:8px">
                    <?php if ($b['status'] === 'checked_out' && db_is_v2()): ?><a class="btn btn-outline btn-small" href="<?= BASE_URL ?>/customer/reviews.php">Đánh giá</a><?php endif; ?>
                    <button type="button" class="btn btn-outline btn-small" data-open-dialog="dlg-<?= (int)$b['id'] ?>">Xem chi tiết</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <dialog id="dlg-<?= (int)$b['id'] ?>">
        <div class="dialog-head"><h3>Chi tiết đơn <?= e($b['booking_code']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
        <div class="dialog-body">
            <dl class="dl">
                <dt>Loại phòng</dt><dd><?= e($b['name']) ?></dd>
                <dt>Nhận / trả phòng</dt><dd><?= date_vn($b['check_in']) ?> - <?= date_vn($b['check_out']) ?> (<?= $nights ?> đêm)</dd>
                <dt>Số khách</dt><dd><?= (int)$b['guests'] ?> người</dd>
                <dt>Trạng thái</dt><dd><span class="badge badge-<?= $tone ?>"><?= $label ?></span></dd>
                <dt>Thanh toán</dt><dd><?= e($b['payment_method']) ?><?= booking_awaiting_payment($b) ? ' — chờ xác nhận đã nhận tiền' : '' ?></dd>
            </dl>
            <div class="bill-row"><span>Tiền phòng</span><b><?= money($b['room_total']) ?></b></div>
            <div class="bill-row"><span>Dịch vụ thêm</span><b><?= money($b['service_total']) ?></b></div>
            <?php if (!empty($b['discount_amount']) && $b['discount_amount'] > 0): ?>
            <div class="bill-row"><span>Khuyến mãi <?= e($b['promo_code']) ?></span><b style="color:var(--ok)">−<?= money($b['discount_amount']) ?></b></div>
            <?php endif; ?>
            <div class="bill-total"><span>Tổng cộng</span><span class="price"><?= money($b['grand_total']) ?></span></div>
        </div>
    </dialog>
<?php endforeach; endif; ?>
<?php include '../includes/account_bottom.php'; ?>
<?php include '../includes/footer.php'; ?>
