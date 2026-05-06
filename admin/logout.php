<?php
/**
 * Sintesi - Admin Çıkış
 */
require_once __DIR__ . '/../config.php';

session_destroy();
header('Location: index.php');
exit;
?>
