<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$settings = app_settings();
$uploadComplete = isset($_GET['uploaded']);
$documentsQuery = $pdo->prepare(
    'SELECT id, original_name, file_type, file_size, page_count, created_at
     FROM print_doc_documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 100'
);
$documentsQuery->execute([$user['id']]);
$documents = $documentsQuery->fetchAll();
$pageTitle = 'สั่งพิมพ์เอกสาร';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-panel">
    <div>
        <p class="eyebrow">พื้นที่ทำงานของคุณ</p>
        <h1>พิมพ์เอกสาร<br><span>ง่ายในไม่กี่ขั้นตอน</span></h1>
        <p class="hero-copy">อัปโหลดไฟล์ เลือกกระดาษและการเข้าเล่ม ระบบคำนวณราคาให้ก่อนส่งคำสั่งซื้อ</p>
    </div>
    <div class="hero-note"><strong>จัดเก็บอย่างเป็นส่วนตัว</strong><span>เอกสารของคุณเข้าถึงได้จากบัญชีนี้เท่านั้น</span></div>
</section>

<section class="content-card" aria-labelledby="upload-title">
    <div class="section-heading">
        <div><p class="eyebrow">ขั้นตอนที่ 1</p><h2 id="upload-title">เพิ่มเอกสาร</h2></div>
        <a class="text-link" href="<?= APP_URL ?>/user/dashboard.php">ดูคลังเอกสารทั้งหมด →</a>
    </div>
    <form id="upload-form" class="upload-zone">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input id="document-files" type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png" required>
        <label for="document-files" class="upload-label"><span class="upload-icon">↑</span><strong>เลือกไฟล์ หรือลากไฟล์มาวาง</strong><span>PDF, Word, Excel, PowerPoint, TXT, JPG และ PNG · ไม่เกิน 15 MB ต่อไฟล์</span></label>
        <button class="button button-secondary" type="submit">อัปโหลดเอกสาร</button>
    </form>
    <p id="upload-message" class="form-message" role="status"></p>
    <?php if ($uploadComplete): ?><p class="success-message" role="status">อัปโหลดเอกสารแล้ว</p><?php endif; ?>
</section>

<section class="content-card" aria-labelledby="order-title">
    <div class="section-heading"><div><p class="eyebrow">ขั้นตอนที่ 2</p><h2 id="order-title">ตั้งค่าการพิมพ์</h2></div></div>
    <div class="order-grid">
        <div>
            <div class="document-list" id="document-list">
                <?php if (!$documents): ?><p class="empty-state">ยังไม่มีเอกสาร เริ่มจากอัปโหลดไฟล์ด้านบน</p><?php endif; ?>
                <?php foreach ($documents as $document): ?>
                    <label class="document-row">
                        <input type="checkbox" class="document-select" value="<?= (int) $document['id'] ?>" data-pages="<?= $document['page_count'] === null ? '' : (int) $document['page_count'] ?>" <?= $document['page_count'] === null ? 'disabled' : '' ?>>
                        <span class="file-icon"><?= e(strtoupper($document['file_type'])) ?></span>
                        <span class="document-meta"><strong><?= e($document['original_name']) ?></strong><small><?= $document['page_count'] === null ? 'ระบุจำนวนหน้าเพื่อใช้คำนวณราคา' : e($document['page_count']) . ' หน้า' ?></small></span>
                        <?php if ($document['page_count'] === null): ?>
                            <span class="manual-pages"><input class="page-input" type="number" min="1" max="10000" value="" placeholder="หน้า" aria-label="จำนวนหน้า <?= e($document['original_name']) ?>" data-document-id="<?= (int) $document['id'] ?>"><button type="button" class="save-pages button button-small" data-document-id="<?= (int) $document['id'] ?>">บันทึก</button></span>
                        <?php else: ?><span class="document-pages"><?= (int) $document['page_count'] ?> หน้า</span><?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="hint">PDF จะนับหน้าอัตโนมัติในเบราว์เซอร์ ส่วนไฟล์ชนิดอื่นกรอกจำนวนหน้าที่ต้องการพิมพ์เอง</p>
        </div>
        <form id="order-form" class="options-panel">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>จำนวนชุด<input type="number" name="copies" min="1" max="100" value="1" required></label>
            <label>งานพิมพ์<select name="color"><option value="bw">ขาวดำ</option><option value="color">สี</option></select></label>
            <label>กระดาษ<select name="paper"><option value="a4_70">A4 · 70 แกรม</option><option value="a3_70">A3 · 70 แกรม</option><option value="b5_70">B5 · 70 แกรม</option><option value="a4_80">A4 · 80 แกรม</option><option value="a4_card">A4 · กระดาษการ์ด</option></select></label>
            <label>การเข้าเล่ม<select name="binding"><option value="none">ไม่เข้าเล่ม</option><option value="staple">เย็บแม็กมุม</option><option value="spiral">สันเกลียว</option><option value="coil">กระดูกงู</option></select></label>
            <label>วิธีรับเอกสาร<select name="delivery"><option value="pickup">รับเอง</option><option value="shipping">ส่งไปรษณีย์/พัสดุ</option></select></label>
            <label id="shipping-address-field" hidden>ที่อยู่จัดส่ง<textarea name="shipping_address" rows="3" maxlength="500" placeholder="ชื่อผู้รับ ที่อยู่ และรหัสไปรษณีย์"></textarea></label>
            <div class="signature-block">
                <div class="signature-heading"><strong>ลายเซ็น (ไม่บังคับ)</strong><button type="button" id="clear-signature" class="text-button">ล้าง</button></div>
                <canvas id="signature-canvas" width="520" height="130" aria-label="ช่องวาดลายเซ็น"></canvas>
                <button type="button" id="save-signature" class="button button-quiet">บันทึกลายเซ็น</button>
                <span id="signature-message" class="hint"></span>
            </div>
            <div id="price-summary" class="price-summary"><span>เลือกเอกสารที่ระบุจำนวนหน้าแล้วเพื่อดูราคา</span></div>
            <button class="button button-primary button-wide" type="submit">ยืนยันคำสั่งพิมพ์</button>
            <p id="order-message" class="form-message" role="status"></p>
        </form>
    </div>
</section>
<script>window.printDocPricing = <?= json_encode($settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script type="module" src="<?= APP_URL ?>/assets/js/app.js"></script>
<script src="<?= APP_URL ?>/assets/js/signature.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
