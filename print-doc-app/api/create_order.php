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
$ids = $input['document_ids'] ?? null;
$copies = filter_var($input['copies'] ?? null, FILTER_VALIDATE_INT);
$options = $input['options'] ?? [];
if (!is_array($ids) || $ids === [] || count($ids) > 100 || !$copies || $copies > 100 || !is_array($options)) {
    json_response(['error' => 'กรุณาเลือกเอกสารและตรวจสอบจำนวนชุด'], 422);
}
$documentIds = array_values(array_unique(array_filter(
    array_map(static fn ($id) => filter_var($id, FILTER_VALIDATE_INT), $ids),
    static fn ($id) => $id !== false && $id > 0
)));
if (count($documentIds) !== count($ids)) {
    json_response(['error' => 'รายการเอกสารไม่ถูกต้อง'], 422);
}
$placeholders = implode(',', array_fill(0, count($documentIds), '?'));
$query = $pdo->prepare(
    "SELECT id, page_count FROM print_doc_documents
     WHERE user_id = ? AND page_count IS NOT NULL AND id IN ({$placeholders})"
);
$query->execute(array_merge([$user['id']], $documentIds));
$documents = $query->fetchAll();
if (count($documents) !== count($documentIds)) {
    json_response(['error' => 'เลือกเอกสารของคุณที่ระบุจำนวนหน้าแล้วเท่านั้น'], 422);
}
$totalPages = array_sum(array_map(static fn ($document) => (int) $document['page_count'], $documents));
$color = $options['color'] ?? '';
$paper = $options['paper'] ?? '';
$binding = $options['binding'] ?? '';
$delivery = $options['delivery'] ?? '';
$shippingAddress = $options['shipping_address'] ?? '';
if (!is_string($shippingAddress)
    || !in_array($color, ['bw', 'color'], true)
    || !in_array($paper, ['a4_70', 'a3_70', 'b5_70', 'a4_80', 'a4_card'], true)
    || !in_array($binding, ['none', 'staple', 'spiral', 'coil'], true)
    || !in_array($delivery, ['pickup', 'shipping'], true)) {
    json_response(['error' => 'ตัวเลือกคำสั่งพิมพ์ไม่ถูกต้อง'], 422);
}
$shippingAddress = trim($shippingAddress);
if (($delivery === 'shipping' && ($shippingAddress === '' || mb_strlen($shippingAddress) > 500))
    || ($delivery === 'pickup' && $shippingAddress !== '')) {
    json_response(['error' => 'กรุณาระบุที่อยู่จัดส่ง (ไม่เกิน 500 ตัวอักษร)'], 422);
}
try {
    $totals = calculate_order(app_settings(), $totalPages, $copies, $color, $paper, $binding, $delivery);
} catch (InvalidArgumentException $exception) {
    json_response(['error' => $exception->getMessage()], 422);
}
$insert = $pdo->prepare(
    'INSERT INTO print_doc_orders (user_id, document_ids, options, total_pages, subtotal, total)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$insert->execute([
    $user['id'],
    json_encode($documentIds, JSON_THROW_ON_ERROR),
    json_encode([
        'copies' => $copies,
        'color' => $color,
        'paper' => $paper,
        'binding' => $binding,
        'delivery' => $delivery,
        'shipping_address' => $shippingAddress,
    ], JSON_THROW_ON_ERROR),
    $totals['total_pages'],
    $totals['subtotal'],
    $totals['total'],
]);
json_response(['order_id' => (int) $pdo->lastInsertId(), 'totals' => $totals], 201);
