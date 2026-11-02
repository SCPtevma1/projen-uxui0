<?php
declare(strict_types=1);

$host = getenv('PRINT_DOC_DB_HOST') ?: 'localhost';
$database = getenv('PRINT_DOC_DB_NAME') ?: 'oopp';
$username = getenv('PRINT_DOC_DB_USER') ?: 'root';
$password = getenv('PRINT_DOC_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS print_doc_users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            signature_path VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS print_doc_documents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(30) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL,
            page_count SMALLINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_print_doc_documents_owner (user_id, created_at),
            CONSTRAINT fk_print_doc_documents_user FOREIGN KEY (user_id)
                REFERENCES print_doc_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS print_doc_orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            document_ids JSON NOT NULL,
            options JSON NOT NULL,
            total_pages INT UNSIGNED NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_print_doc_orders_owner (user_id, created_at),
            CONSTRAINT fk_print_doc_orders_user FOREIGN KEY (user_id)
                REFERENCES print_doc_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS print_doc_settings (
            setting_key VARCHAR(60) NOT NULL PRIMARY KEY,
            setting_value DECIMAL(10,2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $defaults = [
        'bw_rate' => 2,
        'color_rate' => 5,
        'shipping_fee' => 40,
        'paper_a3' => 3,
        'paper_b5' => 0,
        'paper_80gsm' => 0.25,
        'paper_card' => 3,
        'binding_staple' => 0,
        'binding_spiral' => 20,
        'binding_coil' => 30,
        'bulk_threshold' => 50,
        'bulk_bw_rate' => 0.8,
        'minimum_order' => 20,
    ];
    $setting = $pdo->prepare(
        'INSERT IGNORE INTO print_doc_settings (setting_key, setting_value) VALUES (?, ?)'
    );
    foreach ($defaults as $key => $value) {
        $setting->execute([$key, $value]);
    }
} catch (PDOException $exception) {
    error_log('Print document app database error: ' . $exception->getMessage());
    http_response_code(500);
    exit('ไม่สามารถเชื่อมต่อฐานข้อมูลได้ โปรดตรวจสอบ MySQL/ฐานข้อมูล และค่า PRINT_DOC_DB_HOST, PRINT_DOC_DB_NAME, PRINT_DOC_DB_USER, PRINT_DOC_DB_PASSWORD');
}
