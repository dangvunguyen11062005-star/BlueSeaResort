<?php
require_once 'config/app.php';
$page_title = 'Thư viện ảnh';
$photos = [
    ['hero.svg', 'Toàn cảnh resort', 'beach'],
    ['beach.svg', 'Bãi biển Mũi Né', 'beach'],
    ['sunset.svg', 'Hoàng hôn trên biển', 'beach'],
    ['dunes.svg', 'Đồi cát Mũi Né', 'beach'],
    ['pool.svg', 'Hồ bơi vô cực', 'facility'],
    ['restaurant.svg', 'Nhà hàng ven biển', 'facility'],
    ['room1.svg', 'Deluxe Garden View', 'room'],
    ['room2.svg', 'Deluxe Ocean View', 'room'],
    ['room3.svg', 'Bungalow', 'room'],
    ['room4.svg', 'Family Villa', 'room'],
    ['room5.svg', 'Private Pool Villa', 'room'],
];
$cats = ['all' => 'Tất cả', 'room' => 'Phòng nghỉ', 'facility' => 'Tiện ích', 'beach' => 'Cảnh quan'];
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner short" style="--bg-img:url('<?= img('pool.svg') ?>')">
        <h1>Thư viện ảnh</h1>
        <p>Biển, phòng nghỉ và không gian BlueSea Resort</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="chips" data-chip-group=".gallery figure">
            <?php foreach ($cats as $key => $label): ?>
                <button type="button" class="chip-btn" data-chip="<?= $key ?>" aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"><?= $label ?></button>
            <?php endforeach; ?>
        </div>
        <div class="gallery">
            <?php foreach ($photos as [$file, $caption, $cat]): ?>
                <figure data-cat="<?= $cat ?>" data-lightbox="<?= img($file) ?>">
                    <img src="<?= img($file) ?>" alt="<?= e($caption) ?>" loading="lazy">
                    <figcaption><?= e($caption) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<dialog id="lightbox" class="lightbox">
    <button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button>
    <img src="" alt="">
</dialog>
<?php include 'includes/footer.php'; ?>
