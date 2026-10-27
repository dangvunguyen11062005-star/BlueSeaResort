<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_customer();
$page_title = 'Đổi mật khẩu';
$account_active = 'password';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? ''; $confirm = $_POST['confirm_password'] ?? '';
    $stmt = $pdo->prepare("SELECT password FROM customers WHERE id=?");
    $stmt->execute([$_SESSION['customer']['id']]);
    $hash = $stmt->fetchColumn();
    if (!password_matches($cur, $hash)) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($new) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($new !== $confirm) {
        $error = 'Xác nhận mật khẩu không khớp.';
    } else {
        $pdo->prepare("UPDATE customers SET password=? WHERE id=?")->execute([password_make($new), $_SESSION['customer']['id']]);
        $success = 'Đã đổi mật khẩu thành công.';
    }
}
include '../includes/header.php';
?>
<?php include '../includes/account_top.php'; ?>
<h1>Đổi mật khẩu</h1>
<?php if ($error): ?><div class="alert error" role="alert" style="margin-top:16px"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
<?php if ($success): ?><div class="alert success" role="status" style="margin-top:16px"><?= icon('circle-check') ?><span><?= e($success) ?></span></div><?php endif; ?>
<form method="post" class="card-form" style="margin-top:22px">
    <?= csrf_field() ?>
    <div class="form-group"><label for="cur">Mật khẩu hiện tại</label><input class="input" id="cur" type="password" name="current_password" autocomplete="current-password" required></div>
    <div class="form-group"><label for="new">Mật khẩu mới</label><input class="input" id="new" type="password" name="new_password" minlength="6" autocomplete="new-password" required></div>
    <div class="form-group"><label for="confirm">Xác nhận mật khẩu mới</label><input class="input" id="confirm" type="password" name="confirm_password" minlength="6" autocomplete="new-password" required></div>
    <button class="btn btn-primary">Đổi mật khẩu</button>
</form>
<?php include '../includes/account_bottom.php'; ?>
<?php include '../includes/footer.php'; ?>
