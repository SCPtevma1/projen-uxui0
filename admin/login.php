<?php
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
require_once 'db.php';

$error = '';

// --- ตั้งค่าระบบป้องกัน (Rate Limiting Settings) ---
$max_attempts = 5;       // จำนวนครั้งสูงสุดที่อนุญาตให้ล็อกอินผิด
$lockout_minutes = 15;   // เวลาที่ระงับการล็อกอิน (นาที)

// ฟังก์ชันดึง IP Address ที่แท้จริงของผู้ใช้งาน
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ฟังก์ชันบันทึกประวัติการล็อกอินล้มเหลวลงฐานข้อมูล
function record_failed_attempt($conn, $ip, $username, $current_failed, $max_attempts, &$error) {
    $stmt_log = $conn->prepare("INSERT INTO login_logs (ip_address, username, attempted_at) VALUES (?, ?, NOW())");
    if ($stmt_log) {
        $stmt_log->bind_param("ss", $ip, $username);
        $stmt_log->execute();
        $stmt_log->close();
    }

    $new_failed_count = $current_failed + 1;
    $remaining_attempts = $max_attempts - $new_failed_count;

    if ($remaining_attempts <= 0) {
        $error = "คุณกรอกรหัสผ่านผิดเกินกำหนด IP ของคุณถูกระงับชั่วคราวเป็นเวลา 15 นาที";
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาสอีก {$remaining_attempts} ครั้ง)";
    }
}

$user_ip = getUserIP();

// 1. ตรวจสอบจำนวนครั้งที่ล็อกอินผิดจาก IP นี้ภายในระยะเวลาที่กำหนด
$failed_attempts = 0;
$is_locked_out = false;

$stmt_check = $conn->prepare("
    SELECT COUNT(*) AS failed_count, MAX(attempted_at) AS last_attempt 
    FROM login_logs 
    WHERE ip_address = ? AND attempted_at >= NOW() - INTERVAL ? MINUTE
");

if ($stmt_check) {
    $stmt_check->bind_param("si", $user_ip, $lockout_minutes);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result()->fetch_assoc();
    $failed_attempts = $check_result['failed_count'] ?? 0;
    $stmt_check->close();

    if ($failed_attempts >= $max_attempts) {
        $is_locked_out = true;
        $last_attempt_time = strtotime($check_result['last_attempt']);
        $unlock_time = $last_attempt_time + ($lockout_minutes * 60);
        $remaining_seconds = $unlock_time - time();
        $remaining_minutes = ceil(max(1, $remaining_seconds) / 60);

        $error = "IP ของคุณ ({$user_ip}) ถูกระงับชั่วคราวเนื่องจากล็อกอินผิดเกินกำหนด กรุณาลองใหม่ในอีก {$remaining_minutes} นาที";
    }
}

// 2. การประมวลผลเมื่อส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_locked_out) {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
            if (!$stmt) {
                die("SQL Error: " . $conn->error);
            }
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    // ล็อกอินสำเร็จ -> ลบประวัติการล็อกอินผิดของ IP นี้
                    $stmt_clear = $conn->prepare("DELETE FROM login_logs WHERE ip_address = ?");
                    if ($stmt_clear) {
                        $stmt_clear->bind_param("s", $user_ip);
                        $stmt_clear->execute();
                        $stmt_clear->close();
                    }

                    session_regenerate_id(true);
                    $_SESSION['admin_user'] = $username;
                    header("Location: dashboard.php");
                    exit;
                } else {
                    record_failed_attempt($conn, $user_ip, $username, $failed_attempts, $max_attempts, $error);
                }
            } else {
                record_failed_attempt($conn, $user_ip, $username, $failed_attempts, $max_attempts, $error);
            }
            $stmt->close();
        } else {
            $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 20px;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1); padding: 40px 32px;
            border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            width: 100%; max-width: 400px; color: #f8fafc;
        }
        .brand-header { text-align: center; margin-bottom: 28px; }
        .brand-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; color: #ffffff; margin-bottom: 12px;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        .brand-header h2 { font-size: 22px; font-weight: 600; color: #ffffff; }
        .brand-header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .error-alert {
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5; padding: 12px 14px; border-radius: 8px;
            font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; line-height: 1.4;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 13px; font-weight: 500; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i.input-icon { position: absolute; left: 14px; color: #64748b; font-size: 15px; }
        .input-wrapper input {
            width: 100%; padding: 12px 14px 12px 42px; background: #0f172a;
            border: 1px solid #334155; border-radius: 8px; color: #ffffff; font-size: 14px; outline: none; transition: all 0.2s;
        }
        .input-wrapper input:disabled { background: #1e293b; cursor: not-allowed; opacity: 0.6; }
        .input-wrapper input:focus:not(:disabled) { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .toggle-password { position: absolute; right: 14px; color: #64748b; cursor: pointer; font-size: 15px; }
        .toggle-password:hover { color: #cbd5e1; }
        .btn-submit {
            width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff; border: none; border-radius: 8px; cursor: pointer;
            font-size: 15px; font-weight: 500; transition: all 0.2s; margin-top: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-submit:disabled { background: #475569; box-shadow: none; cursor: not-allowed; opacity: 0.7; }
        .btn-submit:hover:not(:disabled) { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); }
        .footer-link { text-align: center; margin-top: 24px; }
        .footer-link a { color: #94a3b8; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .footer-link a:hover { color: #3b82f6; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <div class="brand-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h2>Admin Portal</h2>
            <p>กรุณาเข้าสู่ระบบเพื่อจัดการข้อมูล</p>
        </div>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 16px; flex-shrink: 0;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้งาน</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="Username" required autocomplete="off" <?= $is_locked_out ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required <?= $is_locked_out ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit" <?= $is_locked_out ? 'disabled' : '' ?>>
                <i class="fa-solid fa-right-to-bracket" style="margin-right: 6px;"></i> 
                <?= $is_locked_out ? 'ถูกระงับชั่วคราว' : 'เข้าสู่ระบบ' ?>
            </button>

            <div class="footer-link">
                <a href="../index.php">
                    <i class="fa-solid fa-arrow-left"></i> กลับไปยังหน้าหลัก
                </a>
            </div>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>