<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_customer();
$page_title = 'Thông tin cá nhân';
$account_active = 'profile';
$cust = $_SESSION['customer'];
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $address = trim($_POST['address'] ?? '');
    if ($name === '' || mb_strlen($name) > 120) {
        $error = 'Vui lòng nhập họ tên hợp lệ (tối đa 120 ký tự).';
    } elseif (!valid_phone($phone)) {
        $error = 'Số điện thoại không hợp lệ (8-15 số).';
    } elseif (mb_strlen($address) > 255) {
        $error = 'Địa chỉ quá dài (tối đa 255 ký tự).';
    } else {
        $pdo->prepare("UPDATE customers SET name=?, phone=?, address=? WHERE id=?")->execute([$name, $phone, $address, $cust['id']]);
        $_SESSION['customer']['name'] = $name;
        $_SESSION['customer']['phone'] = $phone;
        $_SESSION['customer']['address'] = $address;
        $cust = $_SESSION['customer'];
        $success = 'Đã lưu thông tin cá nhân.';
    }
}
include '../includes/header.php';
?>
<?php include '../includes/account_top.php'; ?>
<h1>Thông tin cá nhân</h1>
<?php if ($error): ?><div class="alert error" role="alert" style="margin-top:16px"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
<?php if ($success): ?><div class="alert success" role="status" style="margin-top:16px"><?= icon('circle-check') ?><span><?= e($success) ?></span></div><?php endif; ?>
<form method="post" class="card-form" style="margin-top:22px">
    <?= csrf_field() ?>
    <div class="form-group"><label for="p-name">Họ và tên</label><input class="input" id="p-name" name="name" value="<?= e($cust['name']) ?>" maxlength="120" required></div>
    <div class="form-group"><label for="p-phone">Số điện thoại</label><input class="input" id="p-phone" name="phone" value="<?= e($cust['phone']) ?>" inputmode="tel" maxlength="15" pattern="[0-9+\-\s]{8,15}" title="8-15 ký tự gồm số, +, -" required></div>
    <div class="form-group"><label for="p-email">Email</label><input class="input" id="p-email" value="<?= e($cust['email']) ?>" readonly></div>
    <div class="form-group"><label for="p-address">Địa chỉ</label><input class="input" id="p-address" name="address" value="<?= e($cust['address'] ?? '') ?>" maxlength="255"></div>
    <button class="btn btn-primary">Lưu thay đổi</button>
</form>
<?php include '../includes/account_bottom.php'; ?>
<?php include '../includes/footer.php'; ?>
