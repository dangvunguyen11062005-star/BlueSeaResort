<?php
require_once __DIR__ . '/../config/app.php';
$cur = basename($_SERVER['SCRIPT_NAME']);
$alias = ['room-detail.php' => 'rooms.php', 'booking.php' => 'rooms.php', 'payment.php' => 'rooms.php'];
$nav_active = $alias[$cur] ?? $cur;
$nav_items = [
    'index.php'      => 'Trang chủ',
    'about.php'      => 'Giới thiệu',
    'rooms.php'      => 'Phòng',
    'services.php'   => 'Dịch vụ',
    'promotions.php' => 'Khuyến mãi',
    'gallery.php'    => 'Thư viện',
    'contact.php'    => 'Liên hệ',
];
$body_class = $body_class ?? '';
$css_v = @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1;
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'BlueSea Resort') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= img('logo.svg') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $css_v ?>">
</head>
<body class="<?= e($body_class) ?>">
<?php if (empty($hide_site_header)): ?>
<header class="site-header">
    <div class="container">
        <div class="nav-bar">
            <a class="brand" href="<?= BASE_URL ?>/index.php" aria-label="BlueSea Resort - Trang chủ">
                <img src="<?= img('logo.svg') ?>" alt="">
                <span class="brand-name">BlueSea Resort</span>
            </a>
            <nav class="main-nav" id="main-nav" aria-label="Menu chính">
                <?php foreach ($nav_items as $file => $label): ?>
                    <a href="<?= BASE_URL ?>/<?= $file ?>"<?= $nav_active === $file ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="nav-actions">
                <a class="icon-btn" href="<?= BASE_URL ?>/rooms.php" aria-label="Tìm phòng"><?= icon('search') ?></a>
                <?php if (is_customer_logged_in()): ?>
                    <a class="icon-btn" href="<?= BASE_URL ?>/customer/history.php" aria-label="Tài khoản của tôi" title="Tài khoản của tôi"><?= icon('circle-user') ?></a>
                <?php else: ?>
                    <a class="icon-btn" href="<?= BASE_URL ?>/login.php" aria-label="Đăng nhập" title="Đăng nhập"><?= icon('circle-user') ?></a>
                <?php endif; ?>
                <a class="btn btn-primary btn-small" href="<?= BASE_URL ?>/rooms.php">Đặt phòng</a>
                <button class="icon-btn nav-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="main-nav"><?= icon('menu') ?></button>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>
<main>
