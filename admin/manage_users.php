<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

$notice = '';
$error = '';

// จัดการเพิ่มผู้ใช้งาน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน';
        } else {
            // เช็คว่า username ซ้ำหรือไม่
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            if ($checkStmt->get_result()->fetch_assoc()) {
                $error = 'ชื่อผู้ใช้นี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($insStmt) {
                    $insStmt->bind_param("ss", $username, $hashedPassword);
                    if ($insStmt->execute()) {
                        $notice = "เพิ่มผู้ใช้งาน '$username' สำเร็จแล้ว";
                    } else {
                        $error = 'เพิ่มผู้ใช้ไม่สำเร็จ: ' . $insStmt->error;
                    }
                    $insStmt->close();
                }
            }
            $checkStmt->close();
        }
    }

    if ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        // ป้องกันการลบตัวเอง
        $checkUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $checkUser->bind_param("i", $userId);
        $checkUser->execute();
        $targetUser = $checkUser->get_result()->fetch_assoc();
        $checkUser->close();

        if ($targetUser && $targetUser['username'] === $_SESSION['admin_user']) {
            $error = 'ไม่สามารถลบบัญชีที่กำลังเข้าสู่ระบบอยู่ได้';
        } elseif ($userId > 0) {
            $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($delStmt) {
                $delStmt->bind_param("i", $userId);
                if ($delStmt->execute()) {
                    $notice = 'ลบผู้ใช้งานสำเร็จแล้ว';
                } else {
                    $error = 'ลบผู้ใช้งานไม่สำเร็จ: ' . $delStmt->error;
                }
                $delStmt->close();
            }
        }
    }
}

// ดึงรายชื่อผู้ใช้จริงจากฐานข้อมูล
$usersResult = $conn->query("SELECT id, username, created_at FROM users ORDER BY id ASC");
$userList = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการผู้ใช้งาน | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; font-family: 'Prompt', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        @media (max-width: 767px) { .sidebar { min-height: auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <div class="px-2 py-3 mb-2 text-white fw-bold fs-5 border-bottom border-secondary">
                <i class="bi bi-shield-lock-fill text-primary me-2"></i>Admin Panel
            </div>
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php" class="active"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php"><i class="bi bi-window-stack me-2"></i> จัดการเว็บไซต์</a>
            <a href="manage_comments.php"><i class="bi bi-chat-left-text me-2"></i> จัดการความคิดเห็น</a>
            <a href="manage_footer.php"><i class="bi bi-file-earmark-text me-2"></i> จัดการ Footer</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <h1 class="h3 mb-1">👥 จัดการผู้ใช้งาน (User Management)</h1>
                    <p class="text-secondary mb-0">ดูรายชื่อผู้ใช้งานและผู้ดูแลระบบทั้งหมดในฐานข้อมูล</p>
                </div>
                <div>
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
                    <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                </div>
            </header>

            <?php if ($notice): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($notice) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card panel p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>ผู้ใช้งานทั้งหมด (<?= count($userList) ?> คน)</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus me-1"></i> เพิ่มผู้ใช้งานใหม่
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>ชื่อผู้ใช้งาน (Username)</th>
                                <th>สิทธิ์การใช้งาน</th>
                                <th>วันที่สร้าง</th>
                                <th class="text-end">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($userList)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลผู้ใช้งานในระบบ</td></tr>
                            <?php else: ?>
                                <?php foreach ($userList as $u): 
                                    $isCurrent = ($u['username'] === $_SESSION['admin_user']);
                                ?>
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">#<?= $u['id'] ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($u['username']) ?></strong>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-info-subtle text-info ms-2">บัญชีของคุณ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-success-subtle text-success">ผู้ดูแลระบบ</span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($u['created_at']) ?></small></td>
                                        <td class="text-end">
                                            <?php if (!$isCurrent): ?>
                                                <form method="POST" onsubmit="return confirm('ยืนยันลบผู้ใช้งาน <?= htmlspecialchars($u['username']) ?> หรือไม่?');" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="ลบผู้ใช้"><i class="bi bi-trash"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light text-muted" disabled title="บัญชีปัจจุบัน"><i class="bi bi-lock"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal เพิ่มผู้ใช้งาน -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>เพิ่มผู้ใช้งานใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                        <input type="text" name="username" class="form-control" required autocomplete="off" placeholder="เช่น admin_team">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน (Password)</label>
                        <input type="password" name="password" class="form-control" required autocomplete="new-password" placeholder="รหัสผ่านเข้าสู่ระบบ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึกผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
