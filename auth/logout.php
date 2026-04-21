<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

session_destroy();
header('Location: /facturation/auth/login.php');
exit;
?>