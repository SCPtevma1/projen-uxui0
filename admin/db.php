<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล MySQL
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "oopp";

// ปิดการโยน uncaught mysqli_sql_exception เพื่อจัดการ error เองอย่างปลอดภัย
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    // แสดงข้อความแจ้งเตือนที่เข้าใจง่ายหากฐานข้อมูลยังไม่ได้เปิดหรือเชื่อมต่อไม่สำเร็จ
    http_response_code(500);
    die("<div style='font-family: sans-serif; padding: 24px; max-width: 600px; margin: 40px auto; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; color: #9f1239;'>"
        . "<h3 style='margin-top:0;'>⚠️ การเชื่อมต่อฐานข้อมูลล้มเหลว (Database Connection Failed)</h3>"
        . "<p>ไม่สามารถเชื่อมต่อกับ MySQL ได้: " . htmlspecialchars($conn->connect_error) . "</p>"
        . "<p style='font-size: 13px; color: #475569;'>กรุณาตรวจสอบว่า Apache และ MySQL ใน XAMPP Control Panel กำลังทำงานอยู่ และมีฐานข้อมูลชื่อ <code>$dbname</code> เรียบร้อยแล้ว</p>"
        . "</div>");
}

$conn->set_charset("utf8mb4");

/**
 * ฟังก์ชันตรวจสอบและสร้างโครงสร้างตารางที่จำเป็น (Migrations / Initial Setup)
 * ทำงานเฉพาะเมื่อระบบยังไม่มีตาราง หรือเรียกใช้งานแบบ Force เพื่อความลื่นไหลของหน้าเว็บ
 */
function ensureDatabaseSchema(mysqli $conn, bool $force = false): void
{
    static $alreadyRun = false;
    if ($alreadyRun && !$force) {
        return;
    }
    $alreadyRun = true;

    // 1. ตรวจสอบคอลัมน์ price ใน services
    $priceCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'price'");
    if ($priceCheck && $priceCheck->num_rows === 0) {
        $conn->query("ALTER TABLE services ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }

    // 2. ตาราง service_orders
    $conn->query("CREATE TABLE IF NOT EXISTS service_orders (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        service_title VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        customer_name VARCHAR(150) NOT NULL,
        customer_contact VARCHAR(100) NOT NULL,
        customer_email VARCHAR(190) NULL,
        notes TEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_service_orders_created_at (created_at),
        INDEX idx_service_orders_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. ตาราง service_carousel_images
    $conn->query("CREATE TABLE IF NOT EXISTS service_carousel_images (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_service_carousel_images_service (service_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. ตาราง footer_sections
    $conn->query("CREATE TABLE IF NOT EXISTS footer_sections (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. ตาราง footer_links
    $conn->query("CREATE TABLE IF NOT EXISTS footer_links (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        section_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL DEFAULT '#',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_footer_links_section (section_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 6. ตาราง site_comments สำหรับความคิดเห็น/คอมเมนต์จากหน้าบ้าน
    $conn->query("CREATE TABLE IF NOT EXISTS site_comments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(100) NOT NULL DEFAULT 'student',
        user_name VARCHAR(100) NOT NULL,
        comment TEXT NOT NULL,
        rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
        status VARCHAR(20) NOT NULL DEFAULT 'approved',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_site_comments_created (created_at),
        INDEX idx_site_comments_page (page)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 7. ตาราง contact_messages สำหรับข้อความส่งถึงเราจากหน้าบ้าน
    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        sender_name VARCHAR(150) NOT NULL,
        sender_contact VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'unread',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contact_created_at (created_at),
        INDEX idx_contact_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 8. ตาราง site_settings สำหรับการตั้งค่าเว็บไซต์
    $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 9. ตรวจสอบและ Seed ข้อมูลเริ่มต้น
    seedDefaultFooterIfEmpty($conn);
    seedDefaultCommentsIfEmpty($conn);
    seedDefaultContactMessagesIfEmpty($conn);
    seedDefaultSettingsIfEmpty($conn);
}

/**
 * Seed ข้อมูลข้อความติดต่อเริ่มต้น
 */
function seedDefaultContactMessagesIfEmpty(mysqli $conn): void
{
    $check = $conn->query("SELECT COUNT(*) AS total FROM contact_messages");
    if ($check && ($row = $check->fetch_assoc()) && (int)$row['total'] === 0) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (sender_name, sender_contact, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
        if ($stmt) {
            $samples = [
                ['คุณสมชาย ธุรกิจก้าวหน้า', 'somchai@biz.com', 'สอบถามแพ็กเกจเว็บไซต์องค์กร', 'สนใจแพ็กเกจเว็บไซต์สำหรับบริษัท SME มีระบบหลังบ้านจัดการข้อมูลสินค้าและออร์เดอร์ อยากสอบถามระยะเวลาดำเนินการครับ'],
                ['คุณวิภาดา กลิ่นหอม', '089-123-4567', 'ปรึกษาการออกแบบ UX/UI เว็บคลินิก', 'ต้องการปรึกษาการทำหน้าเว็บนัดหมายแพทย์และบริการของคลินิกความงาม มีทีมงานติดต่อกลับได้ไหมคะ']
            ];
            foreach ($samples as $s) {
                $stmt->bind_param("ssss", $s[0], $s[1], $s[2], $s[3]);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

/**
 * Seed ข้อมูลความคิดเห็นเริ่มต้นสำหรับหน้าบ้าน
 */
function seedDefaultCommentsIfEmpty(mysqli $conn): void
{
    $check = $conn->query("SELECT COUNT(*) AS total FROM site_comments");
    if ($check && ($row = $check->fetch_assoc()) && (int)$row['total'] === 0) {
        $stmt = $conn->prepare("INSERT INTO site_comments (page, user_name, comment, rating, status) VALUES (?, ?, ?, ?, 'approved')");
        if ($stmt) {
            $samples = [
                ['student', 'น้องมินท์ (ICT ม.เกษตร)', 'บทเรียนเข้าใจง่ายมากครับ มี Roadmap ชัดเจน ทำให้รู้ว่าต้องเริ่มเรียนจากอะไรก่อน', 5],
                ['student', 'พีรพล (นักศึกษาปี 2)', 'ชอบส่วนของ Coding roadmap มาก มีทั้ง HTML, CSS, JS และ PHP ครบวงจรครับ', 5],
                ['student', 'ทีมงาน Developer SME', 'เว็บไซต์โหลดเร็วขึ้นเยอะ และการออกแบบ UI สวยงามน่าใช้งานมากครับ', 5]
            ];
            foreach ($samples as $s) {
                $stmt->bind_param("sssi", $s[0], $s[1], $s[2], $s[3]);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

/**
 * Seed ข้อมูลเริ่มต้นสำหรับส่วน Footer ในกรณีที่ยังไม่มีข้อมูลในฐานข้อมูล
 */
function seedDefaultFooterIfEmpty(mysqli $conn): void
{
    $check = $conn->query("SELECT COUNT(*) AS total FROM footer_sections");
    if ($check && ($row = $check->fetch_assoc()) && (int)$row['total'] === 0) {
        $defaultSections = [
            [
                'title' => 'บริการของเรา',
                'sort' => 1,
                'links' => [
                    ['title' => 'ออกแบบเว็บไซต์ UX/UI', 'url' => '/index.php'],
                    ['title' => 'โปรโมชั่น / แพ็กเกจ', 'url' => '/hok/pro.html'],
                    ['title' => 'บริการเสริม & ออกแบบกราฟิก', 'url' => '/html/ass.html'],
                    ['title' => 'สั่งจ้างทำเว็บไซต์', 'url' => '/order.php']
                ]
            ],
            [
                'title' => 'คอร์สเรียน & โซลูชัน',
                'sort' => 2,
                'links' => [
                    ['title' => 'สำหรับนักเรียน / นักศึกษา', 'url' => '/demo-container/student.html'],
                    ['title' => 'สำหรับโรงพยาบาลและคลินิก', 'url' => '/demo-container/hospital.html'],
                    ['title' => 'สำหรับธุรกิจขนาดย่อม (SME)', 'url' => '/demo-container/small-business.html'],
                    ['title' => 'สำหรับธุรกิจขนาดใหญ่', 'url' => '/demo-container/enterprise.html']
                ]
            ],
            [
                'title' => 'ช่วยเหลือ & ติดต่อ',
                'sort' => 3,
                'links' => [
                    ['title' => 'ช่องทางการติดต่อทั้งหมด (Link in Bio)', 'url' => '/hok/hok-2/hok-3.html'],
                    ['title' => 'ระบบหลังบ้าน (Admin)', 'url' => '/admin/login.php'],
                    ['title' => 'คำถามที่พบบ่อย (FAQ)', 'url' => '#'],
                    ['title' => 'นโยบายความเป็นส่วนตัว', 'url' => '#']
                ]
            ]
        ];

        $stmtSec = $conn->prepare("INSERT INTO footer_sections (title, sort_order) VALUES (?, ?)");
        $stmtLink = $conn->prepare("INSERT INTO footer_links (section_id, title, url, sort_order) VALUES (?, ?, ?, ?)");

        if ($stmtSec && $stmtLink) {
            foreach ($defaultSections as $sec) {
                $stmtSec->bind_param("si", $sec['title'], $sec['sort']);
                $stmtSec->execute();
                $sectionId = $conn->insert_id;

                $linkOrder = 1;
                foreach ($sec['links'] as $link) {
                    $stmtLink->bind_param("issi", $sectionId, $link['title'], $link['url'], $linkOrder);
                    $stmtLink->execute();
                    $linkOrder++;
                }
            }
            $stmtSec->close();
            $stmtLink->close();
        }
    }
}

/**
 * Seed ค่าตั้งค่าเว็บไซต์เริ่มต้น
 */
function seedDefaultSettingsIfEmpty(mysqli $conn): void
{
    $check = $conn->query("SELECT COUNT(*) AS total FROM site_settings");
    if ($check && ($row = $check->fetch_assoc()) && (int)$row['total'] === 0) {
        $defaults = [
            'site_title'       => 'Projen Studio',
            'site_tagline'     => 'ออกแบบและพัฒนาเว็บไซต์คุณภาพสูง',
            'site_description' => 'ระบบจัดการบริการและสินค้า ออกแบบ UX/UI โดยทีมงานมืออาชีพ',
            'site_keywords'    => 'ออกแบบเว็บไซต์, UX/UI, PHP, WordPress, เว็บไซต์องค์กร',
            'contact_email'    => 'v890x0@gmail.com',
            'contact_phone'    => '',
            'facebook_url'     => '',
            'line_id'          => '',
            'logo_path'        => '',
            'maintenance_mode' => '0',
        ];
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        if ($stmt) {
            foreach ($defaults as $key => $val) {
                $stmt->bind_param("ss", $key, $val);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

// ตรวจสอบความสมบูรณ์ของโครงสร้างและข้อมูลเริ่มต้น
ensureDatabaseSchema($conn);