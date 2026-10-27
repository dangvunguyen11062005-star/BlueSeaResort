<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['do'])) {
    require_csrf();
    $id = (int)$_POST['id'];
    if ($_POST['do'] === 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$id]);
        flash_set('Đã xóa đánh giá.');
    } elseif (in_array($_POST['do'], ['approved', 'hidden'], true)) {
        $pdo->prepare("UPDATE reviews SET status=? WHERE id=?")->execute([$_POST['do'], $id]);
        flash_set($_POST['do'] === 'approved' ? 'Đánh giá đã được hiển thị trên website.' : 'Đã ẩn đánh giá khỏi website.');
    }
    redirect(BASE_URL . '/admin/reviews.php' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : ''));
}

$tabs = ['all' => 'Tất cả', 'pending' => 'Chờ duyệt', 'approved' => 'Đang hiển thị', 'hidden' => 'Đã ẩn'];
$tab = $_GET['tab'] ?? 'pending';
if (!isset($tabs[$tab])) $tab = 'pending';

$counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'hidden' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) n FROM reviews GROUP BY status") as $r) { $counts[$r['status']] = (int)$r['n']; $counts['all'] += (int)$r['n']; }

$avg = $pdo->query("SELECT COALESCE(AVG(rating),0) a FROM reviews WHERE status='approved'")->fetchColumn();

$sql = "SELECT rv.*, c.name customer, rt.name room_name, b.booking_code FROM reviews rv
        JOIN customers c ON c.id=rv.customer_id JOIN room_types rt ON rt.id=rv.room_type_id JOIN bookings b ON b.id=rv.booking_id"
     . ($tab !== 'all' ? " WHERE rv.status=?" : "") . " ORDER BY rv.created_at DESC LIMIT 100";
$st = $pdo->prepare($sql);
$st->execute($tab !== 'all' ? [$tab] : []);
$items = $st->fetchAll();

$page_title = 'Đánh giá';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('message-square') ?>Đánh giá của khách</h1><p>Duyệt đánh giá trước khi hiển thị trên trang chi tiết phòng</p></div>
    <span class="pill"><?= icon('star') ?>Điểm trung bình: <b><?= number_format($avg, 1) ?>/5</b></span>
</div>

<div class="tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab<?= $tab === $key ? ' active' : '' ?>" href="?tab=<?= $key ?>"><?= $label ?> (<?= $counts[$key] ?>)</a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
    <table class="table">
        <tr><th>Khách hàng</th><th>Loại phòng</th><th>Đánh giá</th><th>Nội dung</th><th>Ngày gửi</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($items as $r): [$label, $tone] = review_status($r['status']); ?>
        <tr>
            <td><?= e($r['customer']) ?><br><small class="muted"><?= e($r['booking_code']) ?></small></td>
            <td><?= e($r['room_name']) ?></td>
            <td><?= rating_stars($r['rating']) ?></td>
            <td style="max-width:320px"><?= e($r['comment']) ?: '<span class="muted">(không có nội dung)</span>' ?></td>
            <td><?= date_vn($r['created_at']) ?></td>
            <td><span class="badge badge-<?= $tone ?>"><?= $label ?></span></td>
            <td class="actions">
                <?php if ($r['status'] !== 'approved'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="approved"><input type="hidden" name="tab" value="<?= e($tab) ?>"><button class="act" type="submit" title="Duyệt và hiển thị"><?= icon('circle-check') ?></button></form><?php endif; ?>
                <?php if ($r['status'] !== 'hidden'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="hidden"><input type="hidden" name="tab" value="<?= e($tab) ?>"><button class="act" type="submit" title="Ẩn"><?= icon('circle-x') ?></button></form><?php endif; ?>
                <form method="post" data-confirm="Xóa vĩnh viễn đánh giá này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="tab" value="<?= e($tab) ?>"><button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">Không có đánh giá nào ở mục này.</td></tr><?php endif; ?>
    </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
