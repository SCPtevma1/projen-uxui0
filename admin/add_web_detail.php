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

$serviceId = filter_var($_GET['service_id'] ?? $_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
if (!$serviceId) {
    header('Location: manage_services.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, title, uxui_type, uxui_style, page_count, highlights, file_contents FROM services WHERE id = ?');
if (!$stmt) {
    die('Unable to prepare website lookup: ' . $conn->error);
}
$stmt->bind_param('i', $serviceId);
if (!$stmt->execute()) {
    die('Unable to load website details: ' . $stmt->error);
}
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$service) {
    header('Location: manage_services.php?missing=1');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง';
    } else {
        $uxuiType = trim($_POST['uxui_type'] ?? '');
        $uxuiStyle = trim($_POST['uxui_style'] ?? '');
        $pageCount = max(1, (int) ($_POST['page_count'] ?? 1));
        $highlights = trim($_POST['highlights'] ?? '');
        $fileContents = trim($_POST['file_contents'] ?? '');
        $stmt = $conn->prepare('UPDATE services SET uxui_type=?, uxui_style=?, page_count=?, highlights=?, file_contents=? WHERE id=?');
        if (!$stmt) {
            $error = 'เตรียมอัปเดตรายละเอียดไม่สำเร็จ: ' . $conn->error;
        } else {
            $stmt->bind_param('ssissi', $uxuiType, $uxuiStyle, $pageCount, $highlights, $fileContents, $serviceId);
            if ($stmt->execute()) {
                header('Location: manage_services.php?saved=1');
                exit;
            }
            $error = 'บันทึกรายละเอียดไม่สำเร็จ: ' . $stmt->error;
            $stmt->close();
        }
        $service['uxui_type'] = $uxuiType;
        $service['uxui_style'] = $uxuiStyle;
        $service['page_count'] = $pageCount;
        $service['highlights'] = $highlights;
        $service['file_contents'] = $fileContents;
    }
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายละเอียดเว็บไซต์ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background: #f1f5f9; }.form-box { background: #fff; border-radius: 16px; box-shadow: 0 8px 24px #0f172a0d; padding: 32px; }</style>
</head>
<body>
<main class="container py-5">
    <div class="form-box mx-auto" style="max-width: 850px">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-3 mb-4">
            <div><h1 class="h3 mb-1">แก้ไขรายละเอียดเว็บไซต์</h1><p class="text-secondary mb-0"><?= htmlspecialchars($service['title']) ?></p></div>
            <a href="manage_services.php" class="btn btn-outline-secondary">กลับหน้าจัดการ</a>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" class="row g-3">
            <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="col-md-6"><label class="form-label">ประเภท UX/UI</label><input name="uxui_type" class="form-control" value="<?= htmlspecialchars($service['uxui_type']) ?>"></div>
            <div class="col-md-6"><label class="form-label">สไตล์ UX/UI</label><input name="uxui_style" class="form-control" value="<?= htmlspecialchars($service['uxui_style']) ?>"></div>
            <div class="col-md-4"><label class="form-label">จำนวนหน้าทั้งหมด</label><input type="number" min="1" name="page_count" class="form-control" value="<?= (int) $service['page_count'] ?>"></div>
            <div class="col-12"><label class="form-label">📌 จุดเด่นของงาน</label><textarea name="highlights" class="form-control" rows="5" placeholder="เพิ่มหนึ่งหัวข้อต่อหนึ่งบรรทัด"><?= htmlspecialchars($service['highlights']) ?></textarea></div>
            <div class="col-12"><label class="form-label">📦 สิ่งที่จะได้รับในไฟล์</label><textarea name="file_contents" class="form-control" rows="5" placeholder="เพิ่มหนึ่งรายการต่อหนึ่งบรรทัด"><?= htmlspecialchars($service['file_contents']) ?></textarea></div>
            <div class="col-12 text-end"><button type="submit" class="btn btn-success px-4">บันทึกและอัปเดตหน้าเว็บไซต์</button></div>
        </form>
    </div>
</main>
</body>
</html>
