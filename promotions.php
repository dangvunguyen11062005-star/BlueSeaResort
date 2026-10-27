<?php
require_once 'config/app.php';
$page_title = 'Khuyến mãi';
$promos = [
    ['Stay 3 Pay 2', 'Ở 3 đêm, thanh toán 2 đêm. Áp dụng cho tất cả loại phòng vào ngày thường.', 'beach.svg', 'Đến 30/12'],
    ['Combo nghỉ dưỡng + Spa', 'Đặt phòng từ 2 đêm, tặng ngay 1 buổi spa thư giãn 60 phút.', 'pool.svg', 'Ưu đãi mới'],
    ['Family Escape', 'Giảm 15% khi lưu trú tại Family Villa hoặc Private Pool Villa từ 3 đêm.', 'dunes.svg', 'Dành cho gia đình'],
];
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner short" style="--bg-img:url('<?= img('sunset.svg') ?>')">
        <h1>Khuyến mãi</h1>
        <p>Ưu đãi hấp dẫn dành cho kỳ nghỉ của bạn</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="promo-grid">
            <?php foreach ($promos as [$title, $desc, $bg, $chip]): ?>
            <div class="photo promo" style="--bg-img:url('<?= img($bg) ?>')">
                <span class="chip"><?= e($chip) ?></span>
                <h2><?= e($title) ?></h2>
                <p><?= e($desc) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center muted" style="margin-top:26px;font-size:13.5px">Các chương trình khuyến mãi có thể thay đổi theo từng thời điểm. Vui lòng liên hệ để biết chi tiết điều kiện áp dụng.</p>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
