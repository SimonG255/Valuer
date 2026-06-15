<?php
// logout.php
require_once 'includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
