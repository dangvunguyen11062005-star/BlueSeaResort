<?php
require_once 'config/database.php';
require_once 'config/app.php';
$page_title = 'Liên hệ';
$page_description = 'Liên hệ BlueSea Resort: địa chỉ, số điện thoại, email và biểu mẫu gửi tin nhắn cho lễ tân.';
$sent = false;
$contactError = '';
$logged = $_SESSION['customer'] ?? null;
$cName = $logged['name'] ?? ''; $cEmail = $logged['email'] ?? ''; $cMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $cName = trim($_POST['name'] ?? ''); $cEmail = trim($_POST['email'] ?? ''); $cMsg = trim($_POST['message'] ?? '');
    if (trim($_POST['website'] ?? '') !== '') {
        $sent = true; $cMsg = ''; // ô "website" ẩn chỉ bot mới điền: giả vờ thành công, không lưu
    } elseif ($cName === '' || !valid_email($cEmail) || $cMsg === '') {
        $contactError = 'Vui lòng nhập đầy đủ họ tên, email hợp lệ và nội dung.';
    } elseif (mb_strlen($cName) > 120 || mb_strlen($cMsg) > 3000) {
        $contactError = 'Họ tên tối đa 120 ký tự và nội dung tối đa 3000 ký tự.';
    } elseif (!rate_limit_allow($pdo, 'contact', 5, 3600)) {
        $contactError = 'Bạn đã gửi quá nhiều tin nhắn trong thời gian ngắn. Vui lòng thử lại sau hoặc gọi số điện thoại bên dưới.';
    } else {
        try {
            $pdo->prepare("INSERT INTO contact_messages(name,email,message,ip) VALUES(?,?,?,?)")
                ->execute([$cName, $cEmail, $cMsg, client_ip()]);
            $sent = true; $cName = $cEmail = $cMsg = '';
        } catch (Throwable $ex) {
            error_log('[contact] ' . $ex->getMessage());
            // Không báo thành công khi chưa lưu được, để khách không tưởng tin nhắn đã đến nơi
            $contactError = 'Hệ thống chưa nhận được tin nhắn của bạn. Vui lòng thử lại sau hoặc liên hệ qua số điện thoại ' . setting('phone', '0987 654 321') . '.';
        }
    }
}
include 'includes/header.php';
?>
<section class="container">
    <div class="photo page-banner short" style="--bg-img:url('<?= img('beach.svg') ?>')">
        <h1>Liên hệ</h1>
        <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
    </div>
</section>
<section class="section">
    <div class="container contact-grid">
        <div>
            <h2 style="font-size:22px;margin-bottom:6px">Thông tin resort</h2>
            <p class="muted">Ghé thăm hoặc liên hệ với BlueSea Resort qua các kênh dưới đây.</p>
            <div class="info-list">
                <div><span class="ring"><?= icon('map-pin') ?></span><div><b>Địa chỉ</b><span><?= e(setting('address', 'Mũi Né, Phan Thiết, Bình Thuận, Việt Nam')) ?></span></div></div>
                <div><span class="ring"><?= icon('phone') ?></span><div><b>Điện thoại</b><span><?= e(setting('phone', '0987 654 321')) ?></span></div></div>
                <div><span class="ring"><?= icon('mail') ?></span><div><b>Email</b><span><?= e(setting('email', 'hello@bluesearesort.vn')) ?></span></div></div>
                <div><span class="ring"><?= icon('clock') ?></span><div><b>Giờ hỗ trợ</b><span><?= e(setting('support_hours', '08:00 – 22:00 hằng ngày')) ?></span></div></div>
            </div>
            <div class="map-embed" style="position:relative">
                <img src="<?= img('dunes.svg') ?>" alt="Vị trí BlueSea Resort tại Mũi Né, Phan Thiết" style="width:100%;height:100%;object-fit:cover">
                <span style="position:absolute;left:50%;top:50%;transform:translate(-50%,-100%);width:40px;height:40px;color:#e0424d;filter:drop-shadow(0 3px 4px rgba(0,0,0,.35))"><?= icon('map-pin') ?></span>
            </div>
            <p class="muted" style="font-size:12.5px;margin-top:8px">Bản đồ minh họa. Vui lòng liên hệ để được hướng dẫn đường đi chi tiết.</p>
        </div>
        <div class="panel" style="padding:30px">
            <h2 style="font-size:20px;margin-bottom:16px">Gửi liên hệ</h2>
            <?php if ($sent): ?>
                <div class="alert success" role="status"><?= icon('circle-check') ?><span>Cảm ơn bạn! Tin nhắn đã được gửi tới lễ tân, chúng tôi sẽ phản hồi qua email sớm nhất có thể.</span></div>
            <?php endif; ?>
            <?php if ($contactError): ?>
                <div class="alert error" role="alert"><?= icon('circle-x') ?><span><?= e($contactError) ?></span></div>
            <?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="form-group"><label for="c-name">Họ tên</label><input class="input" id="c-name" name="name" value="<?= e($cName) ?>" maxlength="120" autocomplete="name" required></div>
                <div class="form-group"><label for="c-email">Email</label><input class="input" id="c-email" type="email" name="email" value="<?= e($cEmail) ?>" maxlength="150" autocomplete="email" required></div>
                <div class="form-group"><label for="c-msg">Nội dung</label><textarea class="input" id="c-msg" name="message" rows="5" maxlength="3000" required><?= e($cMsg) ?></textarea></div>
                <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <button class="btn btn-primary btn-block">Gửi liên hệ</button>
            </form>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
