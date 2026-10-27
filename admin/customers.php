<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$where = ''; $params = [];
if ($q !== '') { $where = 'WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
$sql = "SELECT c.*, COUNT(b.id) total_bookings, COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.grand_total ELSE 0 END),0) total_spent
        FROM customers c LEFT JOIN bookings b ON b.customer_id=c.id $where GROUP BY c.id ORDER BY c.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$page_title = 'Quản lý khách hàng';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('users') ?>Quản lý khách hàng</h1><p>Danh sách khách hàng đã đăng ký</p></div>
</div>
<form class="toolbar" method="get">
    <div class="search"><?= icon('search') ?><input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên, email hoặc SĐT..."></div>
    <button class="btn btn-outline" type="submit">Tìm</button>
</form>
<div class="table-wrap">
    <table class="table">
        <tr><th>Khách hàng</th><th>Email</th><th>Điện thoại</th><th>Số đơn</th><th>Tổng chi tiêu</th><th>Ngày tham gia</th></tr>
        <?php foreach ($items as $c): ?>
        <tr>
            <td style="display:flex;align-items:center;gap:10px"><span class="avatar" style="width:34px;height:34px;font-size:14px"><?= e(initials($c['name'])) ?></span><?= e($c['name']) ?></td>
            <td><?= e($c['email']) ?></td>
            <td><?= e($c['phone']) ?></td>
            <td class="center"><?= (int)$c['total_bookings'] ?></td>
            <td><?= money($c['total_spent']) ?></td>
            <td><?= date_vn($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">Không có khách hàng phù hợp.</td></tr><?php endif; ?>
    </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
