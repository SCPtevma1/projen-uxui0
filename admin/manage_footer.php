<?php
session_start();
require_once 'db.php'; // เรียกใช้ $conn จาก db.php

// จัดการการส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. เพิ่มหัวข้อหลัก
    if (isset($_POST['action']) && $_POST['action'] === 'add_section') {
        $title = trim($_POST['section_title']);
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO footer_sections (title) VALUES (?)");
            $stmt->bind_param("s", $title);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 2. เพิ่มเมนูย่อย
    if (isset($_POST['action']) && $_POST['action'] === 'add_link') {
        $section_id = intval($_POST['section_id']);
        $link_title = trim($_POST['link_title']);
        $link_url   = trim($_POST['link_url']);
        if ($section_id > 0 && !empty($link_title)) {
            $stmt = $conn->prepare("INSERT INTO footer_links (section_id, title, url) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $section_id, $link_title, $link_url);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 3. ลบหัวข้อหลัก
    if (isset($_POST['action']) && $_POST['action'] === 'delete_section') {
        $sec_id = intval($_POST['sec_id']);
        $stmt = $conn->prepare("DELETE FROM footer_sections WHERE id = ?");
        $stmt->bind_param("i", $sec_id);
        $stmt->execute();
        $stmt->close();
    }

    // 4. ลบเมนูย่อย
    if (isset($_POST['action']) && $_POST['action'] === 'delete_link') {
        $link_id = intval($_POST['link_id']);
        $stmt = $conn->prepare("DELETE FROM footer_links WHERE id = ?");
        $stmt->bind_param("i", $link_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_footer.php");
    exit;
}

// ดึงข้อมูลหัวข้อหลักทั้งหมด
$sections_result = $conn->query("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");
$sections = $sections_result ? $sections_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการ Footer | Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; font-family: 'Prompt', sans-serif; margin: 0; padding: 0; }
    body { background-color: #f4f6f9; color: #333; padding: 30px; }
    .admin-container { max-width: 1100px; margin: 0 auto; }
    .card { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    h2 { margin-bottom: 8px; color: #1e293b; }
    p.sub { color: #64748b; font-size: 14px; margin-bottom: 20px; }
    label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; color: #475569; }
    input[type="text"], select { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
    .btn-primary { background: #58cc02; color: #fff; }
    .btn-danger { background: #ef4444; color: #fff; padding: 4px 10px; font-size: 12px; border-radius: 6px; }
    .footer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 15px; }
    .section-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; background: #fafafa; }
    .section-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px; }
    .link-list { list-style: none; }
    .link-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 14px; }
    .link-url { font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>

  <div class="admin-container">
    <h2>⚙️ จัดการส่วนท้ายเว็บไซต์ (Footer Manager)</h2>
    <p class="sub">เพิ่ม แก้ไข หรือลบหัวข้อหลักและลิงก์เมนูย่อยของ Footer</p>

    <!-- ฟอร์ม 1: เพิ่มหัวข้อหลัก -->
    <div class="card">
      <h3>1. เพิ่มหัวข้อหลัก (Header Column)</h3>
      <form action="" method="POST" style="display: flex; gap: 10px; margin-top: 15px;">
        <input type="hidden" name="action" value="add_section">
        <input type="text" name="section_title" placeholder="เช่น บริการของเรา, วิชาเรียนยอดนิยม" required>
        <button type="submit" class="btn btn-primary">+ เพิ่มหัวข้อ</button>
      </form>
    </div>

    <!-- ฟอร์ม 2: เพิ่มเมนูย่อย -->
    <div class="card">
      <h3>2. เพิ่มเมนูย่อย (Sub-menu Link)</h3>
      <form action="" method="POST" style="margin-top: 15px;">
        <input type="hidden" name="action" value="add_link">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
          <div>
            <label>เลือกหัวข้อหลัก</label>
            <select name="section_id" required>
              <option value="">-- เลือกหัวข้อ --</option>
              <?php foreach ($sections as $sec): ?>
                <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>ชื่อเมนูย่อย</label>
            <input type="text" name="link_title" placeholder="เช่น ระบบเรียนออนไลน์" required>
          </div>
          <div>
            <label>ลิงก์ URL</label>
            <input type="text" name="link_url" placeholder="เช่น /courses หรือ #">
          </div>
          <div>
            <button type="submit" class="btn btn-primary">+ เพิ่มเมนูย่อย</button>
          </div>
        </div>
      </form>
    </div>

    <!-- รายการในระบบ -->
    <div class="card">
      <h3>📋 รายการ Footer ปัจจุบัน</h3>
      <div class="footer-grid">
        <?php foreach ($sections as $sec): ?>
          <div class="section-box">
            <div class="section-header">
              <strong><?= htmlspecialchars($sec['title']) ?></strong>
              <form action="" method="POST" onsubmit="return confirm('ยืนยันลบหัวข้อนี้?');">
                <input type="hidden" name="action" value="delete_section">
                <input type="hidden" name="sec_id" value="<?= $sec['id'] ?>">
                <button type="submit" class="btn btn-danger">ลบ</button>
              </form>
            </div>

            <ul class="link-list">
              <?php
                $stmt = $conn->prepare("SELECT * FROM footer_links WHERE section_id = ? ORDER BY sort_order ASC, id ASC");
                $stmt->bind_param("i", $sec['id']);
                $stmt->execute();
                $links_res = $stmt->get_result();
                if ($links_res->num_rows === 0):
              ?>
                <li style="font-size: 12px; color: #a1a1aa; padding: 5px 0;">ยังไม่มีเมนูย่อย</li>
              <?php else: ?>
                <?php while ($link = $links_res->fetch_assoc()): ?>
                  <li class="link-item">
                    <div>
                      <div><?= htmlspecialchars($link['title']) ?></div>
                      <div class="link-url"><?= htmlspecialchars($link['url']) ?></div>
                    </div>
                    <form action="" method="POST">
                      <input type="hidden" name="action" value="delete_link">
                      <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                      <button type="submit" class="btn btn-danger">✕</button>
                    </form>
                  </li>
                <?php endwhile; ?>
              <?php endif; $stmt->close(); ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</body>
</html>