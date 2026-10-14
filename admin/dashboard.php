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

$error = '';
$notice = '';
$statuses = [
    'pending' => 'รอติดต่อ',
    'contacted' => 'ติดต่อแล้ว',
    'confirmed' => 'ยืนยันสั่งซื้อ',
    'completed' => 'ขายสำเร็จ',
    'cancelled' => 'ยกเลิก',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง';
    } else {
        $action = $_POST['action'] ?? 'update_order_status';

        // 1. อัปเดตสถานะคำสั่งซื้อ
        if ($action === 'update_order_status') {
            $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';
            if (!$orderId || !is_string($status) || !isset($statuses[$status])) {
                $error = 'สถานะหรือรายการคำสั่งซื้อไม่ถูกต้อง';
            } else {
                $stmt = $conn->prepare('UPDATE service_orders SET status = ? WHERE id = ?');
                if (!$stmt) {
                    $error = 'เตรียมอัปเดตสถานะไม่สำเร็จ: ' . $conn->error;
                } else {
                    $stmt->bind_param('si', $status, $orderId);
                    if ($stmt->execute()) {
                        $notice = 'อัปเดตสถานะคำสั่งซื้อ #' . $orderId . ' แล้ว';
                    } else {
                        $error = 'อัปเดตสถานะไม่สำเร็จ: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }

        // 2. ลบความคิดเห็นจากหน้าบ้าน
        if ($action === 'delete_comment') {
            $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($commentId) {
                $del = $conn->prepare("DELETE FROM site_comments WHERE id = ?");
                if ($del) {
                    $del->bind_param("i", $commentId);
                    if ($del->execute()) {
                        $notice = 'ลบความคิดเห็น #' . $commentId . ' เรียบร้อยแล้ว';
                    } else {
                        $error = 'ลบความคิดเห็นไม่สำเร็จ: ' . $del->error;
                    }
                    $del->close();
                }
            }
        }
    }
}

// ข้อมูลสรุปภาพรวมคำสั่งซื้อ
$summaryResult = $conn->query(
    "SELECT COUNT(*) AS order_count,
            COALESCE(SUM(status = 'pending'), 0) AS pending_count,
            COALESCE(SUM(status = 'completed'), 0) AS completed_count,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) AS completed_revenue
     FROM service_orders"
);
$usersResult = $conn->query('SELECT COUNT(*) AS total FROM users');
$servicesResult = $conn->query('SELECT COUNT(*) AS total FROM services');
$ordersResult = $conn->query('SELECT * FROM service_orders ORDER BY created_at DESC, id DESC');

// ดึงข้อมูลคอมเมนต์และความคิดเห็นจากหน้าบ้าน
$commentsCountResult = $conn->query("SELECT COUNT(*) AS total FROM site_comments");
$commentCount = $commentsCountResult ? (int)$commentsCountResult->fetch_assoc()['total'] : 0;
$recentCommentsResult = $conn->query("SELECT * FROM site_comments ORDER BY created_at DESC, id DESC LIMIT 30");
$recentComments = $recentCommentsResult ? $recentCommentsResult->fetch_all(MYSQLI_ASSOC) : [];

// ข้อมูลเมนู Footer เพื่ออัปเดตแบบเรียลไทม์
$footerCountResult = $conn->query("SELECT (SELECT COUNT(*) FROM footer_sections) as sections, (SELECT COUNT(*) FROM footer_links) as links");
$footerCounts = $footerCountResult ? $footerCountResult->fetch_assoc() : ['sections' => 0, 'links' => 0];

if (!$summaryResult || !$usersResult || !$servicesResult || !$ordersResult) {
    die('Unable to load dashboard data: ' . $conn->error);
}
$summary = $summaryResult->fetch_assoc();
$userCount = (int) $usersResult->fetch_assoc()['total'];
$serviceCount = (int) $servicesResult->fetch_assoc()['total'];
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ภาพรวมระบบ | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; font-family: 'Prompt', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel, .stat-card { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .stat-card { height: 100%; background: #ffffff; }
        .stat-icon { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 12px; font-size: 1.35rem; }
        .status-select { min-width: 140px; }
        .comment-item { border-left: 4px solid #38bdf8; background: #f8fafc; border-radius: 0 10px 10px 0; padding: 14px; margin-bottom: 12px; }
        .comment-item:hover { background: #f1f5f9; }
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
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
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
                    <h1 class="h3 mb-1">ภาพรวมระบบ (System Overview)</h1>
                    <p class="text-secondary mb-0">ข้อมูลคำสั่งซื้อ ความคิดเห็นจากหน้าบ้าน และการจัดการเมนูเว็บไซต์</p>
                </div>
                <div>
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong> 
                    <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                </div>
            </header>

            <?php if ($notice): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($notice) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <!-- แถบสถิติหลัก -->
            <section class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-secondary small">คำสั่งซื้อทั้งหมด</div>
                                <div class="h3 mt-1 mb-0"><?= number_format((int) $summary['order_count']) ?></div>
                            </div>
                            <span class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-secondary small">รอติดต่อ / ดำเนินการ</div>
                                <div class="h3 mt-1 mb-0 text-warning"><?= number_format((int) $summary['pending_count']) ?></div>
                            </div>
                            <span class="stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-secondary small">ยอดขายที่สำเร็จ</div>
                                <div class="h3 mt-1 mb-0 text-success">฿<?= number_format((float) $summary['completed_revenue'], 2) ?></div>
                            </div>
                            <span class="stat-icon bg-success-subtle text-success"><i class="bi bi-currency-baht"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-secondary small">ความคิดเห็นจากหน้าบ้าน</div>
                                <div class="h3 mt-1 mb-0 text-info"><?= number_format($commentCount) ?></div>
                            </div>
                            <span class="stat-icon bg-info-subtle text-info"><i class="bi bi-chat-left-dots"></i></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- แถบสถิติรอง: เมนูและระบบ -->
            <section class="row g-3 mb-4">
                <div class="col-md-6 col-xl-4">
                    <div class="card panel p-3 bg-white h-100 d-flex flex-row justify-content-between align-items-center">
                        <div>
                            <div class="small text-secondary">เมนูส่วนท้าย (Footer Menu)</div>
                            <div class="fw-bold fs-5 mt-1">
                                <span class="badge bg-primary-subtle text-primary"><?= (int)$footerCounts['sections'] ?> หัวข้อ</span>
                                <span class="badge bg-secondary-subtle text-secondary"><?= (int)$footerCounts['links'] ?> ลิงก์</span>
                            </div>
                        </div>
                        <a href="manage_footer.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i> จัดการเมนู</a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card panel p-3 bg-white h-100 d-flex flex-row justify-content-between align-items-center">
                        <div>
                            <div class="small text-secondary">เว็บไซต์ตัวอย่างที่เปิดขาย</div>
                            <div class="fw-bold fs-5 mt-1"><?= number_format($serviceCount) ?> รายการ</div>
                        </div>
                        <a href="manage_services.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> จัดการเว็บ</a>
                    </div>
                </div>
                <div class="col-md-12 col-xl-4">
                    <div class="card panel p-3 bg-white h-100 d-flex flex-row justify-content-between align-items-center">
                        <div>
                            <div class="small text-secondary">ผู้ดูแลระบบทั้งหมด</div>
                            <div class="fw-bold fs-5 mt-1"><?= number_format($userCount) ?> บัญชี</div>
                        </div>
                        <a href="manage_users.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-gear"></i> ดูรายชื่อ</a>
                    </div>
                </div>
            </section>

            <!-- ส่วนที่ 1: ความคิดเห็นและรีวิวที่ส่งมาจากหน้าบ้าน -->
            <section class="card panel p-3 p-lg-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h4 mb-1"><i class="bi bi-chat-quote-fill text-primary me-2"></i>ความคิดเห็นและคอมเมนต์จากหน้าบ้าน (Live Feedback)</h2>
                        <p class="small text-secondary mb-0">ข้อความรีวิวและข้อเสนอแนะที่ส่งมาจากหน้าเว็บนักเรียน (student.html) หรือหน้าบ้าน</p>
                    </div>
                    <span class="badge bg-primary fs-6"><?= count($recentComments) ?> ความคิดเห็นล่าสุด</span>
                </div>

                <?php if (empty($recentComments)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary"></i>
                        ยังไม่มีความคิดเห็นจากหน้าบ้านเข้ามาในขณะนี้
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ผู้แสดงความคิดเห็น</th>
                                    <th>คะแนน</th>
                                    <th>ข้อความความคิดเห็น</th>
                                    <th>หน้าเว็บ</th>
                                    <th>วันที่ส่ง</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentComments as $comm): ?>
                                    <tr>
                                        <td>#<?= (int)$comm['id'] ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($comm['user_name']) ?></div>
                                        </td>
                                        <td>
                                            <span class="text-warning">
                                                <?= str_repeat('⭐', (int)$comm['rating']) ?>
                                            </span>
                                            <small class="text-secondary">(<?= (int)$comm['rating'] ?>/5)</small>
                                        </td>
                                        <td style="max-width: 380px;">
                                            <div class="text-dark"><?= nl2br(htmlspecialchars($comm['comment'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info"><?= htmlspecialchars($comm['page']) ?></span>
                                        </td>
                                        <td class="text-nowrap small text-secondary">
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($comm['created_at']))) ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" onsubmit="return confirm('ยืนยันลบความคิดเห็นนี้หรือไม่?');" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete_comment">
                                                <input type="hidden" name="comment_id" value="<?= (int)$comm['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="ลบความคิดเห็น">
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
            </section>

            <!-- ส่วนที่ 2: รายการสั่งซื้อและข้อความจากลูกค้า -->
            <section class="card panel p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h4 mb-1"><i class="bi bi-cart-check-fill text-success me-2"></i>รายการคำสั่งซื้อและข้อความจากลูกค้า</h2>
                        <p class="small text-secondary mb-0">คำขอใหม่จะเป็น “รอติดต่อ” และยังไม่นับเป็นยอดขายจนกว่าจะเปลี่ยนเป็น “ขายสำเร็จ”</p>
                    </div>
                    <a href="manage_services.php" class="btn btn-outline-primary btn-sm">จัดการเว็บไซต์</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>เลขที่</th>
                                <th>เว็บไซต์ที่เลือก</th>
                                <th>ลูกค้า / ข้อมูลติดต่อ</th>
                                <th>ข้อความ/หมายเหตุจากลูกค้า</th>
                                <th>ราคา</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>อัปเดต</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($ordersResult->num_rows): while ($order = $ordersResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($order['service_title']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                    <div class="small text-secondary">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_contact']) ?>
                                        <?= $order['customer_email'] ? ' · <i class="bi bi-envelope me-1"></i>' . htmlspecialchars($order['customer_email']) : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($order['notes']): ?>
                                        <div class="p-2 bg-light rounded small border">
                                            <i class="bi bi-chat-text text-secondary me-1"></i><?= nl2br(htmlspecialchars($order['notes'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">- ไม่มีข้อความ -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap fw-bold">฿<?= number_format((float) $order['price'], 2) ?></td>
                                <td class="text-nowrap small text-secondary"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'secondary' : ($order['status'] === 'pending' ? 'warning' : 'primary')) ?>">
                                        <?= htmlspecialchars($statuses[$order['status']] ?? $order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <select class="form-select form-select-sm status-select" name="status" aria-label="เปลี่ยนสถานะคำสั่งซื้อ #<?= (int) $order['id'] ?>">
                                            <?php foreach ($statuses as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">บันทึก</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="8" class="text-center text-secondary py-5">ยังไม่มีรายการสั่งซื้อ รายการใหม่จะปรากฏที่นี่</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
