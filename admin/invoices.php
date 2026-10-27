<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$method = $_GET['method'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$validDate = function ($d) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; };
if (!$validDate($from)) $from = '';
if (!$validDate($to)) $to = '';
$methods = ['Tiền mặt', 'Chuyển khoản', 'VNPay', 'MoMo'];
if (!in_array($method, $methods, true)) $method = '';

$where = []; $params = [];
if ($q !== '') {
    $where[] = '(b.booking_code LIKE ? OR c.name LIKE ? OR CONCAT("HD", LPAD(p.id,5,"0")) LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($method !== '') { $where[] = 'p.method = ?'; $params[] = $method; }
if ($from !== '') { $where[] = 'DATE(p.paid_at) >= ?'; $params[] = $from; }
if ($to !== '') { $where[] = 'DATE(p.paid_at) <= ?'; $params[] = $to; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$base = "FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN customers c ON c.id=b.customer_id
         JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id" . $w;

$sum = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(p.amount),0) total $base");
$sum->execute($params);
$totals = $sum->fetch();

$perPage = 15;
$pages = max(1, (int)ceil($totals['n'] / $perPage));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$st = $pdo->prepare("SELECT p.*, b.booking_code, c.name customer, rt.name room_name $base ORDER BY p.paid_at DESC, p.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$st->execute($params);
$items = $st->fetchAll();

$qs = function ($p) use ($q, $method, $from, $to) {
    return '?' . http_build_query(array_filter(['q' => $q, 'method' => $method, 'from' => $from, 'to' => $to, 'page' => $p], function ($v) { return $v !== '' && $v !== null; }));
};

$page_title = 'Quản lý hóa đơn';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('receipt') ?>Quản lý hóa đơn</h1><p>Các giao dịch thanh toán đã ghi nhận</p></div>
</div>

<div class="stat-cards" style="grid-template-columns:repeat(2,1fr);max-width:640px">
    <div class="stat"><div><small>Số hóa đơn</small><strong><?= (int)$totals['n'] ?></strong></div><span class="ico ico-blue"><?= icon('receipt') ?></span></div>
    <div class="stat"><div><small>Tổng tiền thu được</small><strong style="font-size:24px"><?= money($totals['total']) ?></strong></div><span class="ico ico-green"><?= icon('landmark') ?></span></div>
</div>

<form class="toolbar" method="get">
    <div class="search"><?= icon('search') ?><input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Mã hóa đơn, mã đơn hoặc tên khách..."></div>
    <select class="input" name="method" style="flex:0 0 180px;min-width:0">
        <option value="">Mọi phương thức</option>
        <?php foreach ($methods as $m): ?><option<?= $method === $m ? ' selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
    </select>
    <input class="input" type="date" name="from" value="<?= e($from) ?>" style="flex:0 0 160px" aria-label="Từ ngày">
    <input class="input" type="date" name="to" value="<?= e($to) ?>" style="flex:0 0 160px" aria-label="Đến ngày">
    <button class="btn btn-primary" type="submit">Lọc</button>
</form>

<div class="table-wrap">
    <table class="table">
        <tr><th>Mã hóa đơn</th><th>Ngày thanh toán</th><th>Khách hàng</th><th>Phòng</th><th>Phương thức</th><th class="right">Số tiền</th><th>Thao tác</th></tr>
        <?php foreach ($items as $p): ?>
        <tr>
            <td><b>HD<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></b><br><small class="muted"><?= e($p['booking_code']) ?></small></td>
            <td><?= date_vn($p['paid_at'], 'd/m/Y H:i') ?></td>
            <td><?= e($p['customer']) ?></td>
            <td><?= e($p['room_name']) ?></td>
            <td><?= e($p['method']) ?></td>
            <td class="right"><b><?= money($p['amount']) ?></b></td>
            <td class="actions"><a class="act" title="Xem / in hóa đơn" href="invoice.php?id=<?= (int)$p['id'] ?>"><?= icon('file-text') ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">Không có hóa đơn phù hợp.</td></tr><?php endif; ?>
    </table>
</div>

<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Phân trang">
    <?php if ($page > 1): ?><a href="<?= $qs($page - 1) ?>" aria-label="Trang trước"><?= icon('chevron-left') ?></a><?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?><span class="cur" aria-current="page"><?= $i ?></span><?php elseif ($i === 1 || $i === $pages || abs($i - $page) <= 1): ?><a href="<?= $qs($i) ?>"><?= $i ?></a><?php elseif (abs($i - $page) === 2): ?><span class="off">…</span><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $pages): ?><a href="<?= $qs($page + 1) ?>" aria-label="Trang sau"><?= icon('chevron-right') ?></a><?php endif; ?>
</nav>
<?php endif; ?>
<?php include '../includes/admin_footer.php'; ?>
