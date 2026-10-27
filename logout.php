<?php
require_once 'config/app.php';
unset($_SESSION['customer']); redirect(BASE_URL.'/index.php');
?>