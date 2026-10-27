<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin('admin'); // chỉ Administrator mới được đổi cài đặt / mật khẩu quản trị
require_once '../includes/vietqr.php';

$fields = [
    'resort_name'   => ['Tên resort', 'BlueSea Resort'],
    'tagline'       => ['Khẩu hiệu (chân trang)', 'Nghỉ dưỡng giữa thiên nhiên Phan Thiết – Mũi Né.'],
    'phone'         => ['Số điện thoại', '0987 654 321'],
    'email'         => ['Email liên hệ', 'hello@bluesearesort.vn'],
    'address'       => ['Địa chỉ', 'Mũi Né, Phan Thiết, Bình Thuận, Việt Nam'],
    'support_hours' => ['Giờ hỗ trợ', '08:00 – 22:00 hằng ngày'],
    'checkin_time'  => ['Giờ nhận phòng', '14:00'],
    'checkout_time' => ['Giờ trả phòng', '12:00'],
];
$bankFields = [
    'bank_bin'          => ['Ngân hàng', '970436'],
    'bank_account_no'   => ['Số tài khoản', ''],
    'bank_account_name' => ['Tên chủ tài khoản (không dấu)', 'BLUESEA RESORT'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (($_POST['form'] ?? '') === 'password') {
        $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? ''; $conf = $_POST['confirm_password'] ?? '';
        $adminId = (int)$_SESSION['admin']['id'];
        $hash = $pdo->prepare("SELECT password FROM admins WHERE id=?");
        $hash->execute([$adminId]);
        if (!password_matches($cur, $hash->fetchColumn())) {
            flash_set('Mật khẩu hiện tại không đúng.', 'error');
        } elseif (strlen($new) < 6) {
            flash_set('Mật khẩu mới phải có ít nhất 6 ký tự.', 'error');
        } elseif ($new !== $conf) {
            flash_set('Xác nhận mật khẩu không khớp.', 'error');
        } else {
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_make($new), $adminId]);
            flash_set('Đã đổi mật khẩu quản trị.');
        }
    } else {
        $up = $pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        foreach (array_merge($fields, $bankFields) as $key => $_) {
            $val = trim($_POST[$key] ?? '');
            if ($key === 'bank_account_no') $val = preg_replace('/[^A-Za-z0-9]/', '', $val);
            if ($key === 'bank_account_name') $val = vietqr_ascii_upper($val);
            $up->execute([$key, $val]);
        }
        flash_set('Đã lưu cài đặt. Thông tin mới sẽ hiện ở chân trang, trang Liên hệ, hóa đơn và mã QR chuyển khoản.');
    }
    redirect(BASE_URL . '/admin/settings.php');
}

$page_title = 'Cài đặt';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('settings') ?>Cài đặt</h1><p>Thông tin hiển thị trên website và hóa đơn</p></div>
</div>

<div class="two-col">
    <form method="post" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="info">
        <h3>Thông tin resort</h3>
        <?php foreach ($fields as $key => [$label, $default]): ?>
            <div class="form-group"><label for="st-<?= $key ?>"><?= e($label) ?></label><input class="input" id="st-<?= $key ?>" name="<?= $key ?>" value="<?= e(setting($key, $default)) ?>"></div>
        <?php endforeach; ?>

        <h3 style="margin-top:22px">Tài khoản nhận chuyển khoản (tạo mã QR thanh toán)</h3>
        <p class="muted" style="font-size:13px;margin:-6px 0 14px">Nhập đúng số tài khoản thật để khách quét QR chuyển khoản được bằng app ngân hàng của họ.</p>
        <div class="form-group"><label for="st-bank_bin">Ngân hàng</label>
            <select class="input" id="st-bank_bin" name="bank_bin">
                <?php foreach (vietqr_bank_list() as $bin => $name): ?>
                    <option value="<?= $bin ?>"<?= setting('bank_bin', '970436') === $bin ? ' selected' : '' ?>><?= e($name) ?> (<?= $bin ?>)</option>
                <?php endforeach; ?>
            </select></div>
        <?php foreach ($bankFields as $key => [$label, $default]): if ($key === 'bank_bin') continue; ?>
            <div class="form-group"><label for="st-<?= $key ?>"><?= e($label) ?></label><input class="input" id="st-<?= $key ?>" name="<?= $key ?>" value="<?= e(setting($key, $default)) ?>" <?= $key === 'bank_account_no' ? 'placeholder="VD: 0123456789"' : '' ?>></div>
        <?php endforeach; ?>
        <?php if (setting('bank_account_no', '') !== ''): ?>
            <div class="qr-preview">
                <?= vietqr_svg(vietqr_payload(setting('bank_bin', '970436'), setting('bank_account_no', ''), 100000, 'DEMO XEM TRUOC', setting('bank_account_name', setting('resort_name', 'BlueSea Resort'))), 4) ?>
                <small class="muted">Xem thử mã QR (số tiền minh họa 100.000đ)</small>
            </div>
        <?php endif; ?>
        <button class="btn btn-primary">Lưu cài đặt</button>
    </form>

    <form method="post" class="form-card" style="align-self:start">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">
        <h3>Đổi mật khẩu quản trị</h3>
        <p class="muted" style="margin-bottom:14px;font-size:14px">Tài khoản đang đăng nhập: <b><?= e($_SESSION['admin']['email']) ?></b></p>
        <div class="form-group"><label for="pw-cur">Mật khẩu hiện tại</label><input class="input" id="pw-cur" type="password" name="current_password" autocomplete="current-password" required></div>
        <div class="form-group"><label for="pw-new">Mật khẩu mới</label><input class="input" id="pw-new" type="password" name="new_password" minlength="6" autocomplete="new-password" required></div>
        <div class="form-group"><label for="pw-conf">Xác nhận mật khẩu mới</label><input class="input" id="pw-conf" type="password" name="confirm_password" minlength="6" autocomplete="new-password" required></div>
        <button class="btn btn-primary">Đổi mật khẩu</button>
    </form>
</div>
<?php include '../includes/admin_footer.php'; ?>
