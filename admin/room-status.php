<?php
require_once '../config/database.php';
require_once '../config/app.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['available', 'occupied', 'maintenance'], true)) {
        $pdo->prepare("UPDATE rooms SET status=? WHERE id=?")->execute([$status, $id]);
        flash_set('Đã cập nhật trạng thái phòng.');
    } else {
        flash_set('Trạng thái không hợp lệ.', 'error');
    }
}
redirect(BASE_URL . '/admin/rooms.php');
