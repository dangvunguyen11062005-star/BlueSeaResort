<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin('admin'); // Chỉ Administrator mới được quản lý giá & khuyến mãi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $pdo->prepare("UPDATE services SET active = 1-active WHERE id=?")->execute([(int)$_POST['id']]);
        flash_set('Đã cập nhật trạng thái dịch vụ.');
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare("DELETE FROM services WHERE id=?")->execute([(int)$_POST['id']]);
            flash_set('Đã xóa dịch vụ.');
        } catch (PDOException $ex) {
            flash_set('Không thể xóa: dịch vụ này đã được dùng trong một đơn đặt phòng.', 'error');
        }
    } else {
        $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0); $unit = trim($_POST['unit'] ?? 'lần');
        if ($name === '' || $price <= 0) {
            flash_set('Vui lòng nhập tên dịch vụ và giá hợp lệ.', 'error');
        } else {
            $pdo->prepare("INSERT INTO services(name,description,price,unit) VALUES(?,?,?,?)")->execute([$name, $desc, $price, $unit]);
            flash_set('Đã thêm dịch vụ "' . $name . '".');
        }
    }
    redirect(BASE_URL . '/admin/services.php');
}
$items = $pdo->query("SELECT * FROM services ORDER BY id")->fetchAll();
$page_title = 'Quản lý dịch vụ';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('martini') ?>Quản lý dịch vụ</h1><p>Các dịch vụ thêm khách có thể chọn khi đặt phòng</p></div>
    <button class="btn btn-primary" type="button" data-open-dialog="dlg-add-svc"><?= icon('plus') ?>Thêm dịch vụ</button>
</div>

<div class="table-wrap">
    <table class="table">
        <tr><th>Dịch vụ</th><th>Mô tả</th><th>Giá</th><th>Đơn vị</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($items as $s): ?>
        <tr>
            <td style="display:flex;align-items:center;gap:10px"><span class="ico ico-blue" style="width:32px;height:32px;border-radius:9px"><?= icon(service_icon($s['name'])) ?></span><?= e($s['name']) ?></td>
            <td class="muted"><?= e($s['description']) ?></td>
            <td><?= money($s['price']) ?></td>
            <td><?= e($s['unit']) ?></td>
            <td><span class="badge badge-<?= $s['active'] ? 'ok' : 'bad' ?>"><?= $s['active'] ? 'Đang hoạt động' : 'Đã ẩn' ?></span></td>
            <td class="actions">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="act" type="submit" title="<?= $s['active'] ? 'Ẩn dịch vụ' : 'Bật lại' ?>"><?= icon($s['active'] ? 'circle-x' : 'circle-check') ?></button></form>
                <form method="post" data-confirm="Xóa dịch vụ <?= e($s['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button></form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<dialog id="dlg-add-svc">
    <div class="dialog-head"><h3>Thêm dịch vụ</h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <form method="post" class="dialog-body">
        <?= csrf_field() ?>
        <div class="form-group"><label for="s-name">Tên dịch vụ</label><input class="input" id="s-name" name="name" required></div>
        <div class="form-group"><label for="s-desc">Mô tả</label><input class="input" id="s-desc" name="description"></div>
        <div class="form-group"><label for="s-price">Giá</label><input class="input" id="s-price" type="number" name="price" min="1000" step="1000" required></div>
        <div class="form-group"><label for="s-unit">Đơn vị</label><input class="input" id="s-unit" name="unit" value="lần"></div>
        <button class="btn btn-primary btn-block">Lưu dịch vụ</button>
    </form>
</dialog>
<?php include '../includes/admin_footer.php'; ?>
