<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = current_user();
if ($user === null) {
    json_response(['error' => 'กรุณาเข้าสู่ระบบก่อน'], 401);
}
$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : $_POST;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf($input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['error' => 'คำขอไม่ถูกต้องหรือเซสชันหมดอายุ'], 400);
}
$documentId = filter_var($input['document_id'] ?? null, FILTER_VALIDATE_INT);
$pages = filter_var($input['pages'] ?? null, FILTER_VALIDATE_INT);
$source = $input['source'] ?? '';
if (!$documentId || !$pages || $pages > 10000 || !in_array($source, ['pdf', 'manual'], true)) {
    json_response(['error' => 'ข้อมูลจำนวนหน้าไม่ถูกต้อง'], 422);
}
$statement = $pdo->prepare('SELECT id, file_type FROM print_doc_documents WHERE id = ? AND user_id = ?');
$statement->execute([$documentId, $user['id']]);
$document = $statement->fetch();
if (!$document) {
    json_response(['error' => 'ไม่พบเอกสารในคลังของคุณ'], 404);
}
if ($source === 'pdf' && $document['file_type'] !== 'pdf') {
    json_response(['error' => 'วิธีระบุจำนวนหน้าไม่ตรงกับชนิดเอกสาร'], 422);
}
$update = $pdo->prepare('UPDATE print_doc_documents SET page_count = ? WHERE id = ? AND user_id = ?');
$update->execute([$pages, $documentId, $user['id']]);
json_response(['document_id' => $documentId, 'pages' => $pages]);
