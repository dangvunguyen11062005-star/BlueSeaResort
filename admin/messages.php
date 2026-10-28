<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin(); // cả Administrator lẫn Staff đều đọc được tin nhắn của khách

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['do'])) {
    require_csrf();
    $id = (int)$_POST['id'];
    try {
        if ($_POST['do'] === 'delete') {
            $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$id]);
            flash_set('Đã xóa tin nhắn.');
        } elseif (in_array($_POST['do'], ['read', 'new'], true)) {
            $pdo->prepare("UPDATE contact_messages SET status=? WHERE id=?")->execute([$_POST['do'], $id]);
            flash_set($_POST['do'] === 'read' ? 'Đã đánh dấu là đã xử lý.' : 'Đã đánh dấu là chưa xử lý.');
        }
    } catch (Throwable $ex) {
        error_log('[admin][messages] ' . $ex->getMessage());
        flash_set('Không thực hiện được thao tác. Vui lòng thử lại.', 'error');
    }
    redirect(BASE_URL . '/admin/messages.php' . (isset($_POST['tab']) ? '?tab=' . urlencode($_POST['tab']) : ''));
}

$tabs = ['all' => 'Tất cả', 'new' => 'Chưa xử lý', 'read' => 'Đã xử lý'];
$tab = $_GET['tab'] ?? 'new';
if (!isset($tabs[$tab])) $tab = 'new';

$counts = ['all' => 0, 'new' => 0, 'read' => 0];
$items = []; $tableMissing = false;
try {
    foreach ($pdo->query("SELECT status, COUNT(*) n FROM contact_messages GROUP BY status") as $r) { $counts[$r['status']] = (int)$r['n']; $counts['all'] += (int)$r['n']; }
    $st = $pdo->prepare("SELECT * FROM contact_messages" . ($tab !== 'all' ? " WHERE status=?" : "") . " ORDER BY created_at DESC, id DESC LIMIT 100");
    $st->execute($tab !== 'all' ? [$tab] : []);
    $items = $st->fetchAll();
} catch (Throwable $ex) {
    error_log('[admin][messages] ' . $ex->getMessage());
    $tableMissing = true;
}

$page_title = 'Tin nhắn liên hệ';
include '../includes/admin_header.php';
?>
<div class="admin-head">
    <div><h1><?= icon('mail') ?>Tin nhắn liên hệ</h1><p>Tin nhắn khách gửi từ trang Liên hệ</p></div>
</div>

<?php if ($tableMissing): ?>
    <div class="alert error" role="alert"><?= icon('circle-x') ?><span>Chưa có bảng <code>contact_messages</code>. Hãy import <code>database/upgrade_v3.sql</code>.</span></div>
<?php endif; ?>

<div class="tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab<?= $tab === $key ? ' active' : '' ?>" href="?tab=<?= $key ?>"><?= $label ?> (<?= $counts[$key] ?>)</a>
    <?php endforeach; ?>
</div>

<div class="table-wrap">
    <table class="table">
        <tr><th>Người gửi</th><th>Nội dung</th><th>Ngày gửi</th><th>Trạng thái</th><th>Thao tác</th></tr>
        <?php foreach ($items as $m): $isNew = $m['status'] === 'new'; ?>
        <tr>
            <td><b><?= e($m['name']) ?></b><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
            <td style="max-width:420px;white-space:pre-wrap;word-break:break-word"><?= e($m['message']) ?></td>
            <td><?= date_vn($m['created_at'], 'd/m/Y H:i') ?></td>
            <td><span class="badge badge-<?= $isNew ? 'warn' : 'ok' ?>"><?= $isNew ? 'Chưa xử lý' : 'Đã xử lý' ?></span></td>
            <td class="actions">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                    <input type="hidden" name="do" value="<?= $isNew ? 'read' : 'new' ?>">
                    <button class="act" type="submit" title="<?= $isNew ? 'Đánh dấu đã xử lý' : 'Đánh dấu chưa xử lý' ?>"><?= icon($isNew ? 'circle-check' : 'clock') ?></button></form>
                <form method="post" data-confirm="Xóa tin nhắn của <?= e($m['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
                    <input type="hidden" name="do" value="delete">
                    <button class="act del" type="submit" title="Xóa"><?= icon('trash-2') ?></button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5" style="text-align:center;color:var(--muted);padding:30px">Chưa có tin nhắn nào.</td></tr><?php endif; ?>
    </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
