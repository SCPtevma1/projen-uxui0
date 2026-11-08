<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../admin/db.php';

if (empty($_SESSION['parts_register_csrf'])) {
    $_SESSION['parts_register_csrf'] = bin2hex(random_bytes(32));
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$error = '';
$success = false;
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals((string) $_SESSION['parts_register_csrf'], $token)) {
        $error = 'เซสชันหมดอายุ กรุณาลองส่งแบบฟอร์มอีกครั้ง';
    } else {
        $fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $passwordConfirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
        $consent = isset($_POST['consent']) && $_POST['consent'] === '1';

        if (
            $fullName === ''
            || mb_strlen($fullName, 'UTF-8') > 120
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 190
            || !preg_match('/\A[0-9+\s().-]{8,30}\z/', $phone)
            || mb_strlen($password, 'UTF-8') < 10
            || strlen($password) > 72
            || $password !== $passwordConfirmation
            || !$consent
        ) {
            $error = 'กรุณาตรวจสอบข้อมูลให้ครบถ้วน รหัสผ่านต้องมีอย่างน้อย 10 ตัวอักษรและต้องตรงกัน';
        } else {
            $statement = $conn->prepare(
                'INSERT INTO parts_members (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
            );
            if (!$statement) {
                error_log('Unable to prepare parts member registration: ' . $conn->error);
                http_response_code(500);
                $error = 'ระบบสมัครสมาชิกขัดข้องชั่วคราว กรุณาลองใหม่ภายหลัง';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement->bind_param('ssss', $fullName, $email, $phone, $passwordHash);
                if ($statement->execute()) {
                    $success = true;
                    $fullName = '';
                    $email = '';
                    $phone = '';
                    $_SESSION['parts_register_csrf'] = bin2hex(random_bytes(32));
                } elseif ($statement->errno === 1062) {
                    $error = 'อีเมลนี้สมัครสมาชิกแล้ว กรุณาใช้อีเมลอื่น';
                } else {
                    error_log('Unable to save parts member registration: ' . $statement->error);
                    http_response_code(500);
                    $error = 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่ภายหลัง';
                }
                $statement->close();
            }
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="สมัครสมาชิก Projen Parts เพื่อสอบถามอะไหล่โน้ตบุ๊กและมือถือ">
  <title>สมัครสมาชิก | Projen Parts</title>
  <link rel="stylesheet" href="dadw/Our Services.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="Our Services.html" aria-label="กลับหน้าร้าน Projen Parts">
        <span class="brand-mark">P</span>
        <span>Projen Parts<small>NOTEBOOK & MOBILE</small></span>
      </a>
      <nav class="nav-links" aria-label="เมนูหลัก">
        <a href="Our Services.html#catalog">เลือกซื้ออะไหล่</a>
        <a href="Our Services.html#contact">ติดต่อ</a>
      </nav>
      <div class="nav-actions">
        <a class="login-link" href="../index.php">หน้าหลัก</a>
        <a class="button button-small" href="Our Services.html">กลับหน้าร้าน</a>
      </div>
    </div>
  </header>

  <main class="auth-section">
    <div class="container auth-layout">
      <div class="auth-intro">
        <p class="eyebrow"><span class="eyebrow-dot"></span> สมัครสมาชิก</p>
        <h1>เริ่มต้นติดต่อ<br><span>Projen Parts</span></h1>
        <p>สร้างบัญชีสมาชิกเพื่อฝากข้อมูลติดต่อ และให้ทีมงานช่วยตรวจสอบอะไหล่ที่ตรงกับรุ่นอุปกรณ์ของคุณ</p>
        <a class="product-link" href="Our Services.html">← กลับไปเลือกดูอะไหล่</a>
      </div>

      <section class="auth-card" aria-labelledby="register-title">
        <h2 id="register-title">สร้างบัญชีสมาชิก</h2>
        <p>กรอกข้อมูลให้ครบถ้วน ช่องที่มีเครื่องหมาย * จำเป็นต้องกรอก</p>

        <?php if ($success): ?>
          <div class="form-message form-message-success" role="status">
            สมัครสมาชิกเรียบร้อยแล้ว ทีมงานพร้อมช่วยตรวจสอบอะไหล่ให้คุณ
          </div>
        <?php elseif ($error !== ''): ?>
          <div class="form-message" role="alert"><?= $escape($error) ?></div>
        <?php endif; ?>

        <form class="register-form" method="post" action="register.php">
          <input type="hidden" name="csrf_token" value="<?= $escape((string) $_SESSION['parts_register_csrf']) ?>">
          <label>
            ชื่อ-นามสกุล *
            <input type="text" name="full_name" maxlength="120" autocomplete="name" required value="<?= $escape($fullName) ?>">
          </label>
          <label>
            อีเมล *
            <input type="email" name="email" maxlength="190" autocomplete="email" required value="<?= $escape($email) ?>">
          </label>
          <label>
            เบอร์โทรศัพท์ *
            <input type="tel" name="phone" maxlength="30" autocomplete="tel" required value="<?= $escape($phone) ?>">
          </label>
          <label>
            รหัสผ่าน *
            <input type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" required>
          </label>
          <label>
            ยืนยันรหัสผ่าน *
            <input type="password" name="password_confirmation" minlength="10" maxlength="72" autocomplete="new-password" required>
          </label>
          <label class="register-consent">
            <input type="checkbox" name="consent" value="1" required>
            <span>ยินยอมให้จัดเก็บข้อมูลเพื่อสร้างบัญชีและติดต่อกลับเกี่ยวกับการสอบถามอะไหล่</span>
          </label>
          <button class="button" type="submit">สมัครสมาชิก</button>
        </form>
        <p class="auth-footnote">ข้อมูลบัญชีจัดเก็บแยกจากบัญชีผู้ดูแลระบบ และรหัสผ่านจะถูกเข้ารหัสก่อนบันทึก</p>
      </section>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-bottom">
      <span>© <?= date('Y') ?> Projen Parts</span>
      <a href="Our Services.html">กลับหน้าร้าน</a>
    </div>
  </footer>
</body>
</html>
