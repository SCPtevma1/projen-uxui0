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
                    $notice = 'อัปเดตสถานะคำสั่งซื้อแล้ว';
                } else {
                    $error = 'อัปเดตสถานะไม่สำเร็จ: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

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
    <title>ภาพรวมระบบ | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel, .stat-card { border: 0; border-radius: 16px; box-shadow: 0 8px 24px #0f172a0d; }
        .stat-card { height: 100%; }
        .stat-icon { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 12px; font-size: 1.25rem; }
        .status-select { min-width: 145px; }
        @media (max-width: 767px) { .sidebar { min-height: auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_footer.php">footer</a>
            <a href="manage_services.php"><i class="bi bi-window-stack me-2"></i> จัดการเว็บไซต์</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>
        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div><h1 class="h3 mb-1">ภาพรวมระบบ</h1><p class="text-secondary mb-0">ข้อมูลล่าสุดจากรายการบริการ ผู้ใช้ และคำสั่งซื้อ</p></div>
                <div>ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong> <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></div>
            </header>

            <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <section class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">คำสั่งซื้อทั้งหมด</div><div class="h3 mt-2 mb-0"><?= number_format((int) $summary['order_count']) ?></div></div><span class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></span></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">รอติดต่อ</div><div class="h3 mt-2 mb-0"><?= number_format((int) $summary['pending_count']) ?></div></div><span class="stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">ขายสำเร็จ</div><div class="h3 mt-2 mb-0"><?= number_format((int) $summary['completed_count']) ?></div></div><span class="stat-icon bg-success-subtle text-success"><i class="bi bi-bag-check"></i></span></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">ยอดขายที่สำเร็จ</div><div class="h3 mt-2 mb-0">฿<?= number_format((float) $summary['completed_revenue'], 2) ?></div><div class="small text-secondary">คำนวณจากสถานะขายสำเร็จ</div></div><span class="stat-icon bg-info-subtle text-info"><i class="bi bi-currency-baht"></i></span></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">เว็บไซต์ที่เปิดขาย</div><div class="h3 mt-2 mb-0"><?= number_format($serviceCount) ?></div></div><span class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-window-stack"></i></span></div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="card stat-card p-3"><div class="d-flex justify-content-between"><div><div class="text-secondary small">ผู้ใช้งาน</div><div class="h3 mt-2 mb-0"><?= number_format($userCount) ?></div></div><span class="stat-icon bg-secondary-subtle text-secondary"><i class="bi bi-people"></i></span></div></div></div>
            </section>

            <section class="card panel p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div><h2 class="h4 mb-1">รายการซื้อขายทั้งหมด</h2><p class="small text-secondary mb-0">คำขอใหม่จะเป็น “รอติดต่อ” และยังไม่นับเป็นยอดขายจนกว่าจะเปลี่ยนเป็น “ขายสำเร็จ”</p></div>
                    <a href="manage_services.php" class="btn btn-outline-primary btn-sm">จัดการเว็บไซต์</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>เลขที่</th><th>เว็บไซต์</th><th>ลูกค้า / ติดต่อ</th><th>ราคา</th><th>วันที่</th><th>สถานะ</th><th>อัปเดต</th></tr></thead>
                        <tbody>
                        <?php if ($ordersResult->num_rows): while ($order = $ordersResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($order['service_title']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?><div class="small text-secondary"><?= htmlspecialchars($order['customer_contact']) ?><?= $order['customer_email'] ? ' · ' . htmlspecialchars($order['customer_email']) : '' ?></div><?php if ($order['notes']): ?><div class="small text-secondary"><?= nl2br(htmlspecialchars($order['notes'])) ?></div><?php endif; ?></td>
                                <td class="text-nowrap">฿<?= number_format((float) $order['price'], 2) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                                <td><span class="badge text-bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'secondary' : ($order['status'] === 'pending' ? 'warning' : 'primary')) ?>"><?= htmlspecialchars($statuses[$order['status']] ?? $order['status']) ?></span></td>
                                <td><form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <select class="form-select form-select-sm status-select" name="status" aria-label="เปลี่ยนสถานะคำสั่งซื้อ #<?= (int) $order['id'] ?>">
                                        <?php foreach ($statuses as $value => $label): ?><option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">บันทึก</button>
                                </form></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center text-secondary py-5">ยังไม่มีรายการสั่งซื้อ รายการใหม่จะปรากฏที่นี่</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
