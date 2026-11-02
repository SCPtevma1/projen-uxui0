<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/database.php';
$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php promote_admin.php user@example.com\n");
    exit(2);
}
$statement = $pdo->prepare("UPDATE print_doc_users SET role = 'admin' WHERE email = ?");
$statement->execute([$email]);
if ($statement->rowCount() === 0) {
    $check = $pdo->prepare('SELECT role FROM print_doc_users WHERE email = ?');
    $check->execute([$email]);
    $role = $check->fetchColumn();
    if ($role === false) {
        fwrite(STDERR, "No PrintDoc account found for {$email}\n");
        exit(1);
    }
}
fwrite(STDOUT, "Admin access enabled for {$email}\n");
