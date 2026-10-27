<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_customer();
$page_title = 'Đánh giá của tôi';
$account_active = 'reviews';
$cid = (int)$_SESSION['customer']['id'];
$ready = db_is_v2();

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $bid = (int)($_POST['booking_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $chk = $pdo->prepare("SELECT b.id, r.room_type_id FROM bookings b JOIN rooms r ON r.id=b.room_id
        WHERE b.id=? AND b.customer_id=? AND b.status='checked_out' AND NOT EXISTS (SELECT 1 FROM reviews rv WHERE rv.booking_id=b.id)");
    $chk->execute([$bid, $cid]);
    $b = $chk->fetch();
    if (!$b) {
        flash_set('Không thể đánh giá đơn này (chưa trả phòng hoặc đã được đánh giá).', 'error');
    } elseif ($rating < 1 || $rating > 5) {
        flash_set('Vui lòng chọn số sao từ 1 đến 5.', 'error');
    } else {
        $pdo->prepare("INSERT INTO reviews(booking_id,customer_id,room_type_id,rating,comment,status) VALUES(?,?,?,?,?, 'pending')")
            ->execute([$bid, $cid, $b['room_type_id'], $rating, mb_substr($comment, 0, 1000)]);
        flash_set('Cảm ơn bạn! Đánh giá đã được gửi và sẽ hiển thị sau khi được duyệt.');
    }
    redirect(BASE_URL . '/customer/reviews.php');
}

$toReview = $mine = [];
if ($ready) {
    $q = $pdo->prepare("SELECT b.id, b.booking_code, b.check_in, b.check_out, rt.name, rt.image FROM bookings b
        JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id
        WHERE b.customer_id=? AND b.status='checked_out' AND NOT EXISTS (SELECT 1 FROM reviews rv WHERE rv.booking_id=b.id)
        ORDER BY b.check_out DESC LIMIT 20");
    $q->execute([$cid]);
    $toReview = $q->fetchAll();

    $m = $pdo->prepare("SELECT rv.*, rt.name, rt.image FROM reviews rv JOIN room_types rt ON rt.id=rv.room_type_id WHERE rv.customer_id=? ORDER BY rv.created_at DESC");
    $m->execute([$cid]);
    $mine = $m->fetchAll();
}
$flash = flash_get();
include '../includes/header.php';
?>
<?php include '../includes/account_top.php'; ?>
<h1>Đánh giá của tôi</h1>
<?php if ($flash): ?><div class="alert <?= $flash[0] === 'error' ? 'error' : 'success' ?>" role="status" style="margin-top:16px"><?= icon($flash[0] === 'error' ? 'circle-x' : 'circle-check') ?><span><?= e($flash[1]) ?></span></div><?php endif; ?>
<?php if (!$ready): ?><div class="alert error" style="margin-top:16px"><?= icon('circle-x') ?><span>Chức năng đánh giá chưa sẵn sàng. Quản trị viên cần nâng cấp cơ sở dữ liệu.</span></div><?php endif; ?>

<h2 class="form-title" style="margin-top:24px">Kỳ nghỉ chờ đánh giá</h2>
<?php if (!$toReview): ?>
    <div class="panel empty" style="padding:30px"><?= icon('star') ?><p>Bạn chưa có kỳ nghỉ nào cần đánh giá.</p></div>
<?php else: foreach ($toReview as $b): ?>
    <div class="history-card">
        <img src="<?= img($b['image']) ?>" alt="">
        <div class="history-body">
            <h3><?= e($b['name']) ?></h3>
            <p class="dates"><?= date_vn($b['check_in']) ?> - <?= date_vn($b['check_out']) ?> · Mã đơn <?= e($b['booking_code']) ?></p>
            <div class="history-foot"><span></span><button type="button" class="btn btn-primary btn-small" data-open-dialog="dlg-rv-<?= (int)$b['id'] ?>">Viết đánh giá</button></div>
        </div>
    </div>
    <dialog id="dlg-rv-<?= (int)$b['id'] ?>">
        <div class="dialog-head"><h3>Đánh giá <?= e($b['name']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
        <form method="post" class="dialog-body">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
            <span class="label">Bạn thấy kỳ nghỉ thế nào?</span>
            <div class="rate-input" role="radiogroup" aria-label="Số sao">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="rate-<?= (int)$b['id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>"<?= $i === 5 ? ' required' : '' ?>>
                    <label for="rate-<?= (int)$b['id'] ?>-<?= $i ?>" title="<?= $i ?> sao"><?= icon('star') ?><span class="sr-only"><?= $i ?> sao</span></label>
                <?php endfor; ?>
            </div>
            <div class="form-group" style="margin-top:14px"><label for="cm-<?= (int)$b['id'] ?>">Nhận xét (không bắt buộc)</label><textarea class="input" id="cm-<?= (int)$b['id'] ?>" name="comment" rows="4" maxlength="1000" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea></div>
            <button class="btn btn-primary btn-block">Gửi đánh giá</button>
        </form>
    </dialog>
<?php endforeach; endif; ?>

<h2 class="form-title" style="margin-top:28px">Đánh giá đã gửi</h2>
<?php if (!$mine): ?>
    <p class="muted">Bạn chưa gửi đánh giá nào.</p>
<?php else: foreach ($mine as $r): [$label, $tone] = review_status($r['status']); ?>
    <div class="review-item">
        <div class="review-top">
            <div><b><?= e($r['name']) ?></b> <?= rating_stars($r['rating']) ?></div>
            <span class="badge badge-<?= $tone ?>"><?= $label ?></span>
        </div>
        <?php if ($r['comment'] !== ''): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
        <small class="muted"><?= date_vn($r['created_at']) ?></small>
    </div>
<?php endforeach; endif; ?>
<?php include '../includes/account_bottom.php'; ?>
<?php include '../includes/footer.php'; ?>
