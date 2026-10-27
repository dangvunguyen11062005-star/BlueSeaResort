<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/charts.php';
require_admin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

$st = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) rev, COUNT(*) n FROM bookings WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?");
$st->execute([$from, $to]);
$curP = $st->fetch();

$prevFrom = date('Y-m-d', strtotime($from . ' -' . (strtotime($to) - strtotime($from)) / 86400 . ' day'));
$st->execute([$prevFrom, date('Y-m-d', strtotime($from . ' -1 day'))]);
$prevP = $st->fetch();
$pct = function ($a, $b) { return $b > 0 ? round(($a - $b) / $b * 100, 1) : ($a > 0 ? 100 : 0); };
$revDelta = $pct($curP['rev'], $prevP['rev']);
$bkDelta = $pct($curP['n'], $prevP['n']);

$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occRate = $totalRooms ? round(((int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn()) / $totalRooms * 100) : 0;

// Doanh thu theo tháng (12 tháng gần nhất)
$monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, SUM(grand_total) t FROM bookings WHERE payment_status='paid' GROUP BY m ORDER BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
$mLabels = []; $mValues = [];
for ($i = 11; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i month"));
    $mLabels[] = 'T' . (int)date('n', strtotime($key . '-01'));
    $mValues[] = (float)($monthly[$key] ?? 0);
}
$maxV = max($mValues ?: [1]);

$byRoom = $pdo->query("SELECT rt.name, COUNT(b.id) n FROM room_types rt JOIN rooms r ON r.room_type_id=rt.id LEFT JOIN bookings b ON b.room_id=r.id AND b.status<>'cancelled' GROUP BY rt.id ORDER BY n DESC")->fetchAll();
$totalN = array_sum(array_column($byRoom, 'n')) ?: 1;

$page_title = 'Thống kê';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('chart-line') ?>Thống kê doanh thu</h1><p>Từ <?= date_vn($from) ?> đến <?= date_vn($to) ?></p></div>
    <form method="get" class="toolbar" style="margin:0">
        <input class="input" type="date" name="from" value="<?= e($from) ?>">
        <input class="input" type="date" name="to" value="<?= e($to) ?>">
        <button class="btn btn-primary" type="submit">Xem</button>
    </form>
</div>

<div class="stat-cards" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat"><div><small>Doanh thu</small><strong style="font-size:24px"><?= money($curP['rev']) ?></strong>
        <div class="delta <?= $revDelta >= 0 ? 'up' : 'down' ?>"><?= icon($revDelta >= 0 ? 'trending-up' : 'trending-down') ?><?= abs($revDelta) ?>% <small>so với kỳ trước</small></div></div>
        <span class="ico ico-blue"><?= icon('landmark') ?></span></div>
    <div class="stat"><div><small>Số lượt đặt phòng</small><strong><?= (int)$curP['n'] ?></strong>
        <div class="delta <?= $bkDelta >= 0 ? 'up' : 'down' ?>"><?= icon($bkDelta >= 0 ? 'trending-up' : 'trending-down') ?><?= abs($bkDelta) ?>% <small>so với kỳ trước</small></div></div>
        <span class="ico ico-purple"><?= icon('calendar-check') ?></span></div>
    <div class="stat"><div><small>Công suất phòng</small><strong><?= $occRate ?>%</strong>
        <div class="delta up"><?= icon('trending-up') ?><small>hiện tại</small></div></div>
        <span class="ico ico-green"><?= icon('bed-double') ?></span></div>
</div>

<div class="chart-grid" style="grid-template-columns:1.6fr 1fr">
    <div class="chart-panel">
        <h2>Doanh thu theo tháng</h2>
        <div class="bars">
            <div class="bars-y"><span><?= axis_label($maxV) ?></span><span><?= axis_label($maxV * 0.5) ?></span><span>0</span></div>
            <div class="bars-plot">
                <?php foreach ($mValues as $i => $v): $h = $maxV > 0 ? max(2, round($v / $maxV * 190)) : 2; ?>
                    <div class="bar<?= $v <= 0 ? ' zero' : '' ?>" style="height:<?= $h ?>px" title="<?= e($mLabels[$i]) ?>: <?= money($v) ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="bars-x"><?php foreach ($mLabels as $l): ?><span><?= e($l) ?></span><?php endforeach; ?></div>
        </div>
    </div>
    <div class="promo-tile" style="--bg-img:url('<?= img('sunset.svg') ?>');background:var(--bg-img) center/cover">
        <h3>Phan Thiết</h3><p>Mũi Né</p>
    </div>
</div>

<div class="chart-panel" style="margin-top:18px">
    <div class="panel-title-row"><h2>Loại phòng được đặt nhiều nhất</h2></div>
    <ul class="rank">
        <?php foreach ($byRoom as $r): $p = round($r['n'] / $totalN * 100); ?>
        <li>
            <span><?= e($r['name']) ?></span><span><?= $p ?>%</span>
            <div class="meter"><i style="width:<?= $p ?>%"></i></div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include '../includes/admin_footer.php'; ?>
