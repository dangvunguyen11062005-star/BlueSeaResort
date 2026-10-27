<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$cur = basename($_SERVER['SCRIPT_NAME']);
$css_v = @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1;
// mục thứ 4 (true/false) = chỉ Administrator mới được dùng (RBAC); Staff thấy nhưng không bấm được
$admin_nav = [
    ['index.php', 'Dashboard', 'layout-dashboard', false],
    ['rooms.php', 'Quản lý phòng', 'bed-double', false],
    ['room-types.php', 'Quản lý loại phòng', 'layers', true],
    ['bookings.php', 'Quản lý đặt phòng', 'calendar-check', false],
    ['customers.php', 'Quản lý khách hàng', 'users', false],
    ['services.php', 'Quản lý dịch vụ', 'martini', true],
    ['invoices.php', 'Quản lý hóa đơn', 'receipt', false],
    ['promotions.php', 'Khuyến mãi', 'ticket-percent', true],
    ['reviews.php', 'Đánh giá', 'message-square', false],
    ['statistics.php', 'Thống kê', 'chart-line', false],
    ['settings.php', 'Cài đặt', 'settings', true],
];
// trang con vẫn làm sáng mục cha
$admin_alias = ['invoice.php' => 'invoices.php'];
$cur = $admin_alias[$cur] ?? $cur;
$isStaff = is_admin_role('staff');
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? 'Admin - BlueSea Resort') ?> · Admin</title>
<link rel="icon" type="image/svg+xml" href="<?= img('logo.svg') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $css_v ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
<aside class="sidebar">
    <a class="brand" href="<?= BASE_URL ?>/admin/index.php">
        <img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name">BlueSea Resort</span>
    </a>
    <nav aria-label="Menu quản trị">
        <?php foreach ($admin_nav as [$file, $label, $ic, $adminOnly]): ?>
            <?php if ($adminOnly && $isStaff): ?>
                <span class="soon" title="Chỉ Administrator mới có quyền truy cập"><?= icon('lock') ?><?= $label ?></span>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/<?= $file ?>"<?= $cur === $file ? ' class="active" aria-current="page"' : '' ?>><?= icon($ic) ?><?= $label ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="side-foot">
        <p class="role-tag"><?= icon('user-round') ?><?= e($_SESSION['admin']['name']) ?> · <b><?= $isStaff ? 'Staff' : 'Administrator' ?></b></p>
        <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener"><?= icon('external-link') ?>Xem website</a>
        <a href="<?= BASE_URL ?>/admin/logout.php"><?= icon('log-out') ?>Đăng xuất</a>
    </div>
</aside>
<section class="admin-main">
<div class="admin-content">
<?php if (function_exists('db_is_v2') && !db_is_v2()): ?>
    <div class="alert error" role="alert"><?= icon('circle-x') ?><span><b>Cơ sở dữ liệu chưa được nâng cấp.</b> Vào phpMyAdmin, chọn database <code>bluesea_resort</code> → Import file <code>database/upgrade_v2.sql</code> để dùng đủ chức năng Loại phòng, Hóa đơn, Khuyến mãi, Đánh giá, Cài đặt.</span></div>
<?php endif; ?>
<?php if ($flash = flash_get()): ?>
    <div class="alert <?= $flash[0] === 'error' ? 'error' : 'success' ?>" role="status"><?= icon($flash[0] === 'error' ? 'circle-x' : 'circle-check') ?><span><?= e($flash[1]) ?></span></div>
<?php endif; ?>
