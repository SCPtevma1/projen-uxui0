<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = current_user();
if ($user === null) {
    json_response(['error' => 'กรุณาเข้าสู่ระบบก่อน'], 401);
}
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input) || !valid_csrf($input['csrf_token'] ?? null)) {
    json_response(['error' => 'คำขอไม่ถูกต้องหรือเซสชันหมดอายุ'], 400);
}
$dataUrl = $input['signature'] ?? '';
if (!is_string($dataUrl) || !preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
    json_response(['error' => 'ลายเซ็นต้องเป็นภาพ PNG'], 422);
}
$binary = base64_decode($matches[1], true);
if ($binary === false || strlen($binary) > 1024 * 1024) {
    json_response(['error' => 'ไฟล์ลายเซ็นไม่ถูกต้องหรือมีขนาดใหญ่เกินไป'], 422);
}
$imageInfo = getimagesizefromstring($binary);
if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_PNG || $imageInfo[0] > 1600 || $imageInfo[1] > 600) {
    json_response(['error' => 'ขนาดหรือชนิดภาพลายเซ็นไม่ถูกต้อง'], 422);
}
$directory = dirname(__DIR__) . '/uploads/signatures';
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    error_log('Unable to create signature directory');
    json_response(['error' => 'ไม่สามารถเตรียมพื้นที่จัดเก็บลายเซ็นได้'], 500);
}
$storedName = 'user-' . (int) $user['id'] . '.png';
$target = $directory . DIRECTORY_SEPARATOR . $storedName;
if (file_put_contents($target, $binary, LOCK_EX) === false) {
    json_response(['error' => 'บันทึกลายเซ็นไม่สำเร็จ'], 500);
}
$path = 'uploads/signatures/' . $storedName;
$update = $pdo->prepare('UPDATE print_doc_users SET signature_path = ? WHERE id = ?');
$update->execute([$path, $user['id']]);
json_response(['message' => 'บันทึกลายเซ็นแล้ว']);
