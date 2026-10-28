<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();

// Staff chỉ được đổi trạng thái phòng (qua room-status.php); thêm/xóa phòng chỉ dành cho Administrator
$canManageRooms = !is_admin_role('staff');

$types = $pdo->query("SELECT * FROM room_types ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!$canManageRooms) {
        flash_set('Tài khoản Staff chỉ được đổi trạng thái phòng. Thêm hoặc xóa phòng cần quyền Administrator.', 'error');
        redirect(BASE_URL . '/admin/rooms.php');
    }
    if (($_POST['action'] ?? '') === 'delete') {
        try {
            $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([(int)$_POST['id']]);
            flash_set('Đã xóa phòng.');
        } catch (PDOException $ex) {
            flash_set('Không thể xóa: phòng này đang có đơn đặt phòng liên quan.', 'error');
        }
    } else {
        $code = trim($_POST['room_code'] ?? ''); $type = (int)($_POST['room_type_id'] ?? 0); $floor = (int)($_POST['floor'] ?? 1);
        if ($code === '' || !$type) {
            flash_set('Vui lòng nhập mã phòng và chọn loại phòng.', 'error');
        } else {
            try {
                $pdo->prepare("INSERT INTO rooms(room_code,room_type_id,floor) VALUES(?,?,?)")->execute([$code, $type, $floor]);
                flash_set('Đã thêm phòng ' . $code . '.');
            } catch (PDOException $ex) {
                flash_set('Mã phòng "' . $code . '" đã tồn tại.', 'error');
            }
        }
    }
    redirect(BASE_URL . '/admin/rooms.php');
}

$q = trim($_GET['q'] ?? '');
$typeFilter = (int)($_GET['type'] ?? 0);
$where = []; $params = [];
if ($q !== '') { $where[] = '(r.room_code LIKE ? OR rt.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($typeFilter) { $where[] = 'rt.id = ?'; $params[] = $typeFilter; }
$sql = "SELECT r.*, rt.name room_type, rt.price, rt.image FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id" .
       ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY r.id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$page_title = 'Quản lý phòng';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('bed-double') ?>Quản lý phòng</h1><p>Danh sách toàn bộ phòng nghỉ tại BlueSea Resort</p></div>
    <?php if ($canManageRooms): ?>
    <button class="btn btn-primary" type="button" data-open-dialog="dlg-add-room"><?= icon('plus') ?>Thêm phòng</button>
    <?php endif; ?>
</div>

<form class="toolbar" method="get">
    <div class="search"><?= icon('search') ?><input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm kiếm phòng..."></div>
    <select class="input" name="type" data-autosubmit>
        <option value="0">Tất cả loại phòng</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>"<?= $typeFilter === (int)$t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit">Lọc</button>
</form>

<div class="table-wrap">
    <table class="table">
        <tr><th class="th-img">Hình ảnh</th><th>Tên phòng</th><th>Loại phòng</th><th>Giá/đêm</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($rooms as $r): [$label, $tone] = room_status($r['status']); ?>
        <tr>
            <td><img class="mini" src="<?= img($r['image']) ?>" alt=""></td>
            <td><?= e($r['room_code']) ?></td>
            <td><?= e($r['room_type']) ?></td>
            <td><?= money($r['price']) ?></td>
            <td><span class="badge badge-<?= $tone ?>"><?= $label ?></span></td>
            <td class="actions">
                <button class="act" type="button" title="Sửa" data-open-dialog="dlg-edit-<?= (int)$r['id'] ?>"><?= icon('pencil') ?></button>
                <?php if ($canManageRooms): ?>
                <form method="post" data-confirm="Xóa phòng <?= e($r['room_code']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rooms): ?><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">Không có phòng phù hợp.</td></tr><?php endif; ?>
    </table>
</div>

<?php if ($canManageRooms): ?>
<dialog id="dlg-add-room">
    <div class="dialog-head"><h3>Thêm phòng mới</h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <form method="post" class="dialog-body">
        <?= csrf_field() ?>
        <div class="form-group"><label for="r-code">Mã phòng</label><input class="input" id="r-code" name="room_code" placeholder="VD: P09" required></div>
        <div class="form-group"><label for="r-type">Loại phòng</label>
            <select class="input" id="r-type" name="room_type_id" required>
                <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="r-floor">Tầng</label><input class="input" id="r-floor" type="number" name="floor" value="1" min="1"></div>
        <button class="btn btn-primary btn-block">Lưu phòng</button>
    </form>
</dialog>
<?php endif; ?>
<?php foreach ($rooms as $r): ?>
<dialog id="dlg-edit-<?= (int)$r['id'] ?>">
    <div class="dialog-head"><h3>Phòng <?= e($r['room_code']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <form method="post" action="room-status.php" class="dialog-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <div class="form-group"><label>Loại phòng</label><input class="input" value="<?= e($r['room_type']) ?>" readonly></div>
        <div class="form-group"><label for="st-<?= (int)$r['id'] ?>">Trạng thái</label>
            <select class="input" id="st-<?= (int)$r['id'] ?>" name="status">
                <?php foreach (['available' => 'Còn trống', 'occupied' => 'Đang thuê', 'maintenance' => 'Bảo trì'] as $val => $lab): ?>
                    <option value="<?= $val ?>"<?= $r['status'] === $val ? ' selected' : '' ?>><?= $lab ?></option>
                <?php endforeach; ?>
            </select></div>
        <button class="btn btn-primary btn-block">Cập nhật trạng thái</button>
    </form>
</dialog>
<?php endforeach; ?>
<?php include '../includes/admin_footer.php'; ?>
