<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin('admin'); // Chỉ Administrator mới được quản lý giá & khuyến mãi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (($_POST['quick_action'] ?? '') === 'toggle') {
        $pdo->prepare("UPDATE promotions SET active = 1-active WHERE id=?")->execute([(int)$_POST['id']]);
        flash_set('Đã cập nhật trạng thái khuyến mãi.');
        redirect(BASE_URL . '/admin/promotions.php');
    }
    if (($_POST['quick_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM promotions WHERE id=?")->execute([(int)$_POST['id']]);
        flash_set('Đã xóa khuyến mãi. Các đơn đã dùng mã này vẫn giữ nguyên số tiền giảm.');
        redirect(BASE_URL . '/admin/promotions.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $type = ($_POST['discount_type'] ?? '') === 'amount' ? 'amount' : 'percent';
    $value = max(0, (int)($_POST['discount_value'] ?? 0));
    $min = max(0, (int)($_POST['min_total'] ?? 0));
    $start = $_POST['start_date'] ?? ''; $end = $_POST['end_date'] ?? '';
    $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end);

    if ($code === '' || $title === '' || $value <= 0 || !$ok) {
        flash_set('Vui lòng nhập đủ mã, tên chương trình, mức giảm và ngày áp dụng.', 'error');
    } elseif ($type === 'percent' && $value > 100) {
        flash_set('Giảm theo phần trăm không được vượt quá 100%.', 'error');
    } elseif ($end < $start) {
        flash_set('Ngày kết thúc phải sau ngày bắt đầu.', 'error');
    } else {
        try {
            if ($id) {
                $pdo->prepare("UPDATE promotions SET code=?,title=?,description=?,discount_type=?,discount_value=?,min_total=?,start_date=?,end_date=? WHERE id=?")
                    ->execute([$code, $title, $desc, $type, $value, $min, $start, $end, $id]);
                flash_set('Đã cập nhật khuyến mãi ' . $code . '.');
            } else {
                $pdo->prepare("INSERT INTO promotions(code,title,description,discount_type,discount_value,min_total,start_date,end_date) VALUES(?,?,?,?,?,?,?,?)")
                    ->execute([$code, $title, $desc, $type, $value, $min, $start, $end]);
                flash_set('Đã thêm khuyến mãi ' . $code . '.');
            }
        } catch (PDOException $ex) {
            flash_set('Mã khuyến mãi "' . $code . '" đã tồn tại, hãy chọn mã khác.', 'error');
        }
    }
    redirect(BASE_URL . '/admin/promotions.php');
}

$items = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM bookings b WHERE b.promo_code=p.code AND b.status<>'cancelled') used FROM promotions p ORDER BY p.end_date DESC, p.id DESC")->fetchAll();

function promo_form(array $p) {
    $id = (int)($p['id'] ?? 0); ?>
    <form method="post" class="dialog-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-2">
            <div class="form-group"><label>Mã khuyến mãi</label><input class="input" name="code" value="<?= e($p['code'] ?? '') ?>" placeholder="VD: SUMMER20" style="text-transform:uppercase" required></div>
            <div class="form-group"><label>Tên chương trình</label><input class="input" name="title" value="<?= e($p['title'] ?? '') ?>" required></div>
        </div>
        <div class="form-group"><label>Mô tả ngắn</label><input class="input" name="description" value="<?= e($p['description'] ?? '') ?>"></div>
        <div class="form-2">
            <div class="form-group"><label>Kiểu giảm</label>
                <select class="input" name="discount_type">
                    <option value="percent"<?= ($p['discount_type'] ?? 'percent') === 'percent' ? ' selected' : '' ?>>Theo phần trăm (%)</option>
                    <option value="amount"<?= ($p['discount_type'] ?? '') === 'amount' ? ' selected' : '' ?>>Số tiền cố định (đ)</option>
                </select></div>
            <div class="form-group"><label>Mức giảm</label><input class="input" type="number" name="discount_value" min="1" value="<?= (int)($p['discount_value'] ?? 10) ?>" required></div>
            <div class="form-group"><label>Ngày bắt đầu</label><input class="input" type="date" name="start_date" value="<?= e($p['start_date'] ?? date('Y-m-d')) ?>" required></div>
            <div class="form-group"><label>Ngày kết thúc</label><input class="input" type="date" name="end_date" value="<?= e($p['end_date'] ?? date('Y-m-d', strtotime('+3 month'))) ?>" required></div>
        </div>
        <div class="form-group"><label>Giá trị đơn tối thiểu (đ)</label><input class="input" type="number" name="min_total" min="0" step="100000" value="<?= (int)($p['min_total'] ?? 0) ?>"></div>
        <button class="btn btn-primary btn-block"><?= $id ? 'Lưu thay đổi' : 'Thêm khuyến mãi' ?></button>
    </form>
<?php }

$page_title = 'Khuyến mãi';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('ticket-percent') ?>Khuyến mãi</h1><p>Mã giảm giá khách nhập khi đặt phòng</p></div>
    <button class="btn btn-primary" type="button" data-open-dialog="dlg-promo-new"><?= icon('plus') ?>Thêm khuyến mãi</button>
</div>

<div class="table-wrap">
    <table class="table">
        <tr><th>Mã</th><th>Chương trình</th><th>Mức giảm</th><th>Đơn tối thiểu</th><th>Thời gian</th><th>Đã dùng</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($items as $p): [$label, $tone] = promo_state($p); ?>
        <tr>
            <td><span class="code-chip"><?= e($p['code']) ?></span></td>
            <td><b><?= e($p['title']) ?></b><br><small class="muted"><?= e($p['description']) ?></small></td>
            <td><?= e(promo_label($p)) ?></td>
            <td><?= $p['min_total'] > 0 ? money($p['min_total']) : '—' ?></td>
            <td><?= date_vn($p['start_date']) ?> – <?= date_vn($p['end_date']) ?></td>
            <td class="center"><?= (int)$p['used'] ?></td>
            <td><span class="badge badge-<?= $tone ?>"><?= $label ?></span></td>
            <td class="actions">
                <button class="act" type="button" title="Sửa" data-open-dialog="dlg-promo-<?= (int)$p['id'] ?>"><?= icon('pencil') ?></button>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="quick_action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="act" type="submit" title="<?= $p['active'] ? 'Tạm dừng' : 'Bật lại' ?>"><?= icon($p['active'] ? 'circle-x' : 'circle-check') ?></button></form>
                <form method="post" data-confirm="Xóa khuyến mãi <?= e($p['code']) ?>?"><?= csrf_field() ?><input type="hidden" name="quick_action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px">Chưa có khuyến mãi nào.</td></tr><?php endif; ?>
    </table>
</div>

<dialog id="dlg-promo-new">
    <div class="dialog-head"><h3>Thêm khuyến mãi</h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <?php promo_form([]); ?>
</dialog>
<?php foreach ($items as $p): ?>
<dialog id="dlg-promo-<?= (int)$p['id'] ?>">
    <div class="dialog-head"><h3>Sửa: <?= e($p['code']) ?></h3><button class="icon-btn" type="button" data-close-dialog aria-label="Đóng"><?= icon('x') ?></button></div>
    <?php promo_form($p); ?>
</dialog>
<?php endforeach; ?>
<?php include '../includes/admin_footer.php'; ?>
