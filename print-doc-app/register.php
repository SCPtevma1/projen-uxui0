<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (empty($_POST['agree_pdpa'])) {
        $error = 'กรุณายอมรับนโยบายข้อมูลส่วนบุคคลก่อนสมัครสมาชิก';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            $error = 'กรุณาตรวจสอบข้อมูล และใช้รหัสผ่านอย่างน้อย 10 ตัวอักษร';
        } else {
            try {
                $statement = $pdo->prepare('INSERT INTO print_doc_users (name, email, password_hash) VALUES (?, ?, ?)');
                $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                header('Location: ' . APP_URL . '/index.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'อีเมลนี้มีบัญชีอยู่แล้ว';
                } else {
                    throw $exception;
                }
            }
        }
    }
}
$pageTitle = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <p class="eyebrow">เริ่มใช้งาน</p>
    <h1>สร้างบัญชี PrintDoc</h1>
    <p class="muted">สมัครเพื่อจัดเก็บเอกสารและติดตามคำสั่งพิมพ์ของคุณ</p>
    <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>ชื่อที่ใช้แสดง<input name="name" maxlength="120" autocomplete="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>อีเมล<input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>รหัสผ่าน<input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
        <label class="check-row"><input type="checkbox" name="agree_pdpa" value="1" required> ยอมรับ <a href="<?= APP_URL ?>/pdpa-terms.php">นโยบายข้อมูลส่วนบุคคล</a></label>
        <button class="button button-primary" type="submit">สมัครสมาชิก</button>
    </form>
    <p class="auth-switch">มีบัญชีแล้ว? <a href="<?= APP_URL ?>/login.php">เข้าสู่ระบบ</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
