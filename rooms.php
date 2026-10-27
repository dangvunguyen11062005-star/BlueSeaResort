<?php
require_once 'config/database.php';
require_once 'config/app.php';
$page_title = 'Danh sách phòng';

$stay = stay_defaults();
$today = date('Y-m-d');
$guests = $stay['guests'];
$PMIN = 1000000; $PMAX = 10000000;
$min = max($PMIN, min($PMAX, (int)($_GET['min_price'] ?? $PMIN)));
$max = max($PMIN, min($PMAX, (int)($_GET['max_price'] ?? $PMAX)));
if ($min > $max) { [$min, $max] = [$max, $min]; }

$typesSel = array_values(array_filter(array_map('intval', (array)($_GET['types'] ?? []))));
$amSel = array_values(array_intersect((array)($_GET['amenities'] ?? []), array_keys(amenity_defs())));

$allTypes = $pdo->query("SELECT id, name FROM room_types ORDER BY price")->fetchAll();

$where = ['rt.capacity >= ?', 'rt.price BETWEEN ? AND ?'];
$params = [$guests, $min, $max];
if ($typesSel) {
    $where[] = 'rt.id IN (' . implode(',', array_fill(0, count($typesSel), '?')) . ')';
    $params = array_merge($params, $typesSel);
}
foreach ($amSel as $a) { $where[] = 'FIND_IN_SET(?, rt.amenities)'; $params[] = $a; }
$sql = "SELECT rt.*, MIN(r.id) room_id FROM room_types rt
        JOIN rooms r ON r.room_type_id=rt.id AND r.status<>'maintenance'
        WHERE " . implode(' AND ', $where) . " GROUP BY rt.id ORDER BY rt.price";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$carry = stay_params();

// Chỉ tính phòng trống khi khách đã chọn đủ cả ngày nhận lẫn ngày trả (hợp lệ)
$checkAvail = isset($_GET['check_in'], $_GET['check_out']) && stay_range_error($stay['check_in'], $stay['check_out']) === null;
$avail = [];
if ($checkAvail) {
    foreach ($rooms as $r) $avail[(int)$r['id']] = room_type_availability($pdo, $r['id'], $stay['check_in'], $stay['check_out']);
}
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner" style="--bg-img:url('<?= img('hero.svg') ?>')">
        <h1>Phòng nghỉ</h1>
        <p>Không gian nghỉ dưỡng đẳng cấp, tiện nghi hiện đại</p>
    </div>
</section>

<form method="get" id="filter-form" data-autosubmit>
    <div class="container overlap">
        <div class="search-card">
            <div class="sfield">
                <?= icon('calendar-days') ?>
                <div><label for="f-in">Ngày nhận</label><input type="date" id="f-in" name="check_in" value="<?= e($stay['check_in']) ?>" min="<?= $today ?>"></div>
            </div>
            <div class="sfield">
                <?= icon('calendar-check') ?>
                <div><label for="f-out">Ngày trả</label><input type="date" id="f-out" name="check_out" value="<?= e($stay['check_out']) ?>" min="<?= $today ?>"></div>
            </div>
            <div class="sfield">
                <?= icon('user') ?>
                <div><label for="f-guests">Số khách</label>
                    <select id="f-guests" name="guests"><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>"<?= $i === $guests ? ' selected' : '' ?>><?= $i ?> người</option><?php endfor; ?></select></div>
            </div>
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
        </div>
    </div>

    <div class="container" style="margin-top:34px">
        <div class="rooms-layout">
            <aside class="filter" aria-label="Bộ lọc phòng">
                <h3>Loại phòng</h3>
                <?php foreach ($allTypes as $t): ?>
                    <label class="check"><input type="checkbox" name="types[]" value="<?= $t['id'] ?>"<?= in_array((int)$t['id'], $typesSel, true) ? ' checked' : '' ?>><?= e($t['name']) ?></label>
                <?php endforeach; ?>

                <h3>Mức giá</h3>
                <div class="range" data-range data-min="<?= $PMIN ?>" data-max="<?= $PMAX ?>">
                    <div class="range-track"></div><div class="range-fill"></div>
                    <input type="range" name="min_price" min="<?= $PMIN ?>" max="<?= $PMAX ?>" step="100000" value="<?= $min ?>" aria-label="Giá thấp nhất">
                    <input type="range" name="max_price" min="<?= $PMIN ?>" max="<?= $PMAX ?>" step="100000" value="<?= $max ?>" aria-label="Giá cao nhất">
                </div>
                <div class="range-labels">Từ <span data-range-min><?= money($min) ?></span> - <span data-range-max><?= money($max) ?></span></div>

                <h3>Tiện nghi</h3>
                <?php foreach (['pool', 'balcony', 'bathtub', 'wifi', 'ac', 'hairdryer'] as $key): [$label] = amenity_defs()[$key]; ?>
                    <label class="check"><input type="checkbox" name="amenities[]" value="<?= $key ?>"<?= in_array($key, $amSel, true) ? ' checked' : '' ?>><?= e($label) ?></label>
                <?php endforeach; ?>
            </aside>

            <div>
                <?php if (!$rooms): ?>
                    <div class="panel empty">
                        <?= icon('search') ?>
                        <p><b>Chưa có loại phòng phù hợp</b></p>
                        <p>Hãy thử giảm bớt bộ lọc hoặc đổi số khách.</p>
                        <p style="margin-top:14px"><a class="btn btn-outline btn-small" href="<?= BASE_URL ?>/rooms.php">Xóa bộ lọc</a></p>
                    </div>
                <?php else: ?>
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room):
                        $detail = url_with('room-detail.php', ['id' => $room['id']] + $carry);
                        $book = url_with('booking.php', ['room_id' => $room['room_id']] + $carry); ?>
                    <article class="room-card">
                        <div class="thumb">
                            <a href="<?= $detail ?>" tabindex="-1" aria-hidden="true"><img src="<?= img($room['image']) ?>" alt="" loading="lazy"></a>
                            <button class="heart" type="button" aria-pressed="false" data-heart="room-<?= (int)$room['id'] ?>" aria-label="Yêu thích <?= e($room['name']) ?>"><?= icon('heart') ?></button>
                        </div>
                        <div class="room-card-body">
                            <div class="room-card-title"><a href="<?= $detail ?>"><h3><?= e($room['name']) ?></h3></a></div>
                            <div class="room-meta">
                                <span><?= icon('user') ?><?= (int)$room['capacity'] ?> người</span>
                                <span><?= icon('maximize') ?><?= (int)$room['area'] ?>m²</span>
                                <span><?= icon('bed-double') ?><?= e($room['bed_type']) ?></span>
                            </div>
                            <?php $av = $avail[(int)$room['id']] ?? null; ?>
                            <?php if ($av): ?>
                                <p class="avail <?= $av['free'] > 0 ? 'avail-ok' : 'avail-none' ?>"><?= icon($av['free'] > 0 ? 'circle-check' : 'circle-x') ?><?= $av['free'] > 0 ? 'Còn ' . (int)$av['free'] . ' phòng trống' : 'Hết phòng trong ngày đã chọn' ?></p>
                            <?php endif; ?>
                            <div class="room-card-foot">
                                <div class="price"><?= money($room['price']) ?><small>/đêm</small></div>
                                <?php if ($av && $av['free'] < 1): ?>
                                    <a class="btn btn-outline btn-small" href="<?= $detail ?>">Xem ngày khác</a>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-small" href="<?= $book ?>">Đặt phòng</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
<?php include 'includes/footer.php'; ?>
