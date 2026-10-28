<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin(); // cả Administrator lẫn Staff (lễ tân) đều được xác nhận tiền chuyển khoản

/* Xác nhận đã nhận tiền (Tiền mặt tại resort hoặc Chuyển khoản): đơn mới thật sự "đã thanh toán + đã xác nhận" và mới có hóa đơn/doanh thu.
   UPDATE có điều kiện nên chỉ một lần bấm thành công - bấm lại hoặc hai người cùng bấm sẽ không tạo trùng hóa đơn. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_payment') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo->beginTransaction();
        $up = $pdo->prepare("UPDATE bookings SET payment_status='paid', status='confirmed'
                             WHERE id=? AND payment_method IN ('Tiền mặt','Chuyển khoản') AND payment_status='unpaid' AND status='pending'");
        $up->execute([$id]);
        if ($up->rowCount() === 1) {
            $row = $pdo->prepare("SELECT grand_total, booking_code, payment_method FROM bookings WHERE id=?");
            $row->execute([$id]);
            $bk = $row->fetch();
            $pdo->prepare("INSERT INTO payments(booking_id,amount,method) VALUES(?,?,?)")->execute([$id, $bk['grand_total'], $bk['payment_method']]);
            $pdo->commit();
            flash_set('Đã xác nhận nhận tiền (' . mb_strtolower($bk['payment_method'], 'UTF-8') . ') cho đơn ' . $bk['booking_code'] . '.');
        } else {
            $pdo->rollBack();
            flash_set('Không thể xác nhận: đơn không còn ở trạng thái chờ xác nhận thanh toán (có thể đã được xác nhận hoặc đã hủy).', 'error');
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[admin][confirm_payment] ' . $ex->getMessage());
        flash_set('Có lỗi khi xác nhận thanh toán. Vui lòng thử lại.', 'error');
    }
    redirect(BASE_URL . '/admin/bookings.php' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : ''));
}

/* Đổi trạng thái đơn. Chỉ cho các bước hợp lệ (xem booking_allowed_transitions()):
   pending -> cancelled | confirmed -> checked_in / cancelled | checked_in -> checked_out. "confirmed" chỉ đến từ "Xác nhận đã nhận tiền".
   Hủy đơn ĐÃ THU TIỀN = hủy + ghi nhận hoàn tiền (payment_status='refunded', payments.status='refunded') nên đơn bị loại khỏi doanh thu;
   chỉ Administrator được làm việc này, vì việc hoàn tiền thật cho khách phải được thực hiện ngoài hệ thống. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    require_csrf();
    $id = (int)$_POST['id']; $to = (string)$_POST['status'];
    $back = BASE_URL . '/admin/bookings.php' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : '');
    try {
        $pdo->beginTransaction();
        $row = $pdo->prepare("SELECT status, payment_status, booking_code, grand_total FROM bookings WHERE id=? FOR UPDATE");
        $row->execute([$id]);
        $cur = $row->fetch();
        $allowed = booking_allowed_transitions();
        if (!$cur) {
            $pdo->rollBack();
            flash_set('Không tìm thấy đơn.', 'error');
        } elseif (!in_array($to, $allowed[$cur['status']] ?? [], true)) {
            $pdo->rollBack();
            flash_set('Không thể chuyển đơn ' . $cur['booking_code'] . ' từ "' . booking_status($cur['status'])[0] . '" sang "' . booking_status($to)[0] . '".', 'error');
        } elseif ($to === 'cancelled' && $cur['payment_status'] === 'paid') {
            if (!is_admin_role('admin')) {
                $pdo->rollBack();
                flash_set('Đơn ' . $cur['booking_code'] . ' đã thu tiền: chỉ Administrator mới được hủy và hoàn tiền.', 'error');
            } else {
                $pdo->prepare("UPDATE bookings SET status='cancelled', payment_status='refunded' WHERE id=?")->execute([$id]);
                $pdo->prepare("UPDATE payments SET status='refunded' WHERE booking_id=? AND status='success'")->execute([$id]);
                $pdo->commit();
                flash_set('Đã hủy đơn ' . $cur['booking_code'] . ' và ghi nhận HOÀN TIỀN ' . money($cur['grand_total']) . '. Đơn không còn tính vào doanh thu - hãy hoàn tiền thực tế cho khách.');
            }
        } else {
            $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$to, $id]);
            $pdo->commit();
            flash_set('Đã cập nhật trạng thái đơn.');
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[admin][status] ' . $ex->getMessage());
        flash_set('Không cập nhật được trạng thái' . (db_is_v3() ? '.' : ' (có thể cần import database/upgrade_v3.sql).'), 'error');
    }
    redirect($back);
}

$tabs = ['all' => 'Tất cả', 'pending' => 'Chờ thanh toán', 'confirmed' => 'Đã xác nhận', 'checked_in' => 'Đang lưu trú', 'checked_out' => 'Đã trả phòng', 'cancelled' => 'Đã hủy'];
$tab = $_GET['tab'] ?? 'all';
if (!isset($tabs[$tab])) $tab = 'all';
$q = trim($_GET['q'] ?? '');
expire_stale_bookings($pdo); // tự hủy đơn chờ thanh toán quá hạn trước khi hiển thị danh sách

$where = []; $params = [];
if ($tab !== 'all') { $where[] = 'b.status = ?'; $params[] = $tab; }
if ($q !== '') { $where[] = '(b.booking_code LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)'; $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]); }
$sql = "SELECT b.*, c.name customer, c.phone, rt.name room_name FROM bookings b
        JOIN customers c ON c.id=b.customer_id JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id" .
       ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY b.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$page_title = 'Quản lý đặt phòng';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('calendar-check') ?>Quản lý đặt phòng</h1><p>Theo dõi và xử lý các đơn đặt phòng</p></div>
</div>

<div class="tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab<?= $tab === $key ? ' active' : '' ?>" href="?tab=<?= $key ?><?= $q ? '&q=' . urlencode($q) : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>
<form class="toolbar" method="get">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="search"><?= icon('search') ?><input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo mã đơn, tên hoặc SĐT khách..."></div>
    <button class="btn btn-outline" type="submit">Tìm</button>
</form>

<div class="table-wrap">
    <table class="table">
        <tr><th>Mã đơn</th><th>Khách hàng</th><th>Phòng</th><th>Nhận / Trả</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($items as $b): [$label, $tone] = booking_status_for($b); ?>
        <tr>
            <td><?= e($b['booking_code']) ?></td>
            <td><?= e($b['customer']) ?><br><small class="muted"><?= e($b['phone']) ?></small></td>
            <td><?= e($b['room_name']) ?></td>
            <td><?= date_vn($b['check_in']) ?> → <?= date_vn($b['check_out']) ?></td>
            <td><?= money($b['grand_total']) ?></td>
            <td><span class="badge badge-<?= $tone ?>"><?= $label ?></span></td>
            <td class="actions">
                <?php if (booking_awaiting_payment($b)): ?>
                    <form method="post" data-confirm="Xác nhận ĐÃ NHẬN <?= e(money($b['grand_total'])) ?> (<?= e(mb_strtolower($b['payment_method'], 'UTF-8')) ?>) cho đơn <?= e($b['booking_code']) ?>? Đơn sẽ chuyển sang Đã xác nhận và được tính doanh thu."><?= csrf_field() ?><input type="hidden" name="action" value="confirm_payment"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <button class="act" type="submit" title="Xác nhận đã nhận tiền" style="color:var(--ok)"><?= icon('circle-check') ?></button></form>
                <?php endif; ?>
                <?php if ($b['status'] === 'confirmed'): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="checked_in"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <button class="act" type="submit" title="Check-in"><?= icon('key-round') ?></button></form>
                <?php elseif ($b['status'] === 'checked_in'): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="checked_out"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <button class="act" type="submit" title="Trả phòng"><?= icon('door-open') ?></button></form>
                <?php endif; ?>
                <?php $paidLocked = $b['payment_status'] === 'paid' && is_admin_role('staff'); // Staff không được hủy/hoàn tiền đơn đã thu ?>
                <?php if (in_array($b['status'], ['pending', 'confirmed'], true) && !$paidLocked): ?>
                    <form method="post" data-confirm="<?= $b['payment_status'] === 'paid' ? 'Đơn ' . e($b['booking_code']) . ' ĐÃ THU TIỀN. Hủy đơn sẽ ghi nhận HOÀN TIỀN ' . e(money($b['grand_total'])) . ' và loại đơn khỏi doanh thu. Tiếp tục?' : 'Hủy đơn ' . e($b['booking_code']) . '?' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <button class="act del" type="submit" title="Hủy đơn"><?= icon('circle-x') ?></button></form>
                <?php endif; ?>
                <button class="act" type="button" title="Chi tiết" data-open-dialog="dlg-<?= (int)$b['id'] ?>"><?= icon('file-text') ?></button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">Không có đơn phù hợp.</td></tr><?php endif; ?>
    </table>
</div>

<?php foreach ($items as $b): [$label, $tone] = booking_status_for($b); ?>
<dialog id="dlg-<?= (int)$b['id'] ?>">
    <div class="dialog-head"><h3>Đơn <?= e($b['booking_code']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <div class="dialog-body">
        <dl class="dl">
            <dt>Khách hàng</dt><dd><?= e($b['customer']) ?> — <?= e($b['phone']) ?></dd>
            <dt>Phòng</dt><dd><?= e($b['room_name']) ?></dd>
            <dt>Nhận / Trả</dt><dd><?= date_vn($b['check_in']) ?> - <?= date_vn($b['check_out']) ?></dd>
            <dt>Số khách</dt><dd><?= (int)$b['guests'] ?> người</dd>
            <dt>Trạng thái</dt><dd><span class="badge badge-<?= $tone ?>"><?= $label ?></span></dd>
            <dt>Thanh toán</dt><dd><?= e($b['payment_method']) ?> — <?= booking_awaiting_payment($b) ? 'Chờ xác nhận đã nhận tiền' : e(payment_status_label($b['payment_status'])) ?></dd>
        </dl>
        <div class="bill-row"><span>Tiền phòng</span><b><?= money($b['room_total']) ?></b></div>
        <div class="bill-row"><span>Dịch vụ thêm</span><b><?= money($b['service_total']) ?></b></div>
        <?php if (!empty($b['discount_amount']) && $b['discount_amount'] > 0): ?>
        <div class="bill-row"><span>Khuyến mãi <?= e($b['promo_code']) ?></span><b style="color:var(--ok)">−<?= money($b['discount_amount']) ?></b></div>
        <?php endif; ?>
        <div class="bill-total"><span>Tổng cộng</span><span class="price"><?= money($b['grand_total']) ?></span></div>
    </div>
</dialog>
<?php endforeach; ?>
<?php include '../includes/admin_footer.php'; ?>
