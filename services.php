<?php
require_once 'config/database.php';
require_once 'config/app.php';
$page_title = 'Dịch vụ';
$services = $pdo->query("SELECT * FROM services WHERE active=1 ORDER BY id")->fetchAll();
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner short" style="--bg-img:url('<?= img('restaurant.svg') ?>')">
        <h1>Dịch vụ nghỉ dưỡng</h1>
        <p>Tận hưởng kỳ nghỉ trọn vẹn tại BlueSea Resort</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="svc-grid">
            <?php foreach ($services as $s): ?>
            <div class="svc-card">
                <span class="ring"><?= icon(service_icon($s['name'])) ?></span>
                <h3><?= e($s['name']) ?></h3>
                <p><?= e($s['description']) ?></p>
                <div class="price"><?= money($s['price']) ?> <small>/ <?= e($s['unit']) ?></small></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
