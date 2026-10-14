<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

$notice = '';
$error = '';

// จัดการการส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มหัวข้อหลัก
    if ($action === 'add_section') {
        $title = trim($_POST['section_title'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        if ($title !== '') {
            $stmt = $conn->prepare("INSERT INTO footer_sections (title, sort_order) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("si", $title, $sortOrder);
                if ($stmt->execute()) {
                    $notice = "เพิ่มหัวข้อ '$title' เรียบร้อยแล้ว";
                } else {
                    $error = "เพิ่มหัวข้อไม่สำเร็จ: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "กรุณากรอกชื่อหัวข้อ";
        }
    }

    // 2. เพิ่มเมนูย่อย
    if ($action === 'add_link') {
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $linkTitle = trim($_POST['link_title'] ?? '');
        $linkUrl   = trim($_POST['link_url'] ?? '#');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($sectionId > 0 && $linkTitle !== '') {
            $stmt = $conn->prepare("INSERT INTO footer_links (section_id, title, url, sort_order) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issi", $sectionId, $linkTitle, $linkUrl, $sortOrder);
                if ($stmt->execute()) {
                    $notice = "เพิ่มเมนูย่อย '$linkTitle' เรียบร้อยแล้ว";
                } else {
                    $error = "เพิ่มเมนูย่อยไม่สำเร็จ: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "กรุณาเลือกหัวข้อหลักและกรอกชื่อเมนูย่อย";
        }
    }

    // 3. ลบหัวข้อหลัก
    if ($action === 'delete_section') {
        $secId = (int)($_POST['sec_id'] ?? 0);
        if ($secId > 0) {
            $stmt = $conn->prepare("DELETE FROM footer_sections WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $secId);
                if ($stmt->execute()) {
                    $notice = "ลบหัวข้อเรียบร้อยแล้ว";
                } else {
                    $error = "ลบหัวข้อไม่สำเร็จ: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    // 4. ลบเมนูย่อย
    if ($action === 'delete_link') {
        $linkId = (int)($_POST['link_id'] ?? 0);
        if ($linkId > 0) {
            $stmt = $conn->prepare("DELETE FROM footer_links WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $linkId);
                if ($stmt->execute()) {
                    $notice = "ลบเมนูย่อยเรียบร้อยแล้ว";
                } else {
                    $error = "ลบเมนูย่อยไม่สำเร็จ: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// ดึงข้อมูลหัวข้อหลักและเมนูย่อยทั้งหมดแบบรวม Query เดียวเพื่อความรวดเร็ว
$sectionsResult = $conn->query("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");
$sections = $sectionsResult ? $sectionsResult->fetch_all(MYSQLI_ASSOC) : [];

$linksBySection = [];
if (!empty($sections)) {
    $linksResult = $conn->query("SELECT * FROM footer_links ORDER BY sort_order ASC, id ASC");
    if ($linksResult) {
        while ($l = $linksResult->fetch_assoc()) {
            $linksBySection[(int)$l['section_id']][] = $l;
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการ Footer | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; font-family: 'Prompt', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; margin-bottom: 4px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .section-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .section-card-header { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: #fafafa; border-radius: 12px 12px 0 0; }
        .link-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px dashed #e2e8f0; font-size: 0.9rem; }
        .link-item:last-child { border-bottom: none; }
        .link-url { font-size: 0.78rem; color: #64748b; font-family: monospace; }
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
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php"><i class="bi bi-window-stack me-2"></i> จัดการเว็บไซต์</a>
            <a href="manage_comments.php"><i class="bi bi-chat-left-text me-2"></i> จัดการความคิดเห็น</a>
            <a href="manage_footer.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> จัดการ Footer</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">
                <div>
                    <h1 class="h3 mb-1">⚙️ จัดการส่วนท้ายเว็บไซต์ (Footer Manager)</h1>
                    <p class="text-secondary mb-0">เพิ่ม แก้ไข หรือลบหมวดหมู่และลิงก์ของ Footer ที่แสดงผลหน้าเว็บ</p>
                </div>
                <div>
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
                    <a class="btn btn-outline-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                </div>
            </header>

            <?php if ($notice): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($notice) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- ฟอร์ม 1: เพิ่มหัวข้อหลัก -->
                <div class="col-lg-5">
                    <div class="card panel p-4 h-100">
                        <h5 class="card-title mb-3"><i class="bi bi-folder-plus text-primary me-2"></i>1. เพิ่มหัวข้อหลัก (Column Section)</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_section">
                            <div class="mb-3">
                                <label class="form-label">ชื่อหัวข้อหลัก</label>
                                <input type="text" name="section_title" class="form-control" placeholder="เช่น บริการของเรา, วิชาเรียนยอดนิยม" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ลำดับการแสดงผล (Sort Order)</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= count($sections) + 1 ?>" min="0">
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> เพิ่มหัวข้อใหม่</button>
                        </form>
                    </div>
                </div>

                <!-- ฟอร์ม 2: เพิ่มเมนูย่อย -->
                <div class="col-lg-7">
                    <div class="card panel p-4 h-100">
                        <h5 class="card-title mb-3"><i class="bi bi-link-45deg text-success me-2"></i>2. เพิ่มลิงก์เมนูย่อย (Footer Link)</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_link">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">เลือกหัวข้อหลัก</label>
                                    <select name="section_id" class="form-select" required>
                                        <option value="">-- เลือกหัวข้อ --</option>
                                        <?php foreach ($sections as $sec): ?>
                                            <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ชื่อเมนูย่อย</label>
                                    <input type="text" name="link_title" class="form-control" placeholder="เช่น คอร์สเขียนโค้ด" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">ลิงก์ URL</label>
                                    <input type="text" name="link_url" class="form-control" placeholder="เช่น /index.php หรือ #">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ลำดับ</label>
                                    <input type="number" name="sort_order" class="form-control" value="1" min="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success mt-3"><i class="bi bi-plus-lg me-1"></i> เพิ่มเมนูย่อย</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ส่วนแสดงผลรายการ Footer ทั้งหมด -->
            <div class="card panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>โครงสร้าง Footer ในระบบปัจจุบัน</h5>
                    <span class="badge bg-secondary"><?= count($sections) ?> หมวดหมู่</span>
                </div>

                <?php if (empty($sections)): ?>
                    <div class="alert alert-info mb-0">ยังไม่มีข้อมูลหัวข้อ Footer ในระบบ</div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($sections as $sec): 
                            $secId = (int)$sec['id'];
                            $links = $linksBySection[$secId] ?? [];
                        ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="section-card h-100">
                                    <div class="section-card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary me-1">ลำดับ <?= (int)$sec['sort_order'] ?></span>
                                            <strong><?= htmlspecialchars($sec['title']) ?></strong>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('ยืนยันลบหัวข้อ <?= htmlspecialchars($sec['title']) ?> และลิงก์ย่อยทั้งหมดหรือไม่?');" class="m-0">
                                            <input type="hidden" name="action" value="delete_section">
                                            <input type="hidden" name="sec_id" value="<?= $sec['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="ลบหัวข้อ"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="p-2">
                                        <?php if (empty($links)): ?>
                                            <div class="text-secondary small text-center py-3">ยังไม่มีเมนูย่อยในหัวข้อนี้</div>
                                        <?php else: ?>
                                            <?php foreach ($links as $link): ?>
                                                <div class="link-item">
                                                    <div>
                                                        <div class="fw-medium"><?= htmlspecialchars($link['title']) ?></div>
                                                        <div class="link-url"><?= htmlspecialchars($link['url']) ?></div>
                                                    </div>
                                                    <form method="POST" onsubmit="return confirm('ยืนยันลบลิงก์นี้?');" class="m-0">
                                                        <input type="hidden" name="action" value="delete_link">
                                                        <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-1 text-danger border-0" title="ลบลิงก์"><i class="bi bi-x-circle"></i></button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>