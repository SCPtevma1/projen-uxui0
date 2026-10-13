<?php
// เรียกไฟล์เชื่อมต่อ DB
require_once __DIR__ . '/admin/db.php'; 

// ดึงข้อมูลหัวข้อหลัก
$sections_result = $conn->query("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");
$sections = $sections_result ? $sections_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<footer class="subject-page__footer">
  <div class="footer-container">
    
    <!-- คอลัมน์ที่ 1: แบรนด์/โลโก้ -->
    <div class="footer-col footer-brand-col">
      <div class="footer-img-box">
        <img src="/img/ahmetyuksek-mountain-range-9842371_1920.jpg" alt="Logo" class="footer-img">
      </div>
      <p class="footer-title">💚 ITDevTeam Coding</p>
      <p class="footer-desc">สร้างสรรค์การเรียนรู้การเขียนโค้ดให้สนุกและง่ายเหมือนเล่นเกม</p>
    </div>

    <!-- คอลัมน์ดึงจากฐานข้อมูลแบบ Dynamic -->
    <?php foreach ($sections as $sec): ?>
      <div class="footer-col">
        <h3 class="footer-heading"><?= htmlspecialchars($sec['title']) ?></h3>
        <ul class="footer-links">
          <?php
            $stmt = $conn->prepare("SELECT * FROM footer_links WHERE section_id = ? ORDER BY sort_order ASC, id ASC");
            $stmt->bind_param("i", $sec['id']);
            $stmt->execute();
            $links_res = $stmt->get_result();
            while ($link = $links_res->fetch_assoc()):
          ?>
            <li><a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['title']) ?></a></li>
          <?php endwhile; $stmt->close(); ?>
        </ul>
      </div>
    <?php endforeach; ?>

  </div>

  <div class="footer-bottom">
    <p class="copyright">© 2024 ITDevTeam. สงวนลิขสิทธิ์ทั้งหมด</p>
  </div>
</footer>