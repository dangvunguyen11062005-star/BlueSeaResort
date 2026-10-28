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
    <div class="copyright">© <?= date('Y') ?> <?= e(setting('resort_name', 'BlueSea Resort')) ?>. Bảo lưu mọi quyền.</div>
</footer>
<?php endif; ?>
<?php if (empty($hide_chatbot)): ?>
<div id="ai-chat" class="ai-chat" data-endpoint="<?= BASE_URL ?>/api/chat.php" data-csrf="<?= e(csrf_token()) ?>"
     data-welcome="Xin chào! Mình là trợ lý ảo của <?= e(setting('resort_name', 'BlueSea Resort')) ?>. Mình có thể tư vấn phòng, giá, khuyến mãi, dịch vụ và kiểm tra phòng trống giúp bạn.">
    <button class="ai-chat-toggle" type="button" aria-expanded="false" aria-controls="ai-chat-panel" aria-label="Mở trợ lý AI tư vấn"><?= icon('sparkles') ?><span>Tư vấn AI</span></button>
    <div class="ai-chat-panel" id="ai-chat-panel" role="dialog" aria-label="Trợ lý AI tư vấn" hidden>
        <div class="ai-chat-head"><div><b>Trợ lý <?= e(setting('resort_name', 'BlueSea Resort')) ?></b><small>Trả lời tự động bằng AI</small></div>
            <button class="ai-chat-close" type="button" aria-label="Đóng"><?= icon('x') ?></button></div>
        <div class="ai-chat-log" aria-live="polite"></div>
        <div class="ai-chat-chips">
            <button type="button" data-q="Resort có những loại phòng nào và giá bao nhiêu?">Loại phòng & giá</button>
            <button type="button" data-q="Hiện có khuyến mãi nào không?">Khuyến mãi</button>
            <button type="button" data-q="Gia đình 4 người, ngân sách khoảng 5 triệu/đêm thì nên chọn phòng nào?">Gợi ý cho gia đình</button>
            <button type="button" data-q="Giờ nhận phòng và trả phòng là mấy giờ?">Giờ nhận/trả phòng</button>
        </div>
        <form class="ai-chat-form" autocomplete="off">
            <input type="text" maxlength="500" placeholder="Nhập câu hỏi của bạn…" aria-label="Câu hỏi">
            <button class="btn btn-primary btn-small" type="submit" aria-label="Gửi"><?= icon('arrow-right') ?></button>
        </form>
        <small class="ai-chat-note">AI có thể sai sót – vui lòng xác nhận lại với lễ tân khi đặt phòng.</small>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/chatbot.js?v=<?= @filemtime(__DIR__ . '/../assets/js/chatbot.js') ?: 1 ?>"></script>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?: 1 ?>"></script>
</body>
</html>
