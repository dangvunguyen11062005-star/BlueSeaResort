<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin('admin'); // Chỉ Administrator mới được quản lý giá & khuyến mãi

$stockImages = array_map('basename', glob(__DIR__ . '/../assets/images/room[0-9]*.svg') ?: []);
sort($stockImages);
$amenityDefs = amenity_defs();

/* Lưu ảnh tải lên (jpg/png/webp, tối đa 3MB). Trả về tên file hoặc null. */
function save_room_image(array $file) {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 3 * 1024 * 1024) return false;
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) return false;
    $dir = __DIR__ . '/../assets/images/uploads';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = 'uploads/rt_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], __DIR__ . '/../assets/images/' . $name) ? $name : false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)$_POST['id'];
        $n = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id=?");
        $n->execute([$id]);
        if ($n->fetchColumn() > 0) {
            flash_set('Không thể xóa: vẫn còn phòng thuộc loại này. Hãy xóa hoặc chuyển các phòng đó trước.', 'error');
        } else {
            $pdo->prepare("DELETE FROM room_types WHERE id=?")->execute([$id]);
            flash_set('Đã xóa loại phòng.');
        }
        redirect(BASE_URL . '/admin/room-types.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $cap = max(1, (int)($_POST['capacity'] ?? 2));
    $area = max(1, (int)($_POST['area'] ?? 25));
    $bed = trim($_POST['bed_type'] ?? '');
    $view = trim($_POST['view_type'] ?? '');
    $am = implode(',', array_values(array_intersect((array)($_POST['amenities'] ?? []), array_keys($amenityDefs))));
    $image = $_POST['image'] ?? '';
    if (!in_array($image, $stockImages, true)) $image = $stockImages[0] ?? 'room1.svg';

    if ($name === '' || $price < 1000) {
        flash_set('Vui lòng nhập tên loại phòng và giá hợp lệ.', 'error');
        redirect(BASE_URL . '/admin/room-types.php');
    }
    $up = save_room_image($_FILES['image_file'] ?? []);
    if ($up === false) {
        flash_set('Ảnh tải lên không hợp lệ (chỉ nhận JPG/PNG/WEBP, tối đa 3MB).', 'error');
        redirect(BASE_URL . '/admin/room-types.php');
    }
    if ($up) $image = $up;

    if ($id) {
        $pdo->prepare("UPDATE room_types SET name=?,description=?,price=?,capacity=?,area=?,bed_type=?,view_type=?,amenities=?,image=? WHERE id=?")
            ->execute([$name, $desc, $price, $cap, $area, $bed, $view, $am, $image, $id]);
        flash_set('Đã cập nhật loại phòng "' . $name . '".');
    } else {
        $pdo->prepare("INSERT INTO room_types(name,description,price,capacity,area,bed_type,view_type,amenities,image) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$name, $desc, $price, $cap, $area, $bed, $view, $am, $image]);
        flash_set('Đã thêm loại phòng "' . $name . '".');
    }
    redirect(BASE_URL . '/admin/room-types.php');
}

$items = $pdo->query("SELECT rt.*, (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id=rt.id) room_count FROM room_types rt ORDER BY rt.price")->fetchAll();

/* Form dùng chung cho thêm / sửa */
function room_type_form(array $t, array $stockImages, array $amenityDefs) {
    $have = array_filter(explode(',', (string)($t['amenities'] ?? '')));
    $id = (int)($t['id'] ?? 0); ?>
    <form method="post" enctype="multipart/form-data" class="dialog-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-group"><label for="rt-name-<?= $id ?>">Tên loại phòng</label><input class="input" id="rt-name-<?= $id ?>" name="name" value="<?= e($t['name'] ?? '') ?>" required></div>
        <div class="form-group"><label for="rt-desc-<?= $id ?>">Mô tả</label><textarea class="input" id="rt-desc-<?= $id ?>" name="description" rows="3"><?= e($t['description'] ?? '') ?></textarea></div>
        <div class="form-2">
            <div class="form-group"><label>Giá / đêm (đ)</label><input class="input" type="number" name="price" min="1000" step="1000" value="<?= (int)($t['price'] ?? 1500000) ?>" required></div>
            <div class="form-group"><label>Sức chứa (người)</label><input class="input" type="number" name="capacity" min="1" max="12" value="<?= (int)($t['capacity'] ?? 2) ?>" required></div>
            <div class="form-group"><label>Diện tích (m²)</label><input class="input" type="number" name="area" min="1" value="<?= (int)($t['area'] ?? 30) ?>" required></div>
            <div class="form-group"><label>Loại giường</label><input class="input" name="bed_type" value="<?= e($t['bed_type'] ?? 'Giường đôi') ?>"></div>
        </div>
        <div class="form-group"><label>Hướng nhìn</label><input class="input" name="view_type" value="<?= e($t['view_type'] ?? 'Hướng biển') ?>"></div>
        <div class="form-group"><span class="label">Tiện nghi</span>
            <div class="check-grid">
                <?php foreach ($amenityDefs as $key => [$label]): ?>
                    <label class="check"><input type="checkbox" name="amenities[]" value="<?= $key ?>"<?= in_array($key, $have, true) ? ' checked' : '' ?>><?= e($label) ?></label>
                <?php endforeach; ?>
            </div></div>
        <div class="form-2">
            <div class="form-group"><label>Ảnh có sẵn</label>
                <select class="input" name="image">
                    <?php foreach ($stockImages as $img): ?><option value="<?= e($img) ?>"<?= ($t['image'] ?? '') === $img ? ' selected' : '' ?>><?= e($img) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label>Hoặc tải ảnh mới</label><input class="input" type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="padding-top:9px"></div>
        </div>
        <button class="btn btn-primary btn-block"><?= $id ? 'Lưu thay đổi' : 'Thêm loại phòng' ?></button>
    </form>
<?php }

$page_title = 'Quản lý loại phòng';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('layers') ?>Quản lý loại phòng</h1><p>Giá, sức chứa, tiện nghi và ảnh của từng loại phòng</p></div>
    <button class="btn btn-primary" type="button" data-open-dialog="dlg-rt-new"><?= icon('plus') ?>Thêm loại phòng</button>
</div>

<div class="table-wrap">
    <table class="table">
        <tr><th class="th-img">Hình ảnh</th><th>Tên loại phòng</th><th>Giá/đêm</th><th>Sức chứa</th><th>Diện tích</th><th>Số phòng</th><th>Thao tác</th></tr>
        <?php foreach ($items as $t): ?>
        <tr>
            <td><img class="mini" src="<?= img($t['image']) ?>" alt=""></td>
            <td><b><?= e($t['name']) ?></b><br><small class="muted"><?= e($t['bed_type']) ?> · <?= e($t['view_type']) ?></small></td>
            <td><?= money($t['price']) ?></td>
            <td><?= (int)$t['capacity'] ?> người</td>
            <td><?= (int)$t['area'] ?>m²</td>
            <td><?= (int)$t['room_count'] ?></td>
            <td class="actions">
                <button class="act" type="button" title="Sửa" data-open-dialog="dlg-rt-<?= (int)$t['id'] ?>"><?= icon('pencil') ?></button>
<form method="post" data-confirm="Xóa loại phòng <?= e($t['name']) ?>?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<dialog id="dlg-rt-new">
    <div class="dialog-head"><h3>Thêm loại phòng</h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <?php room_type_form([], $stockImages, $amenityDefs); ?>
</dialog>
<?php foreach ($items as $t): ?>
<dialog id="dlg-rt-<?= (int)$t['id'] ?>">
    <div class="dialog-head"><h3>Sửa: <?= e($t['name']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <?php room_type_form($t, $stockImages, $amenityDefs); ?>
</dialog>
<?php endforeach; ?>
<?php include '../includes/admin_footer.php'; ?>
