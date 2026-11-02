<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId) {
    http_response_code(400);
    exit('คำขอไม่ถูกต้อง');
}
$statement = $pdo->prepare('SELECT original_name, stored_path, mime_type FROM print_doc_documents WHERE id = ? AND user_id = ?');
$statement->execute([$documentId, $user['id']]);
$document = $statement->fetch();
if (!$document) {
    http_response_code(404);
    exit('ไม่พบเอกสาร');
}
$filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $document['stored_path']);
$realRoot = realpath(__DIR__ . '/uploads/documents/' . (int) $user['id']);
$realFile = realpath($filePath);
if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR) || !is_file($realFile)) {
    http_response_code(404);
    exit('ไม่พบไฟล์');
}
header('Content-Type: ' . $document['mime_type']);
header('Content-Length: ' . filesize($realFile));
header('Content-Disposition: attachment; filename="document"; filename*=UTF-8\'\'' . rawurlencode($document['original_name']));
header('X-Content-Type-Options: nosniff');
readfile($realFile);
exit;
