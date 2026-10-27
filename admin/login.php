<?php
require_once '../config/database.php';
require_once '../config/app.php';
if (is_admin_logged_in()) redirect(BASE_URL . '/admin/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
    if (!valid_email($email)) {
        $error = 'Email không đúng định dạng.';
    } else {
        $s = $pdo->prepare("SELECT * FROM admins WHERE email=?");
        $s->execute([$email]);
        $a = $s->fetch();
        if ($a && password_matches($password, $a['password'])) {
            password_maybe_upgrade($pdo, 'admins', $a['id'], $password, $a['password']);
            session_regenerate_id(true);
            $_SESSION['admin'] = $a;
            redirect(BASE_URL . '/admin/index.php');
        } else {
            $error = 'Email hoặc mật khẩu quản trị không đúng.';
        }
    }
}
$page_title = 'Đăng nhập quản trị';
$body_class = 'auth-page';
include '../includes/header.php';
?>
<div class="auth-main">
    <div class="auth">
        <a class="brand" href="<?= BASE_URL ?>/index.php"><img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name">BlueSea Resort</span></a>
        <h1>Đăng nhập quản trị</h1>
        <p class="sub">Khu vực dành cho quản trị viên</p>
        <?php if ($error): ?><div class="alert error" role="alert"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
        <form method="post" data-validate>
            <?= csrf_field() ?>
            <div class="form-group"><label for="a-email">Email</label><input class="input" id="a-email" type="email" name="email" autocomplete="username" data-rule="required email" required></div>
            <div class="form-group"><label for="a-password">Mật khẩu</label><input class="input" id="a-password" type="password" name="password" autocomplete="current-password" data-rule="required" required></div>
            <button class="btn btn-primary btn-block" style="height:48px">Đăng nhập</button>
        </form>
        <p class="alt"><a href="<?= BASE_URL ?>/index.php">← Về trang khách hàng</a></p>
    </div>
</div>
<?php $hide_footer = true; include '../includes/footer.php'; ?>
