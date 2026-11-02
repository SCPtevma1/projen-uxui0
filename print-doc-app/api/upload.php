<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$user = current_user();
if ($user === null) {
    json_response(['error' => 'กรุณาเข้าสู่ระบบก่อนอัปโหลด'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf($_POST['csrf_token'] ?? null)) {
    json_response(['error' => 'คำขอไม่ถูกต้องหรือเซสชันหมดอายุ'], 400);
}
if (!isset($_FILES['documents']) || !is_array($_FILES['documents']['name'])) {
    json_response(['error' => 'กรุณาเลือกไฟล์อย่างน้อยหนึ่งไฟล์'], 422);
}

$allowedTypes = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'txt' => ['text/plain', 'text/x-c'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
];
$maxFileSize = 15 * 1024 * 1024;
$uploadRoot = dirname(__DIR__) . '/uploads/documents/' . (int) $user['id'];
if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0750, true) && !is_dir($uploadRoot)) {
    error_log('Unable to create print document upload directory');
    json_response(['error' => 'ไม่สามารถเตรียมพื้นที่จัดเก็บไฟล์ได้'], 500);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$saved = [];
$errors = [];
$count = count($_FILES['documents']['name']);
if ($count > 10) {
    json_response(['error' => 'อัปโหลดได้ไม่เกิน 10 ไฟล์ต่อครั้ง'], 422);
}
for ($index = 0; $index < $count; $index++) {
    $errorCode = (int) $_FILES['documents']['error'][$index];
    $originalName = trim((string) $_FILES['documents']['name'][$index]);
    $temporaryPath = (string) $_FILES['documents']['tmp_name'][$index];
    $size = (int) $_FILES['documents']['size'][$index];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($temporaryPath)) {
        $errors[] = $originalName . ': อัปโหลดไม่สำเร็จ';
        continue;
    }
    if ($size < 1 || $size > $maxFileSize) {
        $errors[] = $originalName . ': ไฟล์ต้องมีขนาดไม่เกิน 15 MB';
        continue;
    }
    if (!isset($allowedTypes[$extension])) {
        $errors[] = $originalName . ': ไม่รองรับไฟล์ชนิดนี้';
        continue;
    }
    $mime = $finfo->file($temporaryPath);
    if (!in_array($mime, $allowedTypes[$extension], true)) {
        $errors[] = $originalName . ': ประเภทไฟล์ไม่ตรงกับนามสกุล';
        continue;
    }
    $header = file_get_contents($temporaryPath, false, null, 0, 8);
    if (in_array($extension, ['doc', 'xls', 'ppt'], true) && !str_starts_with((string) $header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
        $errors[] = $originalName . ': รูปแบบไฟล์ Office ไม่ถูกต้อง';
        continue;
    }
    if (in_array($extension, ['docx', 'xlsx', 'pptx'], true) && !str_starts_with((string) $header, "PK\x03\x04")) {
        $errors[] = $originalName . ': รูปแบบไฟล์ Office ไม่ถูกต้อง';
        continue;
    }
    $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($originalName));
    $cleanName = mb_substr($cleanName ?: 'document.' . $extension, 0, 255);
    $storedName = bin2hex(random_bytes(24)) . '.' . $extension;
    $storedPath = $uploadRoot . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($temporaryPath, $storedPath)) {
        $errors[] = $originalName . ': บันทึกไฟล์ไม่สำเร็จ';
        continue;
    }
    try {
        $statement = $pdo->prepare(
            'INSERT INTO print_doc_documents (user_id, original_name, stored_path, file_type, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $user['id'],
            $cleanName,
            'uploads/documents/' . (int) $user['id'] . '/' . $storedName,
            $extension,
            $mime,
            $size,
        ]);
        $saved[] = [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $cleanName,
            'type' => $extension,
            'pages' => null,
        ];
    } catch (Throwable $exception) {
        unlink($storedPath);
        throw $exception;
    }
}

if ($saved === [] && $errors !== []) {
    json_response(['error' => implode("\n", $errors)], 422);
}
json_response(['documents' => $saved, 'errors' => $errors], $errors === [] ? 201 : 207);
