<?php
require_once 'config/database.php';
require_once 'config/app.php';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', '');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
    if ($name === '' || mb_strlen($name) > 120) {
        $error = 'Vui lòng nhập họ tên hợp lệ.';
    } elseif (!valid_phone($phone)) {
        $error = 'Số điện thoại không hợp lệ (8-15 số).';
    } elseif (!valid_email($email)) {
        $error = 'Email không đúng định dạng.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO customers(name,phone,email,password) VALUES(?,?,?,?)");
            $stmt->execute([$name, $phone, $email, password_make($password)]);
            redirect(BASE_URL . '/login.php' . ($next ? '?next=' . urlencode($next) : ''));
        } catch (PDOException $ex) {
            error_log('[register] ' . $ex->getMessage());
            $error = 'Email đã được sử dụng.';
        }
    }
}
$page_title = 'Đăng ký';
$body_class = 'auth-page';
include 'includes/header.php';
?>
<div class="auth-main">
    <div class="auth">
        <a class="brand" href="<?= BASE_URL ?>/index.php"><img src="<?= img('logo.svg') ?>" alt=""><span class="brand-name">BlueSea Resort</span></a>
        <h1>Tạo tài khoản</h1>
        <p class="sub">Chỉ mất một phút để bắt đầu kỳ nghỉ của bạn</p>
        <?php if ($error): ?><div class="alert error" role="alert"><?= icon('circle-x') ?><span><?= e($error) ?></span></div><?php endif; ?>
        <form method="post" data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="form-group"><label for="name">Họ và tên</label><input class="input" id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" autocomplete="name" data-rule="required maxlen:120" required></div>
            <div class="form-group"><label for="phone">Số điện thoại</label><input class="input" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" autocomplete="tel" inputmode="tel" data-rule="required phone" required></div>
            <div class="form-group"><label for="email">Email</label><input class="input" id="email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" data-rule="required email" required></div>
            <div class="form-group"><label for="password">Mật khẩu (tối thiểu 6 ký tự)</label><input class="input" id="password" type="password" name="password" minlength="6" autocomplete="new-password" data-rule="required minlen:6" required></div>
            <button class="btn btn-primary btn-block" style="height:48px">Đăng ký</button>
        </form>
        <p class="alt">Đã có tài khoản? <a href="<?= BASE_URL ?>/login.php">Đăng nhập</a></p>
    </div>
</div>
<?php $hide_footer = true; include 'includes/footer.php'; ?>
