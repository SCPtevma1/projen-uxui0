<?php
/**
 * includes/sidebar.php — Sidebar Navigation ส่วนกลาง
 * ตัวแปรที่ต้องกำหนดก่อน include:
 *   $activePage (string) — ชื่อหน้าปัจจุบัน เช่น 'dashboard', 'manage_users', 'manage_footer', 'settings'
 */
$activePage = $activePage ?? '';

$menuItems = [
    'dashboard'        => ['href' => 'dashboard.php',        'icon' => 'bi-speedometer2',      'label' => 'ภาพรวมระบบ'],
    'manage_users'     => ['href' => 'manage_users.php',     'icon' => 'bi-people',             'label' => 'จัดการผู้ใช้งาน'],
    'manage_products'  => ['href' => 'manage_products.php',  'icon' => 'bi-box-seam',           'label' => 'รายการสินค้า'],
    'manage_services'  => ['href' => 'manage_services.php',  'icon' => 'bi-window-stack',       'label' => 'จัดการเว็บไซต์'],
    'manage_orders'    => ['href' => 'manage_orders.php',    'icon' => 'bi-cart-check',         'label' => 'จัดการคำสั่งซื้อ'],
    'manage_comments'  => ['href' => 'manage_comments.php',  'icon' => 'bi-chat-left-text',     'label' => 'จัดการความคิดเห็น'],
    'manage_footer'    => ['href' => 'manage_footer.php',    'icon' => 'bi-file-earmark-text',  'label' => 'จัดการ Footer'],
    'settings'         => ['href' => 'settings.php',         'icon' => 'bi-gear',               'label' => 'ตั้งค่าระบบ'],
];
?>
<aside class="col-md-3 col-lg-2 sidebar p-3">
    <div class="px-2 py-3 mb-2 text-white fw-bold fs-5 border-bottom border-secondary">
        <i class="bi bi-shield-lock-fill text-primary me-2"></i>Admin Panel
    </div>
    <?php foreach ($menuItems as $key => $item): ?>
        <a href="<?= $item['href'] ?>" class="<?= $activePage === $key ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?> me-2"></i> <?= $item['label'] ?>
        </a>
    <?php endforeach; ?>
</aside>
