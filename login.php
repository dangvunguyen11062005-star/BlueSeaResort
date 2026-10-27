<?php
require_once 'config/database.php';
require_once 'config/app.php';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', BASE_URL . '/customer/history.php');
if (is_customer_logged_in()) redirect($next);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
    if (!valid_email($email)) {
        $error = 'Email không đúng định dạng.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email=?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_matches($password, $u['password'])) {
            password_maybe_upgrade($pdo, 'customers', $u['id'], $password, $u['password']);
            session_regenerate_id(true); // tránh session fixation khi đổi quyền từ khách vãng lai -> đã đăng nhập
            $_SESSION['customer'] = $u;
            redirect($next);
        } else {
            $error = 'Email hoặc mật khẩu không đúng.';
        }
    }
}
$page_title = 'Đăng nhập';
$body_class = 'auth-page';
include 'includes/header.php';
?>
<div class="auth-main">
    <div class="auth">
        <a class="brand" href="<?= BASE_URL ?>/index.php"><img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name">BlueSea Resort</span></a>
        <h1>Đăng nhập</h1>
        <p class="sub">Đăng nhập để đặt phòng và xem lịch sử của bạn</p>
        <?php if ($error): ?><div class="alert error" role="alert"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="form-group"><label for="email">Email</label><input class="input" id="email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required></div>
            <div class="form-group"><label for="password">Mật khẩu</label><input class="input" id="password" type="password" name="password" autocomplete="current-password" required></div>
            <button class="btn btn-primary btn-block" style="height:48px">Đăng nhập</button>
        </form>
        <p class="alt">Chưa có tài khoản? <a href="<?= BASE_URL ?>/register.php<?= $next !== BASE_URL . '/customer/history.php' ? '?next=' . urlencode($next) : '' ?>">Đăng ký</a></p>
    </div>
</div>
<?php $hide_footer = true; include 'includes/footer.php'; ?>
