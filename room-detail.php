<?php
require_once 'config/database.php';
require_once 'config/app.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT rt.*, MIN(r.id) room_id FROM room_types rt JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance' WHERE rt.id=? GROUP BY rt.id");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    http_response_code(404);
    $page_title = 'Không tìm thấy phòng';
    include 'includes/header.php';
    echo '<section class="section"><div class="container"><div class="panel empty">' . icon('search') . '<p><b>Không tìm thấy phòng này</b></p><p>Phòng có thể đang bảo trì hoặc không tồn tại.</p><p style="margin-top:14px"><a class="btn btn-primary btn-small" href="' . BASE_URL . '/rooms.php">Xem tất cả phòng</a></p></div></div></section>';
    include 'includes/footer.php';
    exit;
}

$page_title = $room['name'];
$reviewStats = ['n' => 0, 'avg' => 0];
$reviews = [];
if (db_is_v2()) {
    $rs = $pdo->prepare("SELECT COUNT(*) n, COALESCE(AVG(rating),0) avg_r FROM reviews WHERE room_type_id=? AND status='approved'");
    $rs->execute([$id]);
    $reviewStats = ['n' => (int)$rs->fetchColumn(), 'avg' => 0];
    $rs->execute([$id]);
    $row = $rs->fetch();
    $reviewStats = ['n' => (int)$row['n'], 'avg' => (float)$row['avg_r']];
    $rl = $pdo->prepare("SELECT rv.rating, rv.comment, rv.created_at, c.name FROM reviews rv JOIN customers c ON c.id=rv.customer_id WHERE rv.room_type_id=? AND rv.status='approved' ORDER BY rv.created_at DESC LIMIT 6");
    $rl->execute([$id]);
    $reviews = $rl->fetchAll();
}

$other = 'room' . ((((int)filter_var($room['image'], FILTER_SANITIZE_NUMBER_INT)) % 5) + 1) . '.svg';
$thumbs = [$room['image'], $other, 'pool.svg', 'dunes.svg'];
$side = ['beach.svg', 'hero.svg', 'sunset.svg', 'restaurant.svg'];
$amenities = room_amenities($room['amenities']);
$book = url_with('booking.php', ['room_id' => $room['room_id']] + stay_params());

// Phòng trống theo ngày khách đã chọn (nếu có); hết phòng thì gợi ý các khoảng ngày gần nhất còn phòng
$stayChosen = stay_defaults();
$av = null; $suggest = [];
if (isset($_GET['check_in'], $_GET['check_out']) && stay_range_error($stayChosen['check_in'], $stayChosen['check_out']) === null) {
    $av = room_type_availability($pdo, $room['id'], $stayChosen['check_in'], $stayChosen['check_out']);
    if ($av['free'] < 1) $suggest = suggest_free_ranges($pdo, $room['id'], $stayChosen['check_in'], $stayChosen['check_out']);
}
include 'includes/header.php';
?>
<section class="section-tight" style="padding-top:14px">
    <div class="container">
        <div class="detail">
            <div>
                <div class="gallery-main"><img id="gallery-main" src="<?= img($thumbs[0]) ?>" alt="<?= e($room['name']) ?>"></div>
                <div class="thumbs" role="group" aria-label="Ảnh phòng">
                    <?php foreach ($thumbs as $i => $t): ?>
                        <button type="button" data-gallery="<?= img($t) ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Xem ảnh <?= $i + 1 ?>"><img src="<?= img($t) ?>" alt=""></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="side-photos" aria-hidden="true">
                <?php foreach ($side as $t): ?><img src="<?= img($t) ?>" alt="" loading="lazy"><?php endforeach; ?>
            </div>
        </div>

        <div class="detail-info" style="max-width:calc(100% - 270px)">
            <h1><?= e($room['name']) ?></h1>
            <div class="stars" aria-label="Đánh giá <?= $reviewStats['n'] ? round($reviewStats['avg'],1) : 0 ?> trên 5 sao">
                <?php if ($reviewStats['n'] > 0): $full = round($reviewStats['avg']); ?>
                    <?= str_repeat(icon('star'), $full) ?><?= str_repeat('<span class="off">' . icon('star') . '</span>', 5 - $full) ?>
                    <span><?= number_format($reviewStats['avg'], 1) ?> (<?= $reviewStats['n'] ?> đánh giá)</span>
                <?php else: ?>
                    <span class="off"><?= str_repeat(icon('star'), 5) ?></span><span>Chưa có đánh giá</span>
                <?php endif; ?>
            </div>
            <div class="detail-meta">
                <span><?= icon('user') ?><?= (int)$room['capacity'] ?> người</span>
                <span><?= icon('maximize') ?><?= (int)$room['area'] ?>m²</span>
                <span><?= icon('bed-double') ?><?= e($room['bed_type']) ?></span>
                <span><?= icon('map-pin') ?><?= e($room['view_type']) ?></span>
            </div>
            <div class="detail-desc">
                <div>
                    <h2>Mô tả</h2>
                    <p><?= e($room['description']) ?></p>
                </div>
                <div class="buy-box">
                    <span class="price"><?= money($room['price']) ?><small>/đêm</small></span>
                    <?php if ($av): ?>
                        <p class="avail <?= $av['free'] > 0 ? 'avail-ok' : 'avail-none' ?>"><?= icon($av['free'] > 0 ? 'circle-check' : 'circle-x') ?><?= $av['free'] > 0 ? 'Còn ' . (int)$av['free'] . ' phòng trống (' . date_vn($stayChosen['check_in']) . ' - ' . date_vn($stayChosen['check_out']) . ')' : 'Hết phòng ngày ' . date_vn($stayChosen['check_in']) . ' - ' . date_vn($stayChosen['check_out']) ?></p>
                    <?php endif; ?>
                    <?php if ($av && $av['free'] < 1): ?>
                        <?php if ($suggest): ?>
                            <p class="avail-note">Gợi ý ngày còn phòng (cùng số đêm):</p>
                            <div class="suggest-list">
                                <?php foreach ($suggest as $sg): ?>
                                    <a class="btn btn-outline btn-small" href="<?= e(url_with('room-detail.php', ['id' => $room['id'], 'check_in' => $sg['check_in'], 'check_out' => $sg['check_out'], 'guests' => $stayChosen['guests']])) ?>"><?= date_vn($sg['check_in'], 'd/m') ?> - <?= date_vn($sg['check_out'], 'd/m/Y') ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="avail-note">Chưa tìm thấy ngày trống gần đó, vui lòng thử thời điểm khác.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="btn btn-primary" href="<?= $book ?>">Đặt phòng</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($amenities): ?>
            <h2 style="margin-top:26px">Tiện nghi</h2>
            <div class="amenities">
                <?php foreach ($amenities as [$label, $ic]): ?>
                    <div class="amenity"><?= icon($ic) ?><?= e($label) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($reviews): ?>
        <div class="reviews-block">
            <h2 style="font-size:18px;font-weight:700;margin-bottom:14px">Đánh giá từ khách hàng</h2>
            <?php foreach ($reviews as $r): ?>
                <div class="review-item">
                    <div class="review-top"><b><?= e($r['name']) ?></b> <?= rating_stars($r['rating']) ?></div>
                    <?php if ($r['comment'] !== ''): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
                    <small class="muted"><?= date_vn($r['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
