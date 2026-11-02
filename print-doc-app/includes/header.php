<?php
$pageTitle = $pageTitle ?? 'PrintDoc';
$signedInUser = current_user();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | PrintDoc</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= APP_URL ?>/index.php"><span class="brand-mark">P</span> PrintDoc</a>
    <nav aria-label="เมนูหลัก">
        <?php if ($signedInUser): ?>
            <a href="<?= APP_URL ?>/index.php">สั่งพิมพ์</a>
            <a href="<?= APP_URL ?>/user/dashboard.php">คลังเอกสาร</a>
            <a href="<?= APP_URL ?>/user/account.php">บัญชีของฉัน</a>
            <?php if ($signedInUser['role'] === 'admin'): ?>
                <a href="<?= APP_URL ?>/admin/pricing.php">จัดการราคา</a>
            <?php endif; ?>
            <span class="nav-user"><?= e($signedInUser['name']) ?></span>
            <form class="inline-form" action="<?= APP_URL ?>/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="nav-button" type="submit">ออกจากระบบ</button>
            </form>
        <?php else: ?>
            <a href="<?= APP_URL ?>/login.php">เข้าสู่ระบบ</a>
            <a class="nav-cta" href="<?= APP_URL ?>/register.php">สมัครสมาชิก</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page-shell">
