<?php
session_start();
require_once 'auth_check.php';
require_once 'db.php';

$notice = '';
$error  = '';

$statuses = [
    'pending'   => 'รอติดต่อ',
    'contacted' => 'ติดต่อแล้ว',
    'confirmed' => 'ยืนยันสั่งซื้อ',
    'completed' => 'ขายสำเร็จ',
    'cancelled' => 'ยกเลิก',
];

$statusColors = [
    'pending'   => 'warning',
    'contacted' => 'primary',
    'confirmed' => 'info',
    'completed' => 'success',
    'cancelled' => 'secondary',
];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง';
    } else {
        $action = $_POST['action'] ?? '';

        // อัปเดตสถานะ
        if ($action === 'update_status') {
            $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $status  = $_POST['status'] ?? '';
            if (!$orderId || !isset($statuses[$status])) {
                $error = 'ข้อมูลไม่ถูกต้อง';
            } else {
                $stmt = $conn->prepare('UPDATE service_orders SET status = ? WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('si', $status, $orderId);
                    $stmt->execute() ? $notice = "อัปเดตสถานะ #$orderId เรียบร้อย" : $error = $stmt->error;
                    $stmt->close();
                }
            }
        }

        // ลบคำสั่งซื้อ
        if ($action === 'delete_order') {
            $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            if ($orderId) {
                $stmt = $conn->prepare('DELETE FROM service_orders WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $orderId);
                    $stmt->execute() ? $notice = "ลบคำสั่งซื้อ #$orderId เรียบร้อย" : $error = $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

// Pagination
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$filterStatus = $_GET['status'] ?? '';

// Filter
$where = '';
$params = [];
$types  = '';
if ($filterStatus && isset($statuses[$filterStatus])) {
    $where  = 'WHERE status = ?';
    $params = [$filterStatus];
    $types  = 's';
}

$totalResult = $conn->query("SELECT COUNT(*) AS c FROM service_orders $where" . ($params ? '' : ''));
if ($params) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM service_orders $where");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $stmt->close();
}
$totalOrders = $totalResult ? (int)$totalResult->fetch_assoc()['c'] : 0;
$totalPages  = (int)ceil($totalOrders / $perPage);

$sql = "SELECT * FROM service_orders $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
if ($params) {
    $stmt = $conn->prepare($sql);
    $allParams = array_merge($params, [$perPage, $offset]);
    $allTypes  = $types . 'ii';
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $ordersResult = $stmt->get_result();
    $stmt->close();
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $ordersResult = $stmt->get_result();
    $stmt->close();
}
$orders = $ordersResult ? $ordersResult->fetch_all(MYSQLI_ASSOC) : [];

// สถิติแต่ละสถานะ
$statsResult = $conn->query("SELECT status, COUNT(*) AS cnt, SUM(price) AS total FROM service_orders GROUP BY status");
$statsSummary = [];
if ($statsResult) {
    while ($r = $statsResult->fetch_assoc()) {
        $statsSummary[$r['status']] = $r;
    }
}

$pageTitle  = 'จัดการคำสั่งซื้อ';
$activePage = 'manage_orders';
$extraCss   = '
    .order-row:hover { background: #f8fafc; }
    .status-select { min-width: 140px; }
    .filter-btn.active { background: #334155; color: #fff; }
    .stat-mini { border-radius: 10px; padding: 12px 16px; }
';
include 'includes/head.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-cart-check-fill text-success me-2"></i>จัดการคำสั่งซื้อ</h1>
                    <p class="text-secondary mb-0">รายการสั่งซื้อทั้งหมด — คลิกเปลี่ยนสถานะได้ทันที</p>
                </div>
                <div>
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
                    <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                </div>
            </header>

            <?php if ($notice): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($notice) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <!-- สถิติสถานะ -->
            <section class="row g-3 mb-4">
                <?php
                $allStatusColors = ['pending'=>'warning','contacted'=>'primary','confirmed'=>'info','completed'=>'success','cancelled'=>'secondary'];
                foreach ($statuses as $sKey => $sLabel):
                    $cnt = (int)($statsSummary[$sKey]['cnt'] ?? 0);
                    $col = $allStatusColors[$sKey] ?? 'secondary';
                ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="?status=<?= $sKey ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm stat-mini bg-<?= $col ?>-subtle text-<?= $col ?>-emphasis h-100">
                            <div class="fw-bold fs-4"><?= number_format($cnt) ?></div>
                            <div class="small"><?= $sLabel ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="manage_orders.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm stat-mini bg-dark-subtle text-dark-emphasis h-100">
                            <div class="fw-bold fs-4"><?= number_format($totalOrders) ?></div>
                            <div class="small">ทั้งหมด</div>
                        </div>
                    </a>
                </div>
            </section>

            <!-- ฟิลเตอร์สถานะ -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="manage_orders.php" class="btn btn-sm btn-outline-secondary <?= !$filterStatus ? 'active' : '' ?>">ทั้งหมด (<?= $totalOrders ?>)</a>
                <?php foreach ($statuses as $sKey => $sLabel): 
                    $cnt = (int)($statsSummary[$sKey]['cnt'] ?? 0);
                ?>
                    <a href="?status=<?= $sKey ?>" class="btn btn-sm btn-outline-<?= $allStatusColors[$sKey] ?? 'secondary' ?> <?= $filterStatus === $sKey ? 'active' : '' ?>">
                        <?= $sLabel ?> (<?= $cnt ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- ตารางคำสั่งซื้อ -->
            <div class="card panel p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>เว็บไซต์ที่สั่ง</th>
                                <th>ลูกค้า / ติดต่อ</th>
                                <th>หมายเหตุ</th>
                                <th class="text-end">ราคา</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="8" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                ยังไม่มีรายการคำสั่งซื้อ<?= $filterStatus ? " ที่มีสถานะ '{$statuses[$filterStatus]}'" : '' ?>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr class="order-row">
                                <td class="ps-3 fw-bold text-secondary">#<?= (int)$order['id'] ?></td>
                                <td class="fw-semibold text-primary" style="max-width:160px;">
                                    <?= htmlspecialchars($order['service_title']) ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_contact']) ?>
                                    </div>
                                    <?php if ($order['customer_email']): ?>
                                    <div class="small text-secondary">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['customer_email']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:240px;">
                                    <?php if ($order['notes']): ?>
                                        <div class="p-2 bg-light rounded small border">
                                            <i class="bi bi-chat-text text-secondary me-1"></i><?= nl2br(htmlspecialchars($order['notes'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">— ไม่มีข้อความ —</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap fw-bold">฿<?= number_format((float)$order['price'], 2) ?></td>
                                <td class="text-nowrap small text-secondary">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($order['created_at']))) ?>
                                    <br><span class="text-muted"><?= htmlspecialchars(date('H:i', strtotime($order['created_at']))) ?></span>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>">
                                        <?= htmlspecialchars($statuses[$order['status']] ?? $order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <!-- เปลี่ยนสถานะ -->
                                        <form method="POST" class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                            <select class="form-select form-select-sm status-select" name="status">
                                                <?php foreach ($statuses as $sVal => $sLbl): ?>
                                                    <option value="<?= $sVal ?>" <?= $order['status'] === $sVal ? 'selected' : '' ?>><?= $sLbl ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        <!-- ลบ -->
                                        <form method="POST" onsubmit="return confirm('ยืนยันลบคำสั่งซื้อ #<?= (int)$order['id'] ?> หรือไม่?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="ลบ"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                    <small class="text-secondary">แสดง <?= count($orders) ?> จาก <?= $totalOrders ?> รายการ</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?><?= $filterStatus ? "&status=$filterStatus" : '' ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
