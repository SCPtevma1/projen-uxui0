<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "oopp";
    
    $conn = new mysqli($host,$user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
    
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$priceColumn = $conn->query("SHOW COLUMNS FROM services LIKE 'price'");
if (!$priceColumn) {
    die("Unable to inspect services schema: " . $conn->error);
}
if ($priceColumn->num_rows === 0 && !$conn->query(
    "ALTER TABLE services ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00"
)) {
    die("Unable to add services.price: " . $conn->error);
}

$ordersTable = "CREATE TABLE IF NOT EXISTS service_orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$conn->query($ordersTable)) {
    die("Unable to initialize service_orders: " . $conn->error);
}

$serviceImagesTable = "CREATE TABLE IF NOT EXISTS service_carousel_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_carousel_images_service (service_id, sort_order),
    CONSTRAINT fk_service_carousel_images_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (!$conn->query($serviceImagesTable)) {
    die("Unable to initialize service_carousel_images: " . $conn->error);
}
?>