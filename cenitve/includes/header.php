<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = trenutniUporabnik();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= $base ?? '' ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $base ?? '' ?>index.php" class="logo">
            <span class="logo-icon">◈</span>
            <span class="logo-text"><?= APP_NAME ?></span>
        </a>
        <?php if ($user): ?>
        <nav class="nav">
            <span class="nav-user">Pozdravljeni, <strong><?= e($user['ime']) ?></strong></span>
            <a href="<?= $base ?? '' ?>cenitve.php" class="nav-link">Cenitve</a>
            <a href="<?= $base ?? '' ?>logout.php" class="btn btn-sm btn-ghost">Odjava</a>
        </nav>
        <?php else: ?>
        <nav class="nav">
            <a href="<?= $base ?? '' ?>login.php" class="nav-link">Prijava</a>
            <a href="<?= $base ?? '' ?>register.php" class="btn btn-sm btn-primary">Registracija</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="main-content">
