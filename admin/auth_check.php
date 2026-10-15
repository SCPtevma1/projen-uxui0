<?php
/**
 * auth_check.php — ตรวจสอบสิทธิ์ Admin Session
 * Include ไฟล์นี้ในทุกหน้า Admin เพื่อป้องกันการเข้าถึงโดยไม่ได้ล็อกอิน
 *
 * วิธีใช้:
 *   session_start();
 *   require_once 'auth_check.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_user'])) {
    // เก็บ URL ที่กำลังจะเข้า เพื่อ Redirect กลับหลังจาก Login สำเร็จ
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $_SESSION['redirect_after_login'] = $currentUrl;

    header('Location: login.php');
    exit;
}

// สร้าง CSRF Token ถ้ายังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
