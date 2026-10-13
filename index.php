<?php 
  require_once 'admin/db.php';
  $query = "SELECT * FROM services ORDER BY id DESC";
  $result = $conn->query($query);
  if (!$result) {
      die('Unable to load websites: ' . $conn->error);
  }
  $serviceRows = $result->fetch_all(MYSQLI_ASSOC);
  $galleryImages = [];
  if ($serviceRows) {
      $serviceIds = array_map(static fn($row) => (int) $row['id'], $serviceRows);
      $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
      $galleryQuery = $conn->prepare(
          "SELECT service_id, image_path FROM service_carousel_images WHERE service_id IN ($placeholders) ORDER BY sort_order, id"
      );
      if (!$galleryQuery) {
          die('Unable to prepare carousel image lookup: ' . $conn->error);
      }
      $galleryQuery->bind_param(str_repeat('i', count($serviceIds)), ...$serviceIds);
      if (!$galleryQuery->execute()) {
          die('Unable to load carousel images: ' . $galleryQuery->error);
      }
      $imageResult = $galleryQuery->get_result();
      while ($imageRow = $imageResult->fetch_assoc()) {
          $galleryImages[(int) $imageRow['service_id']][] = $imageRow['image_path'];
      }
      $galleryQuery->close();
  }
  foreach ($serviceRows as &$serviceRow) {
      $serviceRow['carousel_images'] = $galleryImages[(int) $serviceRow['id']] ?? [$serviceRow['image_url']];
  }
  unset($serviceRow);
  $serviceSlides = array_chunk($serviceRows, 3);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>หน้าแรก</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    #servicesCarousel > .services-pagination {
      position: static;
      margin: 1.25rem 0 0;
    }
    #servicesCarousel > .services-pagination [data-bs-target] {
      background-color: #4f46e5;
    }
    .service-gallery-carousel,
    .service-gallery-carousel .carousel-inner,
    .service-gallery-carousel .carousel-item {
      height: 100%;
    }
    .service-gallery-indicators {
      z-index: 2;
      margin-bottom: 6px;
    }
  </style>
</head>
<body>
  <header class="site-header">
    <a class="brand" href="/index.php">เว็บไซต์ของฉัน</a>
    <nav aria-label="เมนูหลัก">
      <a href="/index.php">หน้าแรก</a>
      <a href="hok/pro.html">โปรโมชั่น / แพ็คเกจ</a>
      <a href="/html/ass.html">บริการเสริม</a>
    </nav>
  </header>
  <main class="hero">
    <h1>ออกแบบUXUIหน้าเว็ปพร้อมใช้งาน</h1>
  </main>

<div id="demo-container" class="card-container">
                <a href="demo-container/student.html" class="card-item group">
                    <div class="card-icon bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white">
                       <i class="fa-solid fa-user-graduate" style="color: rgb(81, 35, 217);" aria-hidden="true"></i>
                    </div>
                    <p class="card-title">สำหรับนักศึกษา</p>
                    <p class="card-subtitle">ส่วนลดคอร์สเรียนและเครื่องมือสำหรับวัยเรียน</p>
                    <span class ="card-arrow">
                        ดูรายละเอียด <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a> 
                <a href="demo-container/hospital.html" class="card-item group">
                    <div class="card-icon bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fa-solid fa-hospital" style="color: rgb(81, 35, 217);" aria-hidden="true"></i>
                    </div>
                    <p class="card-title">โรงพยาบาล</p>
                    <p class="card-subtitle">โซลูชันด้านสาธารณสุขและการดูแลสุขภาพ</p>
                    <span class="card-arrow text-emerald-600">
                        ดูรายละเอียด <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>

                
                <a href="demo-container/small-business.html" class="card-item group">
                    <div class="card-icon bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white">
                        <i class="fa-solid fa-store" style="color: rgb(81, 35, 217);" aria-hidden="true"></i>
                    </div>
                    <p class="card-title">สำหรับธุรกิจเล็ก</p>
                    <p class="card-subtitle">แพ็กเกจเริ่มต้นประหยัด คุ้มค่าสำหรับ SME</p>
                    <span class="card-arrow text-amber-600">
                        ดูรายละเอียด <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>

                <a href="demo-container/enterprise.html" class="card-item group">
                    <div class="card-icon bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fa-solid fa-building" style="color: rgb(81, 35, 217);" aria-hidden="true"></i>
                    </div>
                    <p class="card-title">สำหรับธุรกิจใหญ่</p>
                    <p class="card-subtitle">ระบบระดับองค์กร รองรับการขยายตัวสูง</p>
                    <span class="card-arrow text-indigo-600">
                        ดูรายละเอียด <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>
                
                <a href="demo-container/organization.html" class="card-item group">
                    <div class="card-icon bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white">
                    <i class="fa-solid fa-sitemap" style="color: rgb(81, 35, 217);" aria-hidden="true"></i>
                    </div>
                    <p class="card-title">สำหรับองค์กร</p>
                    <p class="card-subtitle">ระบบระดับองค์กร รองรับการขยายตัวสูง</p>
                    <span class="card-arrow text-indigo-600">
                        ดูรายละเอียด <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </a>
            </div>
<div class="VVS">
  <button type="button" onclick="window.location.href='html/จ่างาน.html'">จ้างงาน</button>
  <button type="button" onclick="window.location.href='html/จ่างาน.html'">อนิเมะ</button>
  <button type="button" onclick="window.location.href='html/จ่างาน.html'">ภาคเสียงภาษาต่างๆ</button>
  <button type="button" onclick="window.location.href='html/จ่างาน.html'">รับทำระบบPOS</button>
</div>
<section class="coco" aria-label="เว็บไซต์และบริการ">
  <h2 class="text-center mb-4">เว็บไซต์และแพ็กเกจ</h2>
  <?php if ($serviceSlides): ?>
  <div id="servicesCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="8000" aria-label="รายการเว็บไซต์">
    <div class="carousel-inner">
      <?php foreach ($serviceSlides as $slideIndex => $slide): ?>
      <div class="carousel-item <?= $slideIndex === 0 ? 'active' : '' ?>">
        <div class="services-grid-container">
          <?php foreach ($slide as $row): ?>
          <article class="service-box-item">
            <div class="service-box__image-wrapper">
              <?php $imageSlides = $row['carousel_images']; ?>
              <?php if (count($imageSlides) > 1): ?>
                <div id="service-gallery-<?= (int) $row['id'] ?>" class="carousel slide service-gallery-carousel" data-bs-ride="carousel" data-bs-interval="4000" data-bs-pause="false" aria-label="รูปภาพ <?= htmlspecialchars($row['title']) ?>">
                  <div class="carousel-inner">
                    <?php foreach ($imageSlides as $imageIndex => $imagePath): ?>
                      <div class="carousel-item <?= $imageIndex === 0 ? 'active' : '' ?>">
                        <img class="service-box__image" src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($row['title']) ?> รูปที่ <?= $imageIndex + 1 ?>" loading="<?= $imageIndex === 0 ? 'eager' : 'lazy' ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="carousel-indicators service-gallery-indicators">
                    <?php foreach ($imageSlides as $imageIndex => $imagePath): ?>
                      <button type="button" data-bs-target="#service-gallery-<?= (int) $row['id'] ?>" data-bs-slide-to="<?= $imageIndex ?>" class="<?= $imageIndex === 0 ? 'active' : '' ?>" aria-label="รูปที่ <?= $imageIndex + 1 ?>" <?= $imageIndex === 0 ? 'aria-current="true"' : '' ?>></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php elseif (!empty($imageSlides[0])): ?>
                <img class="service-box__image" src="<?= htmlspecialchars($imageSlides[0]) ?>" alt="<?= htmlspecialchars($row['title']) ?>" loading="lazy">
              <?php else: ?>
                <div class="service-box__image" role="img" aria-label="ไม่มีรูปภาพ"></div>
              <?php endif; ?>
              <?php if (!empty($row['web_type'])): ?><span class="web-type-badge"><?= htmlspecialchars($row['web_type']) ?></span><?php endif; ?>
            </div>
            <div class="service-box__body">
              <p class="eyebrow"><?= htmlspecialchars($row['eyebrow']) ?></p>
              <h2><?= htmlspecialchars($row['title']) ?></h2>
              <p class="desc-text"><?= htmlspecialchars($row['description']) ?></p>
              <?php if (!empty($row['tech_tags'])): ?>
                <div class="tech-tags-wrapper">
                  <?php foreach (explode(',', $row['tech_tags']) as $tag): $cleanTag = trim($tag); if ($cleanTag !== ''): ?>
                    <span class="tech-badge tag-generic"><?= htmlspecialchars($cleanTag) ?></span>
                  <?php endif; endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-auto">
                <strong class="text-white">฿<?= number_format((float) $row['price'], 2) ?></strong>
                <a class="service-box__button" href="detail.php?id=<?= (int) $row['id'] ?>">ดูรายละเอียด / สั่งซื้อ</a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($serviceSlides) > 1): ?>
      <div class="carousel-indicators services-pagination">
        <?php foreach ($serviceSlides as $slideIndex => $slide): ?>
          <button type="button" data-bs-target="#servicesCarousel" data-bs-slide-to="<?= $slideIndex ?>" class="<?= $slideIndex === 0 ? 'active' : '' ?>" aria-label="รายการชุดที่ <?= $slideIndex + 1 ?>" <?= $slideIndex === 0 ? 'aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php else: ?>
    <p class="text-center text-secondary py-4">ยังไม่มีรายการเว็บไซต์ เพิ่มรายการได้ที่หน้า admin/manage_services.php</p>
  <?php endif; ?>
</section>

<div class="projor">
  <h1>ขั้นตอนการทำงาน-ตรวจเช็กงาน</h1>
</div>

<div class="process-cards">

  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>รับทำ SEO</p>
    <h3>รับทำ SEO</h3>
  </div>

  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>เช็กโครงสร้างเว็บไซต์</p>
    <h3>รับทำ SEO</h3>
  </div>

  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>ออกแบบหน้าเว็บ</p>
    <h3>รับทำ SEO</h3>
  </div>
</div>
<!------>
<div class="process-cards">
  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>รับทำ SEO</p>
    <h3>รับทำ SEO</h3>
  </div>

  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>เช็กโครงสร้างเว็บไซต์</p>
    <h3>รับทำ SEO</h3>
  </div>

  <div class="info-card__body-box1">
    <div class="info-card__image-box1">
      <img src="./img/chiemseherin-milky-way-8149815_1920.jpg" alt="บริการ">
    </div>
    <p>ออกแบบหน้าเว็บ</p>
    <h3>รับทำ SEO</h3>
  </div>
</div>
<div class="projor"><h1>ดูแลหลังการขาย</h1></div>
<div class="vs-vs">
<div class="ppoo ppoo-benefits">
    <div class="ppoo-badge">✨ บริการดูแลฟรี</div>
    <h3 class="ppoo-title">การรับประกัน & การดูแล</h3>
    <ul class="benefit-list">
      <li><span class="check-icon">✓</span> แก้บัค/ข้อผิดพลาดฟรี</li>
      <li><span class="check-icon">✓</span> อัปเดตเนื้อหาตามที่ตกลง</li>
      <li><span class="check-icon">✓</span> สำรองข้อมูลเว็บไซต์</li>
      <li><span class="check-icon">✓</span> ตรวจสอบความปลอดภัย</li>
      <li><span class="check-icon">✓</span> ให้คำปรึกษาตลอดระยะดูแล</li>
    </ul>
  </div>

  <!-- การ์ดฝั่งขวา: แพ็กเกจราคาดูแลต่อ -->
  <div class="ppoo ppoo-pricing">
    <div class="ppoo-badge badge-orange">🚀 สิทธิพิเศษ</div>
    <h3 class="ppoo-title">ซื้อแพ็กเก็ตดูแลต่อได้</h3>
    <div class="price-list">
      <div class="price-item">
        <span class="service-name">เว็บไซต์หน้าเดียว</span>
        <span class="service-price">1,400 <span>บาท</span></span>
      </div>
      <div class="price-item">
        <span class="service-name">เว็บไซต์หลายหน้า</span>
        <span class="service-price">4,900 <span>บาท</span></span>
      </div>
      <div class="price-item highlight">
        <span class="service-name">เว็บไซต์ครบวงจร</span>
        <span class="service-price">6,900 <span>บาท</span></span>
      </div>
    </div>
  </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/app.js?v=2"></script>
  <script src="js/appwrite.js"></script>
  <footer class="site-footer">
    <div class="">
      <img src="./img/ahmetyuksek-mountain-range-9842371_1920.jpg" alt="" width="133px" style="border-radius: 16px;">
    </div>
    <div class="site-footer__grid">
      <div class="site-footer__column">
        <h4>บริการของเรา</h4>
        <ul>
          <li><a href="html2/page-01.html">สำเร็จรูป: สำหรับคนที่ต้องการงานเร็ว</a></li>
          <li><a href="html2/page-02.html">รับออกแบบใหม่ตามโจทย์ลูกค้า</a></li>
          <li><a href="html2/page-03.html">ให้คำแนะนำสำหรับนักเรียนที่ทำโปรเจกต์</a></li>
        </ul>
      </div>
      <div class="site-footer__column">
        <h4>เกี่ยวกับ</h4>
        <ul>
          <li><a href="index.html">เกี่ยวกับ THE SPACE</a></li>
          <li><a href="html2/page-08.html">ทีมงานของเรา</a></li>
          <li><a href="html2/page-04.html">ผลงานของเรา</a></li>
          <li><a href="html2/page-09.html">รีวิวจากลูกค้า</a></li>
        </ul>
      </div>
      <div class="site-footer__column">
        <h4>ช่วยเหลือและสนับสนุน</h4>
        <ul>
          <li><a href="html2/page-05.html">คำถามที่พบบ่อย</a></li>
          <li><a href="html2/page-06.html">นโยบายการให้บริการ</a></li>
          <li><a href="html2/page-10.html">คู่มือการใช้งาน</a></li>
          <li><a href="html2/page-11.html">แจ้งปัญหาการใช้งาน</a></li>
        </ul>
      </div>
      <div class="site-footer__column">
        <h4>ช่องทางการติดต่อ</h4>
        <ul>
          <li><a href="mailto:hello@thespace.example">v890x0@gmail.com</a></li>
          <li>โทร. 0653276864</li>
          <li><a href="html2/page-07.html">ส่งข้อความถึงเรา</a></li>
          <li>จันทร์ - ศุกร์ 09:00 - 18:00 น.</li>
          <li>กรุงเทพฯ และปริมณฑล</li>
        </ul>
      </div>
    </div>
    <div class="site-footer__bottom">
      <p>© 2026 THE SPACE. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>
