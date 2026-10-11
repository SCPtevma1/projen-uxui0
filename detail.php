<?php
require_once 'admin/db.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$id = $id ?: 0;
$stmt = $conn->prepare('SELECT * FROM services WHERE id = ?');
if (!$stmt) {
    die('Unable to prepare service lookup: ' . $conn->error);
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    die('Unable to load service: ' . $stmt->error);
}
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    http_response_code(404);
    echo 'ไม่พบข้อมูลเว็บไซต์ที่ต้องการ';
    exit;
}

function renderContentLines(string $content): void
{
    $lines = array_filter(array_map('trim', preg_split('/\R/', $content)));
    if (!$lines) {
        echo '<p class="text-secondary">ยังไม่มีข้อมูลส่วนนี้</p>';
        return;
    }
    echo '<ul class="content-list">';
    foreach ($lines as $line) {
        echo '<li>' . htmlspecialchars($line) . '</li>';
    }
    echo '</ul>';
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($row['title']) ?> | รายละเอียดเว็บไซต์</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f1f5f9; color: #172033; }
        .detail-shell { max-width: 1120px; margin: 48px auto; }
        .detail-panel { border: 0; border-radius: 20px; box-shadow: 0 12px 38px #17203312; }
        .hero-image { width: 100%; height: 380px; object-fit: cover; border-radius: 14px; }
        .detail-title { color: #172033; }
        .content-panel { background: #f8fafc; border-radius: 14px; padding: 22px; height: 100%; }
        .content-list { padding-left: 1.25rem; margin-bottom: 0; }
        .content-list li { padding: 4px 0; color: #475569; }
        .order-box { border-top: 1px solid #e2e8f0; margin-top: 24px; padding-top: 22px; }
        .price { font-size: 1.9rem; font-weight: 800; color: #4f46e5; }
        @media (max-width: 576px) { .detail-shell { margin: 18px auto; } .hero-image { height: 230px; } }
    </style>
</head>
<body>
<main class="container detail-shell">
    <div class="card detail-panel p-3 p-md-4 p-lg-5">
        <a href="index.php" class="text-decoration-none mb-3">← กลับไปหน้ารวมเว็บไซต์</a>
        <img class="hero-image mb-4" src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
        <div class="row g-4">
            <section class="col-lg-8">
                <div class="text-primary fw-semibold mb-2"><?= htmlspecialchars($row['eyebrow'] ?: $row['web_type']) ?></div>
                <h1 class="detail-title h2"><?= htmlspecialchars($row['title']) ?></h1>
                <p class="text-secondary"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                <div class="row g-3 mt-2">
                    <div class="col-md-6"><div class="content-panel"><h2 class="h5">📌 จุดเด่นของงาน</h2><?php renderContentLines((string) $row['highlights']); ?></div></div>
                    <div class="col-md-6"><div class="content-panel"><h2 class="h5">📦 สิ่งที่จะได้รับในไฟล์</h2><?php renderContentLines((string) $row['file_contents']); ?></div></div>
                </div>
            </section>
            <aside class="col-lg-4">
                <div class="content-panel">
                    <h2 class="h5 border-bottom pb-3">ข้อมูลเว็บไซต์</h2>
                    <dl class="mb-0">
                        <dt class="text-secondary small mt-3">ประเภทเว็บไซต์</dt><dd><?= htmlspecialchars($row['web_type']) ?></dd>
                        <dt class="text-secondary small mt-3">รูปแบบ UX/UI</dt><dd><?= htmlspecialchars($row['uxui_type'] ?: '-') ?></dd>
                        <dt class="text-secondary small mt-3">สไตล์การออกแบบ</dt><dd><?= htmlspecialchars($row['uxui_style'] ?: '-') ?></dd>
                        <dt class="text-secondary small mt-3">จำนวนหน้า</dt><dd><?= (int) $row['page_count'] ?> หน้า</dd>
                        <dt class="text-secondary small mt-3">เทคโนโลยี</dt><dd><?= htmlspecialchars($row['tech_tags'] ?: '-') ?></dd>
                    </dl>
                    <div class="order-box">
                        <div class="text-secondary small">ราคาเริ่มต้น</div>
                        <div class="price">฿<?= number_format((float) $row['price'], 2) ?></div>
                        <a href="order.php?service_id=<?= (int) $row['id'] ?>" class="btn btn-primary btn-lg w-100">🛒 สั่งซื้อ / ขอรายละเอียด</a>
                        <a href="<?= htmlspecialchars($row['link_url']) ?>" class="btn btn-outline-secondary w-100 mt-2">ช่องทางติดต่อเพิ่มเติม</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>
</body>
</html>
