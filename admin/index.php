<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../includes/charts.php';
$page_title = 'Dashboard';

$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupied = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$todayIn = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE check_in=CURDATE() AND status<>'cancelled'")->fetchColumn();
$monthRevenue = (float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM bookings WHERE payment_status='paid' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetchColumn();

// Doanh thu 7 ngày gần nhất
$rows = $pdo->query("SELECT DATE(created_at) d, SUM(grand_total) t FROM bookings WHERE payment_status='paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d")->fetchAll(PDO::FETCH_KEY_PAIR);
$labels = []; $values = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $labels[] = date('d/m', strtotime($d));
    $values[] = (float)($rows[$d] ?? 0);
}

// Loại phòng được đặt nhiều nhất (theo số lượt đặt, không tính đã hủy)
$typeCounts = $pdo->query("SELECT rt.name, COUNT(*) n FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id WHERE b.status<>'cancelled' GROUP BY rt.id ORDER BY n DESC")->fetchAll();
$totalBk = array_sum(array_column($typeCounts, 'n')) ?: 1;
$colors = ['#1e9bfe', '#7cd8c9', '#f0a25b', '#f5c04a', '#9fbdd2'];
$percents = []; $legendItems = [];
$shown = array_slice($typeCounts, 0, 4);
$restN = array_sum(array_column($typeCounts, 'n')) - array_sum(array_column($shown, 'n'));
foreach ($shown as $t) { $p = round($t['n'] / $totalBk * 100, 1); $percents[] = $p; $legendItems[] = [$t['name'], $p]; }
if ($restN > 0) { $p = round($restN / $totalBk * 100, 1); $percents[] = $p; $legendItems[] = ['Khác', $p]; }

$page_title = 'Dashboard';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('layout-dashboard') ?>Xin chào, <?= e($_SESSION['admin']['name']) ?>!</h1><p>Tổng quan hoạt động của BlueSea Resort</p></div>
    <span class="pill"><?= icon('calendar-days') ?><?= weekday_vn() ?>, <?= date_vn(date('Y-m-d')) ?></span>
</div>

<div class="stat-cards">
    <div class="stat"><div><small>Tổng số phòng</small><strong><?= $totalRooms ?></strong></div><span class="ico ico-blue"><?= icon('bed-double') ?></span></div>
    <div class="stat"><div><small>Đang thuê</small><strong><?= $occupied ?></strong></div><span class="ico ico-orange"><?= icon('key-round') ?></span></div>
    <div class="stat"><div><small>Đặt hôm nay</small><strong><?= $todayIn ?></strong></div><span class="ico ico-purple"><?= icon('calendar-check') ?></span></div>
    <div class="stat"><div><small>Doanh thu tháng</small><strong style="font-size:22px"><?= money($monthRevenue) ?></strong></div><span class="ico ico-green"><?= icon('landmark') ?></span></div>
</div>

<div class="chart-grid">
    <div class="chart-panel">
        <h2>Doanh thu 7 ngày gần đây</h2>
        <?= line_chart_svg($labels, $values) ?>
    </div>
    <div class="chart-panel">
        <h2>Loại phòng được đặt nhiều nhất</h2>
        <div class="pie-wrap">
            <div class="pie" style="background:<?= pie_gradient($percents, $colors) ?>"></div>
            <ul class="legend">
                <?php foreach ($legendItems as $i => [$name, $p]): ?>
                    <li><i style="background:<?= $colors[$i % count($colors)] ?>"></i><?= e($name) ?><b><?= $p ?>%</b></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php include '../includes/admin_footer.php'; ?>
