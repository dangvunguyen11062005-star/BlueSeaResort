<?php
/* Khung tài khoản khách hàng: sidebar + nội dung. Cần $account_active = history|profile|password */
$acc = $_SESSION['customer'];
$acc_items = [
    ['index.php', 'Trang chủ', 'house', 'home', BASE_URL . '/index.php'],
    ['rooms.php', 'Đặt phòng', 'calendar-days', 'book', BASE_URL . '/rooms.php'],
    ['history', 'Lịch sử đặt phòng', 'file-text', 'history', BASE_URL . '/customer/history.php'],
    ['reviews', 'Đánh giá của tôi', 'star', 'reviews', BASE_URL . '/customer/reviews.php'],
    ['profile', 'Thông tin cá nhân', 'user-round', 'profile', BASE_URL . '/customer/profile.php'],
    ['password', 'Đổi mật khẩu', 'key-round', 'password', BASE_URL . '/customer/password.php'],
];
?>
<div class="account-shell">
    <aside class="account-side">
        <div class="profile-mini">
            <span class="avatar"><?= e(initials($acc['name'])) ?></span>
            <div><b><?= e($acc['name']) ?></b><small>Khách hàng</small></div>
        </div>
        <nav class="side-menu" aria-label="Menu tài khoản">
            <?php foreach ($acc_items as [, $label, $ic, $key, $href]): ?>
                <?php if ($href === null): ?>
                    <span class="soon" title="Chức năng đang phát triển"><?= icon($ic) ?><?= $label ?><span class="tag">Sắp có</span></span>
                <?php else: ?>
                    <a href="<?= $href ?>"<?= ($account_active ?? '') === $key ? ' class="active" aria-current="page"' : '' ?>><?= icon($ic) ?><?= $label ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="sep"></div>
            <a href="<?= BASE_URL ?>/logout.php"><?= icon('log-out') ?>Đăng xuất</a>
        </nav>
    </aside>
    <section class="account-main">
