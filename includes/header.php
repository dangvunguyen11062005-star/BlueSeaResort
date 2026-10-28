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

/* ---- SEO / OpenGraph. Trang có thể đặt trước khi include: $page_description, $og_image, $canonical_params (mảng), $noindex ---- */
$seo_site  = setting('resort_name', 'BlueSea Resort');
$seo_title = !empty($page_title) ? $page_title . ' | ' . $seo_site : $seo_site . ' - Nghỉ dưỡng Mũi Né, Phan Thiết';
if (!empty($page_title) && stripos($page_title, $seo_site) !== false) $seo_title = $page_title; // tránh lặp tên resort
$seo_desc  = $page_description ?? (setting('tagline', 'Nghỉ dưỡng giữa thiên nhiên Phan Thiết – Mũi Né.') . ' Đặt phòng trực tuyến, nhiều ưu đãi.');
$seo_desc  = mb_strimwidth(trim(preg_replace('/\s+/u', ' ', strip_tags($seo_desc))), 0, 160, '…', 'UTF-8');
$seo_path  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$seo_query = !empty($canonical_params) ? '?' . http_build_query($canonical_params) : '';
if (BASE_URL !== '' && strpos($seo_path, BASE_URL) === 0) $seo_path = substr($seo_path, strlen(BASE_URL)); // site_url() đã gồm BASE_URL
$seo_url   = site_url() . $seo_path . $seo_query;
$seo_image = (string)($og_image ?? (site_url() . '/assets/images/og-image.png'));
// Trang riêng tư / giao dịch không cho công cụ tìm kiếm lập chỉ mục
$seo_noindex = !empty($noindex) || strpos($body_class, 'auth-page') !== false
    || in_array($cur, ['booking.php', 'payment.php'], true)
    || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/customer/') !== false;
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($seo_title) ?></title>
    <meta name="description" content="<?= e($seo_desc) ?>">
    <?php if ($seo_noindex): ?><meta name="robots" content="noindex,nofollow"><?php else: ?><link rel="canonical" href="<?= e($seo_url) ?>"><?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:site_name" content="<?= e($seo_site) ?>">
    <meta property="og:title" content="<?= e($seo_title) ?>">
    <meta property="og:description" content="<?= e($seo_desc) ?>">
    <meta property="og:url" content="<?= e($seo_url) ?>">
    <meta property="og:image" content="<?= e($seo_image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($seo_title) ?>">
    <meta name="twitter:description" content="<?= e($seo_desc) ?>">
    <meta name="twitter:image" content="<?= e($seo_image) ?>">
    <meta name="theme-color" content="#045a92">
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
