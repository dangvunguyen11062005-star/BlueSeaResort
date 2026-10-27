<?php
require_once 'config/database.php';
require_once 'config/app.php';
$page_title = 'BlueSea Resort - Phan Thiết Mũi Né';
$rooms = $pdo->query("SELECT rt.*, MIN(r.id) room_id FROM room_types rt LEFT JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance' GROUP BY rt.id ORDER BY rt.id LIMIT 4")->fetchAll();
$stay = stay_defaults();
$today = date('Y-m-d');
include 'includes/header.php';
?>
<section class="hero container">
    <div class="photo hero-photo" style="--bg-img:url('<?= img('hero.svg') ?>')">
        <p class="hero-script">BlueSea Resort</p>
        <h1 class="hero-sub"><span>Nghỉ dưỡng giữa thiên nhiên</span><span>Phan Thiết - Mũi Né</span></h1>
    </div>
    <form class="search-card" action="<?= BASE_URL ?>/rooms.php" method="get">
        <div class="sfield">
            <?= icon('calendar-days') ?>
            <div><label for="s-in">Ngày nhận phòng</label><input type="date" id="s-in" name="check_in" value="<?= e($stay['check_in']) ?>" min="<?= $today ?>" required></div>
        </div>
        <div class="sfield">
            <?= icon('calendar-check') ?>
            <div><label for="s-out">Ngày trả phòng</label><input type="date" id="s-out" name="check_out" value="<?= e($stay['check_out']) ?>" min="<?= $today ?>" required></div>
        </div>
        <div class="sfield">
            <?= icon('user') ?>
            <div><label for="s-guests">Số khách</label>
                <select id="s-guests" name="guests"><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>"<?= $i === $stay['guests'] ? ' selected' : '' ?>><?= $i ?> người</option><?php endfor; ?></select></div>
        </div>
        <button class="btn btn-primary" type="submit">Tìm phòng</button>
    </form>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Trải nghiệm nghỉ dưỡng tuyệt vời</h2>
            <p>Hòa mình vào thiên nhiên, tận hưởng những khoảnh khắc bình yên tại BlueSea Resort</p>
        </div>
        <div class="features">
            <div class="feature"><span class="ring"><?= icon('map-pin') ?></span><h3>Vị trí đắc địa</h3><p>Ngay bãi biển Mũi Né</p></div>
            <div class="feature"><span class="ring"><?= icon('hotel') ?></span><h3>Phòng nghỉ sang trọng</h3><p>View biển &amp; vườn</p></div>
            <div class="feature"><span class="ring"><?= icon('utensils') ?></span><h3>Ẩm thực đa dạng</h3><p>Hải sản tươi ngon</p></div>
            <div class="feature"><span class="ring"><?= icon('concierge-bell') ?></span><h3>Dịch vụ đẳng cấp</h3><p>Spa, hồ bơi, tour du lịch</p></div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:8px">
    <div class="container">
        <div class="section-head">
            <h2>Các loại phòng nổi bật</h2>
            <a class="link-more" href="<?= BASE_URL ?>/rooms.php">Xem tất cả <?= icon('arrow-right') ?></a>
        </div>
        <div class="photo-cards">
            <?php foreach ($rooms as $room): $detail = BASE_URL . '/room-detail.php?id=' . $room['id']; ?>
            <article class="photo-card">
                <div class="thumb">
                    <a href="<?= $detail ?>" tabindex="-1" aria-hidden="true"><img src="<?= img($room['image']) ?>" alt="" loading="lazy"></a>
                    <button class="heart" type="button" aria-pressed="false" data-heart="room-<?= (int)$room['id'] ?>" aria-label="Yêu thích <?= e($room['name']) ?>"><?= icon('heart') ?></button>
                </div>
                <a href="<?= $detail ?>"><h3><?= e($room['name']) ?></h3></a>
                <p class="from">Từ <b><?= money($room['price']) ?></b>/đêm</p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:8px">
    <div class="container">
        <div class="photo cta" style="--bg-img:url('<?= img('sunset.svg') ?>')">
            <div>
                <h2>Phan Thiết – Mũi Né đang chờ bạn</h2>
                <p>Đặt kỳ nghỉ của bạn tại BlueSea Resort ngay hôm nay.</p>
            </div>
            <a class="btn btn-white" href="<?= BASE_URL ?>/rooms.php">Khám phá phòng</a>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
