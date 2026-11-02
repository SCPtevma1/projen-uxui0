<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$documentsQuery = $pdo->prepare(
    'SELECT id, original_name, file_type, file_size, page_count, created_at
     FROM print_doc_documents WHERE user_id = ? ORDER BY created_at DESC'
);
$documentsQuery->execute([$user['id']]);
$documents = $documentsQuery->fetchAll();
$ordersQuery = $pdo->prepare(
    'SELECT id, total_pages, total, status, created_at FROM print_doc_orders
     WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
);
$ordersQuery->execute([$user['id']]);
$orders = $ordersQuery->fetchAll();
$pageTitle = 'คลังเอกสารของฉัน';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">พื้นที่ส่วนตัว</p><h1>คลังเอกสารของฉัน</h1>
    <p class="muted">ไฟล์ในคลังนี้มองเห็นได้เฉพาะบัญชีของคุณ</p>
    <a class="button button-primary" href="<?= APP_URL ?>/index.php">+ สั่งพิมพ์เอกสาร</a>
    <a class="button button-secondary" href="<?= APP_URL ?>/user/account.php">จัดการบัญชี</a>
</section>
<section class="content-card">
    <div class="section-heading"><h2>เอกสารที่อัปโหลด</h2><span class="count-pill"><?= count($documents) ?> ไฟล์</span></div>
    <?php if (!$documents): ?><p class="empty-state">ยังไม่มีเอกสารในคลัง</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>ชื่อไฟล์</th><th>ประเภท</th><th>ขนาด</th><th>จำนวนหน้า</th><th>เพิ่มเมื่อ</th><th></th></tr></thead><tbody>
        <?php foreach ($documents as $document): ?>
            <tr><td><?= e($document['original_name']) ?></td><td><?= e(strtoupper($document['file_type'])) ?></td><td><?= number_format((int) $document['file_size'] / 1024, 1) ?> KB</td><td><?= $document['page_count'] === null ? 'ยังไม่ระบุ' : (int) $document['page_count'] ?></td><td><?= e(date('d/m/Y H:i', strtotime($document['created_at']))) ?></td><td><a class="text-link" href="<?= APP_URL ?>/download.php?id=<?= (int) $document['id'] ?>">ดาวน์โหลด</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<section class="content-card">
    <div class="section-heading"><h2>คำสั่งพิมพ์ล่าสุด</h2></div>
    <?php if (!$orders): ?><p class="empty-state">ยังไม่มีคำสั่งพิมพ์</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>หมายเลข</th><th>จำนวนหน้ารวม</th><th>ยอดรวม</th><th>สถานะ</th><th>วันที่</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?>
            <tr><td>#<?= (int) $order['id'] ?></td><td><?= (int) $order['total_pages'] ?></td><td>฿<?= number_format((float) $order['total'], 2) ?></td><td><span class="status-pill"><?= e($order['status']) ?></span></td><td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
