<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_login();
if ($admin['role'] !== 'admin') {
    http_response_code(403);
    exit('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$labels = [
    'bw_rate' => ['ราคาขาวดำต่อหน้า', 'บาท/หน้า'],
    'color_rate' => ['ราคาสีต่อหน้า', 'บาท/หน้า'],
    'shipping_fee' => ['ค่าจัดส่ง', 'บาท/คำสั่งซื้อ'],
    'paper_a3' => ['กระดาษ A3 เพิ่ม', 'บาท/หน้า'],
    'paper_b5' => ['กระดาษ B5 เพิ่ม', 'บาท/หน้า'],
    'paper_80gsm' => ['กระดาษ A4 80 แกรมเพิ่ม', 'บาท/หน้า'],
    'paper_card' => ['กระดาษการ์ดเพิ่ม', 'บาท/หน้า'],
    'binding_staple' => ['เย็บแม็กมุม', 'บาท/ชุด'],
    'binding_spiral' => ['สันเกลียว', 'บาท/ชุด'],
    'binding_coil' => ['กระดูกงู', 'บาท/ชุด'],
    'bulk_threshold' => ['จำนวนหน้าส่วนลดขั้นต่ำ', 'หน้า'],
    'bulk_bw_rate' => ['ราคาขาวดำเมื่อถึงจำนวนส่วนลด', 'บาท/หน้า'],
    'minimum_order' => ['ยอดสั่งซื้อขั้นต่ำ', 'บาท/คำสั่งซื้อ'],
];
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่';
    } else {
        $updates = [];
        foreach ($labels as $key => $_label) {
            $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_FLOAT);
            if ($value === false || $value < 0 || $value > 100000) {
                $error = 'กรุณากรอกราคาเป็นตัวเลขตั้งแต่ 0 ถึง 100,000';
                break;
            }
            if ($key === 'bulk_threshold' && (!is_numeric($_POST[$key]) || (float) $_POST[$key] < 1 || floor((float) $_POST[$key]) !== (float) $_POST[$key])) {
                $error = 'จำนวนหน้าส่วนลดต้องเป็นจำนวนเต็มอย่างน้อย 1';
                break;
            }
            $updates[$key] = (float) $value;
        }
        if ($error === '') {
            $statement = $pdo->prepare(
                'INSERT INTO print_doc_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            foreach ($updates as $key => $value) {
                $statement->execute([$key, $value]);
            }
            $success = 'บันทึกอัตราค่าบริการแล้ว';
        }
    }
}
$settings = app_settings();
$pageTitle = 'จัดการอัตราค่าบริการ';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <p class="eyebrow">ผู้ดูแลระบบ</p><h1>อัตราค่าบริการ</h1>
    <p class="muted">การเปลี่ยนแปลงมีผลกับการคำนวณคำสั่งพิมพ์ใหม่</p>
</section>
<section class="content-card admin-card">
    <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="success-message" role="status"><?= e($success) ?></div><?php endif; ?>
    <form method="post" class="pricing-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($labels as $key => [$label, $unit]): ?>
            <label><?= e($label) ?><span class="input-with-unit"><input name="<?= e($key) ?>" type="number" min="0" max="100000" step="<?= $key === 'bulk_threshold' ? '1' : '0.01' ?>" value="<?= e($settings[$key] ?? 0) ?>" required><small><?= e($unit) ?></small></span></label>
        <?php endforeach; ?>
        <button class="button button-primary" type="submit">บันทึกอัตราค่าบริการ</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
