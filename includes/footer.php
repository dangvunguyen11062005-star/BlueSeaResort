</main>
<?php if (empty($hide_footer)): ?>
<footer class="footer">
    <div class="container footer-grid">
        <div>
            <div class="brand"><img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name">BlueSea Resort</span></div>
            <p><?= e(setting('tagline', 'Nghỉ dưỡng giữa thiên nhiên Phan Thiết – Mũi Né.')) ?></p>
            <p class="tagline">Your Perfect Beach Escape</p>
        </div>
        <div>
            <h4>Khám phá</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/rooms.php">Phòng nghỉ</a></li>
                <li><a href="<?= BASE_URL ?>/services.php">Dịch vụ</a></li>
                <li><a href="<?= BASE_URL ?>/promotions.php">Khuyến mãi</a></li>
                <li><a href="<?= BASE_URL ?>/gallery.php">Thư viện ảnh</a></li>
            </ul>
        </div>
        <div>
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/about.php">Giới thiệu</a></li>
                <li><a href="<?= BASE_URL ?>/contact.php">Liên hệ</a></li>
                <li><a href="<?= BASE_URL ?>/login.php">Tài khoản</a></li>
            </ul>
        </div>
        <div>
            <h4>Liên hệ</h4>
            <p class="line"><?= icon('map-pin') ?><span><?= e(setting('address', 'Mũi Né, Phan Thiết, Bình Thuận, Việt Nam')) ?></span></p>
            <p class="line"><?= icon('phone') ?><span><?= e(setting('phone', '0987 654 321')) ?></span></p>
            <p class="line"><?= icon('mail') ?><span><?= e(setting('email', 'hello@bluesearesort.vn')) ?></span></p>
        </div>
    </div>
    <div class="copyright">© <?= date('Y') ?> <?= e(setting('resort_name', 'BlueSea Resort')) ?>. Dự án demo phục vụ đồ án tốt nghiệp.</div>
</footer>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?: 1 ?>"></script>
</body>
</html>
