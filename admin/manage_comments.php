<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = '';
$error = '';

// จัดการคำสั่ง POST (ลบข้อความ, เปลี่ยนสถานะข้อความ, ลบคอมเมนต์)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. เปลี่ยนสถานะข้อความติดต่อ (อ่านแล้ว / ยังไม่อ่าน)
        if ($action === 'toggle_message_status') {
            $msgId = filter_var($_POST['msg_id'] ?? null, FILTER_VALIDATE_INT);
            $newStatus = ($_POST['new_status'] ?? '') === 'read' ? 'read' : 'unread';
            if ($msgId) {
                $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $newStatus, $msgId);
                if ($stmt->execute()) {
                    $notice = 'อัปเดตสถานะข้อความ #' . $msgId . ' แล้ว';
                } else {
                    $error = 'อัปเดตสถานะไม่สำเร็จ: ' . $stmt->error;
                }
                $stmt->close();
            }
        }

        // 2. ลบข้อความติดต่อ
        if ($action === 'delete_message') {
            $msgId = filter_var($_POST['msg_id'] ?? null, FILTER_VALIDATE_INT);
            if ($msgId) {
                $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->bind_param("i", $msgId);
                if ($stmt->execute()) {
                    $notice = 'ลบข้อความติดต่อ #' . $msgId . ' เรียบร้อยแล้ว';
                } else {
                    $error = 'ลบข้อความไม่สำเร็จ: ' . $stmt->error;
                }
                $stmt->close();
            }
        }

        // 3. ลบความคิดเห็น / รีวิว
        if ($action === 'delete_comment') {
            $commId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($commId) {
                $stmt = $conn->prepare("DELETE FROM site_comments WHERE id = ?");
                $stmt->bind_param("i", $commId);
                if ($stmt->execute()) {
                    $notice = 'ลบความคิดเห็น #' . $commId . ' เรียบร้อยแล้ว';
                } else {
                    $error = 'ลบความคิดเห็นไม่สำเร็จ: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// ดึงข้อมูลข้อความติดต่อ
$unreadCountRes = $conn->query("SELECT COUNT(*) AS total FROM contact_messages WHERE status = 'unread'");
$unreadCount = $unreadCountRes ? (int)$unreadCountRes->fetch_assoc()['total'] : 0;
$messagesResult = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC");
$messages = $messagesResult ? $messagesResult->fetch_all(MYSQLI_ASSOC) : [];

// ดึงข้อมูลความคิดเห็น/รีวิว
$commentsResult = $conn->query("SELECT * FROM site_comments ORDER BY created_at DESC, id DESC");
$comments = $commentsResult ? $commentsResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการความคิดเห็น & ข้อความ | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; font-family: 'Prompt', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; border-radius: 8px 8px 0 0; padding: 12px 20px; }
        .nav-tabs .nav-link.active { color: #2563eb; border-color: #e2e8f0 #e2e8f0 #fff; }
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
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php"><i class="bi bi-window-stack me-2"></i> จัดการเว็บไซต์</a>
            <a href="manage_comments.php" class="active">
                <i class="bi bi-chat-left-text me-2"></i> จัดการความคิดเห็น
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_footer.php"><i class="bi bi-file-earmark-text me-2"></i> จัดการ Footer</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <h1 class="h3 mb-1">💬 จัดการความคิดเห็น & ข้อความติดต่อ</h1>
                    <p class="text-secondary mb-0">ตรวจสอบข้อความที่ส่งมาจากหน้าแรก (Contact Us) และรีวิวจากหน้าวิชาเรียน</p>
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

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="commentTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages-pane" type="button" role="tab">
                        <i class="bi bi-envelope-paper-fill me-1 text-primary"></i> ข้อความติดต่อถึงเรา
                        <span class="badge bg-primary ms-1"><?= count($messages) ?></span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $unreadCount ?> ยังไม่อ่าน</span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button" role="tab">
                        <i class="bi bi-star-fill me-1 text-warning"></i> ความคิดเห็นและรีวิวผู้เรียน
                        <span class="badge bg-secondary ms-1"><?= count($comments) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="commentTabContent">
                <!-- แท็บ 1: ข้อความติดต่อจากหน้าบ้าน (Contact Messages) -->
                <div class="tab-pane fade show active" id="messages-pane" role="tabpanel">
                    <div class="card panel p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">รายการข้อความที่ส่งถึงเราทั้งหมด</h5>
                            <span class="text-secondary small">ข้อความล่าสุดจะอยู่ด้านบนสุด</span>
                        </div>

                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                ยังไม่มีข้อความติดต่อจากหน้าบ้านในระบบ
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>สถานะ</th>
                                            <th>ผู้ส่ง</th>
                                            <th>ข้อมูลติดต่อ</th>
                                            <th>หัวข้อเรื่อง</th>
                                            <th>ข้อความ</th>
                                            <th>วันที่เวลา</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $msg): 
                                            $isUnread = ($msg['status'] === 'unread');
                                        ?>
                                            <tr class="<?= $isUnread ? 'table-warning-subtle' : '' ?>">
                                                <td>
                                                    <?php if ($isUnread): ?>
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-envelope-fill me-1"></i>ยังไม่อ่าน</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-envelope-open me-1"></i>อ่านแล้ว</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?= htmlspecialchars($msg['sender_name']) ?></strong></td>
                                                <td>
                                                    <span class="small text-secondary"><?= htmlspecialchars($msg['sender_contact']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-primary"><?= htmlspecialchars($msg['subject']) ?></span>
                                                </td>
                                                <td style="max-width: 320px;">
                                                    <div class="small p-2 bg-light rounded border text-dark">
                                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                                    </div>
                                                </td>
                                                <td class="small text-secondary text-nowrap">
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($msg['created_at']))) ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="toggle_message_status">
                                                        <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $isUnread ? 'read' : 'unread' ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="<?= $isUnread ? 'ทำเครื่องหมายว่าอ่านแล้ว' : 'เปลี่ยนเป็นยังไม่ได้อ่าน' ?>">
                                                            <i class="bi bi-<?= $isUnread ? 'check2-all' : 'envelope' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" onsubmit="return confirm('ยืนยันลบข้อความนี้หรือไม่?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="delete_message">
                                                        <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบข้อความ">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- แท็บ 2: ความคิดเห็นและรีวิวผู้เรียน (Site Comments & Reviews) -->
                <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
                    <div class="card panel p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">ความคิดเห็นและคะแนนรีวิวจากผู้เรียนทั้งหมด</h5>
                            <span class="text-secondary small"><?= count($comments) ?> รายการ</span>
                        </div>

                        <?php if (empty($comments)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-chat-square-text fs-1 d-block mb-2 text-secondary"></i>
                                ยังไม่มีความคิดเห็นในระบบ
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ผู้แสดงความคิดเห็น</th>
                                            <th>คะแนนความพึงพอใจ</th>
                                            <th>ข้อความรีวิว</th>
                                            <th>หน้าเว็บที่ส่งมา</th>
                                            <th>วันที่เวลา</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comments as $c): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary-subtle text-secondary">#<?= (int)$c['id'] ?></span></td>
                                                <td><strong><?= htmlspecialchars($c['user_name']) ?></strong></td>
                                                <td>
                                                    <span class="text-warning"><?= str_repeat('⭐', (int)$c['rating']) ?></span>
                                                    <small class="text-secondary">(<?= (int)$c['rating'] ?>/5)</small>
                                                </td>
                                                <td style="max-width: 360px;">
                                                    <div class="text-dark small"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                                                </td>
                                                <td><span class="badge bg-info-subtle text-info"><?= htmlspecialchars($c['page']) ?></span></td>
                                                <td class="small text-secondary text-nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['created_at']))) ?></td>
                                                <td class="text-end">
                                                    <form method="POST" onsubmit="return confirm('ยืนยันลบความคิดเห็นนี้?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="delete_comment">
                                                        <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบความคิดเห็น">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
