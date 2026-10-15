<?php
session_start();
require_once 'auth_check.php';
require_once 'db.php';

$notice = '';
$error  = '';

// Helper: ดึงค่าทั้งหมดจากตาราง site_settings
function getSettings(mysqli $conn): array
{
    $result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

// Helper: บันทึกค่า setting
function saveSetting(mysqli $conn, string $key, ?string $value): bool
{
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง';
    } else {
        $fields = [
            'site_title', 'site_tagline', 'site_description', 'site_keywords',
            'contact_email', 'contact_phone', 'facebook_url', 'line_id'
        ];

        $allOk = true;
        foreach ($fields as $field) {
            $val = trim($_POST[$field] ?? '');
            if (!saveSetting($conn, $field, $val)) {
                $allOk = false;
                $error = "บันทึก '$field' ไม่สำเร็จ: " . $conn->error;
                break;
            }
        }

        // Maintenance Mode (checkbox)
        $maintenanceVal = isset($_POST['maintenance_mode']) ? '1' : '0';
        if ($allOk) {
            saveSetting($conn, 'maintenance_mode', $maintenanceVal);
        }

        // จัดการ Logo Upload
        if ($allOk && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['logo']['tmp_name']);
            if (!in_array($mimeType, $allowedTypes)) {
                $error = 'ไฟล์โลโก้ต้องเป็น JPG, PNG, GIF, WebP หรือ SVG เท่านั้น';
            } else {
                $uploadDir = __DIR__ . '/../uploads/settings/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext      = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $dest     = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    saveSetting($conn, 'logo_path', '/uploads/settings/' . $filename);
                } else {
                    $error = 'อัปโหลดโลโก้ไม่สำเร็จ';
                    $allOk = false;
                }
            }
        }

        if ($allOk && !$error) {
            $notice = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';
        }
    }
}

$settings = getSettings($conn);
$s = fn(string $key) => htmlspecialchars($settings[$key] ?? '');

$pageTitle  = 'ตั้งค่าระบบ';
$activePage = 'settings';
$extraCss   = '
    .settings-nav .nav-link { color: #475569; border-radius: 8px; padding: 10px 16px; }
    .settings-nav .nav-link.active { background: #1e293b; color: #fff; }
    .settings-nav .nav-link:hover:not(.active) { background: #f1f5f9; }
    .section-title { font-size: 1rem; font-weight: 600; color: #1e293b; border-left: 4px solid #3b82f6; padding-left: 12px; margin-bottom: 1.25rem; }
    .logo-preview { max-height: 80px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 4px; }
    .form-label { font-weight: 500; font-size: 0.875rem; color: #374151; }
    .input-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
    .tab-pane { animation: fadeIn .2s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
';
include 'includes/head.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-gear-fill text-primary me-2"></i>ตั้งค่าระบบ</h1>
                    <p class="text-secondary mb-0">จัดการข้อมูลทั่วไป โลโก้ ช่องทางติดต่อ และ SEO ของเว็บไซต์</p>
                </div>
                <div>
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
                    <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                </div>
            </header>

            <?php if ($notice): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($notice) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="row g-4">
                    <!-- แถบ Tab ซ้าย -->
                    <div class="col-lg-3">
                        <div class="card panel p-3">
                            <nav class="nav flex-column settings-nav gap-1">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-general">
                                    <i class="bi bi-globe me-2"></i>ข้อมูลทั่วไป
                                </a>
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-contact">
                                    <i class="bi bi-telephone me-2"></i>ข้อมูลติดต่อ
                                </a>
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-social">
                                    <i class="bi bi-share me-2"></i>Social Media
                                </a>
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-seo">
                                    <i class="bi bi-search me-2"></i>SEO
                                </a>
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-logo">
                                    <i class="bi bi-image me-2"></i>โลโก้
                                </a>
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-system">
                                    <i class="bi bi-sliders me-2"></i>ระบบ
                                </a>
                            </nav>
                        </div>
                    </div>

                    <!-- เนื้อหา Tab ขวา -->
                    <div class="col-lg-9">
                        <div class="tab-content">

                            <!-- Tab: ข้อมูลทั่วไป -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <div class="card panel p-4">
                                    <div class="section-title">ข้อมูลทั่วไปของเว็บไซต์</div>
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อเว็บไซต์ (Site Title)</label>
                                        <input type="text" name="site_title" class="form-control" value="<?= $s('site_title') ?>" required>
                                        <div class="input-hint">ชื่อที่แสดงบน Tab เบราว์เซอร์และส่วนหัว</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">คำโปรย (Tagline)</label>
                                        <input type="text" name="site_tagline" class="form-control" value="<?= $s('site_tagline') ?>">
                                        <div class="input-hint">คำอธิบายสั้นๆ ที่แสดงใต้ชื่อเว็บ</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">คำอธิบายเว็บไซต์</label>
                                        <textarea name="site_description" class="form-control" rows="3"><?= $s('site_description') ?></textarea>
                                        <div class="input-hint">ใช้ใน Meta Description และส่วน About</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: ข้อมูลติดต่อ -->
                            <div class="tab-pane fade" id="tab-contact">
                                <div class="card panel p-4">
                                    <div class="section-title">ข้อมูลช่องทางติดต่อ</div>
                                    <div class="mb-3">
                                        <label class="form-label">อีเมลรับแจ้งเตือน</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="contact_email" class="form-control" value="<?= $s('contact_email') ?>">
                                        </div>
                                        <div class="input-hint">อีเมลที่ระบบจะส่งการแจ้งเตือนคำสั่งซื้อใหม่</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">เบอร์โทรศัพท์</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="tel" name="contact_phone" class="form-control" value="<?= $s('contact_phone') ?>" placeholder="เช่น 085-xxx-xxxx">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Social Media -->
                            <div class="tab-pane fade" id="tab-social">
                                <div class="card panel p-4">
                                    <div class="section-title">ลิงก์ Social Media</div>
                                    <div class="mb-3">
                                        <label class="form-label">Facebook URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text text-primary"><i class="bi bi-facebook"></i></span>
                                            <input type="url" name="facebook_url" class="form-control" value="<?= $s('facebook_url') ?>" placeholder="https://facebook.com/...">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Line ID / Line URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text text-success"><i class="bi bi-chat-dots"></i></span>
                                            <input type="text" name="line_id" class="form-control" value="<?= $s('line_id') ?>" placeholder="@yourline หรือ https://lin.ee/...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: SEO -->
                            <div class="tab-pane fade" id="tab-seo">
                                <div class="card panel p-4">
                                    <div class="section-title">การตั้งค่า SEO</div>
                                    <div class="mb-3">
                                        <label class="form-label">Meta Description</label>
                                        <textarea name="site_description" class="form-control" rows="4"><?= $s('site_description') ?></textarea>
                                        <div class="input-hint">แนะนำ 120–160 ตัวอักษร เพื่อ SEO ที่ดีที่สุด</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Keywords (คีย์เวิร์ด)</label>
                                        <input type="text" name="site_keywords" class="form-control" value="<?= $s('site_keywords') ?>" placeholder="คำหลัก1, คำหลัก2, คำหลัก3">
                                        <div class="input-hint">คั่นด้วยคอมม่า (,) — ใช้ใน Meta Keywords</div>
                                    </div>
                                    <div class="alert alert-info py-2 small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Meta Description ยังสำคัญสำหรับ SEO — แนะนำให้เขียนให้ดึงดูดและครบถ้วน
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: โลโก้ -->
                            <div class="tab-pane fade" id="tab-logo">
                                <div class="card panel p-4">
                                    <div class="section-title">อัปโหลดโลโก้</div>
                                    <?php if (!empty($settings['logo_path'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">โลโก้ปัจจุบัน</label><br>
                                            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" class="logo-preview">
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">อัปโหลดโลโก้ใหม่</label>
                                        <input type="file" name="logo" class="form-control" accept="image/*" id="logoInput">
                                        <div class="input-hint">รองรับ JPG, PNG, WebP, SVG — ขนาดแนะนำ 300×100px</div>
                                        <div class="mt-2" id="logoPreviewContainer" style="display:none;">
                                            <img id="logoPreviewImg" src="" alt="Preview" class="logo-preview">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: ระบบ -->
                            <div class="tab-pane fade" id="tab-system">
                                <div class="card panel p-4">
                                    <div class="section-title">การตั้งค่าระบบ</div>
                                    <div class="mb-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   name="maintenance_mode" id="maintenance_mode"
                                                   value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="maintenance_mode">
                                                เปิดโหมดปิดปรับปรุงระบบ (Maintenance Mode)
                                            </label>
                                        </div>
                                        <div class="input-hint mt-1">
                                            <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                                            เมื่อเปิด ผู้เยี่ยมชมหน้าบ้านจะเห็นหน้าปิดปรับปรุงแทน
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="section-title mt-3">ข้อมูลระบบ</div>
                                    <table class="table table-sm table-borderless">
                                        <tr><td class="text-secondary" style="width:160px;">PHP Version</td><td><?= phpversion() ?></td></tr>
                                        <tr><td class="text-secondary">Database</td><td><?= htmlspecialchars($conn->server_info) ?></td></tr>
                                        <tr><td class="text-secondary">Server</td><td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td></tr>
                                    </table>
                                </div>
                            </div>

                        </div><!-- /tab-content -->

                        <!-- ปุ่ม Save -->
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> กลับ Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-floppy me-1"></i> บันทึกการตั้งค่า
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preview Logo ก่อน Upload
document.getElementById('logoInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logoPreviewImg').src = e.target.result;
            document.getElementById('logoPreviewContainer').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// จำ Tab ที่เปิดอยู่ใน localStorage
const tabLinks = document.querySelectorAll('.settings-nav .nav-link');
const savedTab = localStorage.getItem('settings_active_tab');
if (savedTab) {
    const target = document.querySelector(`.settings-nav .nav-link[href="${savedTab}"]`);
    if (target) target.click();
}
tabLinks.forEach(link => {
    link.addEventListener('shown.bs.tab', () => {
        localStorage.setItem('settings_active_tab', link.getAttribute('href'));
    });
});
</script>
</body>
</html>