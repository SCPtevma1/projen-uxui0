<?php
/**
 * includes/head.php — Head HTML ส่วนกลาง
 * ตัวแปรที่ต้องกำหนดก่อน include:
 *   $pageTitle (string) — ชื่อหน้า เช่น 'ภาพรวมระบบ'
 */
$pageTitle = $pageTitle ?? 'Admin Panel';
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; font-family: 'Prompt', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; margin-bottom: 4px; transition: background .15s; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel, .stat-card { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .stat-card { height: 100%; background: #ffffff; }
        .stat-icon { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 12px; font-size: 1.35rem; }
        @media (max-width: 767px) { .sidebar { min-height: auto; } }
        <?= $extraCss ?? '' ?>
    </style>
</head>
<body>
