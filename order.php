<?php
session_start();
require_once 'admin/db.php';

if (empty($_SESSION['order_csrf_token'])) {
    $_SESSION['order_csrf_token'] = bin2hex(random_bytes(32));
}

$serviceId = filter_var($_GET['service_id'] ?? $_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
$service = null;
$error = '';
$success = isset($_GET['success']);

if ($serviceId) {
    $stmt = $conn->prepare('SELECT id, title, price FROM services WHERE id = ?');
    if (!$stmt) {
        die('Unable to prepare service lookup: ' . $conn->error);
    }
    $stmt->bind_param('i', $serviceId);
    if (!$stmt->execute()) {
        die('Unable to load service: ' . $stmt->error);
    }
    $service = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$service) {
    http_response_code(404);
    $error = 'ไม่พบเว็บไซต์ที่เลือก กรุณากลับไปเลือกบริการอีกครั้ง';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['order_csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง';
    } else {
        $name = trim($_POST['customer_name'] ?? '');
        $contact = trim($_POST['customer_contact'] ?? '');
        $email = trim($_POST['customer_email'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($name === '' || $contact === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $error = 'กรุณากรอกชื่อ ช่องทางติดต่อ และอีเมลให้ถูกต้อง (อีเมลไม่บังคับ)';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO service_orders (service_id, service_title, price, customer_name, customer_contact, customer_email, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                $error = 'เตรียมบันทึกคำสั่งซื้อไม่สำเร็จ: ' . $conn->error;
            } else {
                $price = (float) $service['price'];
                $stmt->bind_param('isdssss', $serviceId, $service['title'], $price, $name, $contact, $email, $notes);
                if ($stmt->execute()) {
                    header('Location: order.php?service_id=' . $serviceId . '&success=1');
                    exit;
                }
                $error = 'บันทึกคำสั่งซื้อไม่สำเร็จ: ' . $stmt->error;
                $stmt->close();
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
    <title>สั่งซื้อเว็บไซต์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #eef2ff, #f8fafc); color: #172033; }
        .order-card { max-width: 680px; border: 0; border-radius: 20px; box-shadow: 0 18px 55px #17203314; }
        .price { color: #4f46e5; font-size: 1.7rem; font-weight: 800; }
    </style>
</head>
<body>
<main class="container py-5">
    <div class="card order-card mx-auto p-3 p-md-4">
        <div class="card-body">
            <a href="<?= $service ? 'detail.php?id=' . (int) $service['id'] : 'index.php' ?>" class="text-decoration-none">← กลับ</a>
            <h1 class="h3 mt-3">ส่งคำขอสั่งซื้อ</h1>
            <?php if ($success): ?>
                <div class="alert alert-success mt-3" role="status">ส่งคำขอเรียบร้อยแล้ว ทีมงานจะติดต่อกลับตามช่องทางที่ให้ไว้</div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger mt-3" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($service): ?>
                <div class="bg-light rounded-3 p-3 my-4">
                    <div class="text-secondary small">เว็บไซต์ที่เลือก</div>
                    <div class="fw-semibold"><?= htmlspecialchars($service['title']) ?></div>
                    <div class="price">฿<?= number_format((float) $service['price'], 2) ?></div>
                    <small class="text-secondary">ส่งคำขอเพื่อให้ทีมงานติดต่อกลับ ยังไม่มีการชำระเงินออนไลน์</small>
                </div>
                <?php if (!$success): ?>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['order_csrf_token']) ?>">
                        <div class="col-12"><label class="form-label" for="customer_name">ชื่อผู้ติดต่อ</label><input class="form-control" id="customer_name" name="customer_name" maxlength="150" required value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="customer_contact">เบอร์โทรหรือช่องทางติดต่อ</label><input class="form-control" id="customer_contact" name="customer_contact" maxlength="100" required value="<?= htmlspecialchars($_POST['customer_contact'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="customer_email">อีเมล (ไม่บังคับ)</label><input class="form-control" type="email" id="customer_email" name="customer_email" maxlength="190" value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label" for="notes">รายละเอียดเพิ่มเติม (ไม่บังคับ)</label><textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea></div>
                        <div class="col-12"><button class="btn btn-primary btn-lg w-100" type="submit">ยืนยันส่งคำขอสั่งซื้อ</button></div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
