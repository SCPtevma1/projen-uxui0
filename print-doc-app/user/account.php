<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$error = '';
$success = (string) ($_SESSION['account_success'] ?? '');
unset($_SESSION['account_success']);
$name = (string) $user['name'];
$email = (string) $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (($_POST['action'] ?? '') === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $error = 'กรุณาตรวจสอบชื่อและอีเมลอีกครั้ง';
        } else {
            try {
                $statement = $pdo->prepare('UPDATE print_doc_users SET name = ?, email = ? WHERE id = ?');
                $statement->execute([$name, $email, $user['id']]);
                $_SESSION['account_success'] = 'บันทึกข้อมูลบัญชีแล้ว';
                header('Location: ' . APP_URL . '/user/account.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'อีเมลนี้ถูกใช้กับบัญชีอื่นแล้ว';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($newPassword) < 10) {
            $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 10 ตัวอักษร';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
        } else {
            $statement = $pdo->prepare('SELECT password_hash FROM print_doc_users WHERE id = ?');
            $statement->execute([$user['id']]);
            $passwordHash = $statement->fetchColumn();
            if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } else {
                $statement = $pdo->prepare('UPDATE print_doc_users SET password_hash = ? WHERE id = ?');
                $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
                session_regenerate_id(true);
                $_SESSION['account_success'] = 'เปลี่ยนรหัสผ่านแล้ว';
                header('Location: ' . APP_URL . '/user/account.php');
                exit;
            }
        }
    } else {
        $error = 'ไม่พบรายการที่ต้องการดำเนินการ';
    }
}

$pageTitle = 'บัญชีของฉัน';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">จัดการบัญชี</p>
    <h1>บัญชีของฉัน</h1>
    <p class="muted">แก้ไขข้อมูลส่วนตัวและรหัสผ่านของบัญชี PrintDoc</p>
    <a class="button button-secondary" href="<?= APP_URL ?>/user/dashboard.php">กลับไปคลังเอกสาร</a>
</section>

<?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><p class="success-message" role="status"><?= e($success) ?></p><?php endif; ?>

<section class="content-card" aria-labelledby="profile-title">
    <div class="section-heading">
        <div><p class="eyebrow">ข้อมูลส่วนตัว</p><h2 id="profile-title">ชื่อและอีเมล</h2></div>
    </div>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update_profile">
        <label>ชื่อที่ใช้แสดง
            <input name="name" maxlength="120" autocomplete="name" required value="<?= e($name) ?>">
        </label>
        <label>อีเมล
            <input type="email" name="email" maxlength="190" autocomplete="email" required value="<?= e($email) ?>">
        </label>
        <button class="button button-primary" type="submit">บันทึกข้อมูล</button>
    </form>
</section>

<section class="content-card" aria-labelledby="password-title">
    <div class="section-heading">
        <div><p class="eyebrow">ความปลอดภัย</p><h2 id="password-title">เปลี่ยนรหัสผ่าน</h2></div>
    </div>
    <form method="post" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="change_password">
        <label>รหัสผ่านปัจจุบัน
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <label>รหัสผ่านใหม่
            <input type="password" name="new_password" minlength="10" autocomplete="new-password" required>
        </label>
        <label>ยืนยันรหัสผ่านใหม่
            <input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required>
        </label>
        <p class="hint">ใช้รหัสผ่านอย่างน้อย 10 ตัวอักษร</p>
        <button class="button button-primary" type="submit">เปลี่ยนรหัสผ่าน</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
