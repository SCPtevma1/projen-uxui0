<?php
require_once __DIR__ . '/admin/db.php';
$sections_query = $pdo->query("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");
$sections = $sections_query->fetchAll(PDO::FETCH_ASSOC);
?>

<footer class="subject-page__footer">
  <div class="footer-container">
    
    <!-- คอลัมน์ที่ 1: โลโก้แบรนด์ -->
    <div class="footer-col footer-brand-col">
      <p class="footer-title">💚 ITDevTeam Coding</p>
      <p class="footer-desc">สร้างสรรค์การเรียนรู้การเขียนโค้ดให้สนุกและง่ายเหมือนเล่นเกม</p>
    </div>

    <!-- คอลัมน์ดึงจากฐานข้อมูลแบบ Dynamic -->
    <?php foreach ($sections as $sec): ?>
      <div class="footer-col">
        <h3 class="footer-heading"><?= htmlspecialchars($sec['title']) ?></h3>
        <ul class="footer-links">
          <?php
            $links_stmt = $pdo->prepare("SELECT * FROM footer_links WHERE section_id = ? ORDER BY sort_order ASC, id ASC");
            $links_stmt->execute([$sec['id']]);
            $links = $links_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($links as $link):
          ?>
            <li><a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>

  </div>
</footer>