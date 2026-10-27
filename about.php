<?php
require_once 'config/app.php';
$page_title = 'Giới thiệu';
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner short" style="--bg-img:url('<?= img('hero.svg') ?>')">
        <h1>Giới thiệu</h1>
        <p>Câu chuyện của BlueSea Resort</p>
    </div>
</section>
<section class="section">
    <div class="container split">
        <img src="<?= img('beach.svg') ?>" alt="Bãi biển BlueSea Resort">
        <div>
            <h2>Nghỉ dưỡng giữa thiên nhiên Mũi Né</h2>
            <p>BlueSea Resort nằm ngay trên bãi biển Mũi Né, Phan Thiết — nơi giao hòa giữa biển xanh, cát trắng và những đồi cát nổi tiếng. Chúng tôi mang đến không gian nghỉ dưỡng yên bình, tách biệt khỏi nhịp sống ồn ào.</p>
            <p>Từ những phòng Deluxe hướng vườn ấm cúng đến các Villa có hồ bơi riêng sang trọng, mỗi không gian tại BlueSea Resort đều được thiết kế để bạn tận hưởng trọn vẹn kỳ nghỉ cùng gia đình và người thân.</p>
            <div class="stat-row">
                <div><b>5</b><span>Loại phòng</span></div>
                <div><b>8+</b><span>Phòng nghỉ</span></div>
                <div><b>12+</b><span>năm hoạt động</span></div>
                <div><b>4.8/5</b><span>Đánh giá khách hàng</span></div>
            </div>
        </div>
    </div>
</section>
<section class="section" style="padding-top:0">
    <div class="container split">
        <div>
            <h2>Dịch vụ &amp; tiện ích đẳng cấp</h2>
            <p>Hồ bơi vô cực hướng biển, nhà hàng phục vụ hải sản tươi sống, spa thư giãn và các tour khám phá Mũi Né — tất cả được đội ngũ nhân viên tận tâm của chúng tôi chăm chút để mang lại trải nghiệm khó quên.</p>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/services.php">Xem dịch vụ</a>
        </div>
        <img src="<?= img('restaurant.svg') ?>" alt="Nhà hàng BlueSea Resort">
    </div>
</section>
<?php include 'includes/footer.php'; ?>
