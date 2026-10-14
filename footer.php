<?php
// เรียกไฟล์เชื่อมต่อ DB
require_once __DIR__ . '/admin/db.php'; 

// ดึงข้อมูลหัวข้อหลักและลิงก์ทั้งหมดอย่างมีประสิทธิภาพ (2 queries แทนที่จะเป็น N+1)
$sectionsResult = $conn->query("SELECT id, title FROM footer_sections ORDER BY sort_order ASC, id ASC");
$sections = $sectionsResult ? $sectionsResult->fetch_all(MYSQLI_ASSOC) : [];

$linksBySection = [];
if (!empty($sections)) {
    $linksResult = $conn->query("SELECT section_id, title, url FROM footer_links ORDER BY sort_order ASC, id ASC");
    if ($linksResult) {
        while ($link = $linksResult->fetch_assoc()) {
            $linksBySection[(int)$link['section_id']][] = $link;
        }
    }
}
?>

<footer class="subject-page__footer">
  <div class="footer-container">
    
    <!-- คอลัมน์ที่ 1: แบรนด์/โลโก้ -->
    <div class="footer-col footer-brand-col">
      <div class="footer-img-box">
        <img src="/img/ahmetyuksek-mountain-range-9842371_1920.jpg" alt="Logo" class="footer-img">
      </div>
      <p class="footer-title">💚 ITDevTeam Coding</p>
      <p class="footer-desc">สร้างสรรค์การเรียนรู้การเขียนโค้ดและพัฒนาเว็บไซต์ให้สนุกและเข้าใจง่ายเหมือนเล่นเกม</p>
    </div>

    <!-- คอลัมน์ดึงจากฐานข้อมูลแบบ Dynamic -->
    <?php foreach ($sections as $sec): 
      $secId = (int)$sec['id'];
      $secLinks = $linksBySection[$secId] ?? [];
    ?>
      <div class="footer-col">
        <h3 class="footer-heading"><?= htmlspecialchars($sec['title']) ?></h3>
        <ul class="footer-links">
          <?php if (empty($secLinks)): ?>
            <li class="empty-hint" style="color: #64748b; font-size: 13px;">ยังไม่มีลิงก์ในหมวดหมู่นี้</li>
          <?php else: ?>
            <?php foreach ($secLinks as $link): ?>
              <li><a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['title']) ?></a></li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    <?php endforeach; ?>

  </div>

  <div class="footer-bottom">
    <p class="copyright">© <?= date('Y') ?> ITDevTeam Coding. สงวนลิขสิทธิ์ทั้งหมด</p>
  </div>
</footer>