<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $statement = $pdo->prepare('SELECT id, password_hash FROM print_doc_users WHERE email = ?');
        $statement->execute([$email]);
        $account = $statement->fetch();
        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            header('Location: ' . APP_URL . '/index.php');
            exit;
        }
        $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}
$pageTitle = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <p class="eyebrow">ยินดีต้อนรับกลับ</p>
    <h1>เข้าสู่ระบบ</h1>
    <p class="muted">จัดการเอกสารและเริ่มสร้างคำสั่งพิมพ์</p>
    <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>อีเมล<input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>รหัสผ่าน<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button button-primary" type="submit">เข้าสู่ระบบ</button>
    </form>
    <p class="auth-switch">ยังไม่มีบัญชี? <a href="<?= APP_URL ?>/register.php">สมัครสมาชิก</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
