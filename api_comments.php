<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/admin/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page = trim($_GET['page'] ?? 'student');
    $stmt = $conn->prepare("SELECT id, user_name, comment, rating, created_at FROM site_comments WHERE page = ? AND status = 'approved' ORDER BY id DESC LIMIT 50");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    $stmt->bind_param("s", $page);
    $stmt->execute();
    $res = $stmt->get_result();
    $comments = [];
    while ($row = $res->fetch_assoc()) {
        $row['created_formatted'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $comments[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

if ($method === 'POST') {
    // รองรับทั้ง application/json และ standard form POST
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $userName = trim($input['user_name'] ?? '');
    $commentText = trim($input['comment'] ?? '');
    $rating = (int)($input['rating'] ?? 5);
    $page = trim($input['page'] ?? 'student');

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    if ($userName === '' || $commentText === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกชื่อและความคิดเห็นให้ครบถ้วน']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO site_comments (page, user_name, comment, rating, status) VALUES (?, ?, ?, ?, 'approved')");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'ไม่สามารถเตรียมบันทึกข้อมูลได้: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssi", $page, $userName, $commentText, $rating);
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'ส่งความคิดเห็นเรียบร้อยแล้ว ขอบคุณสำหรับรีวิวของคุณครับ!',
            'comment' => [
                'id' => $newId,
                'user_name' => htmlspecialchars($userName),
                'comment' => htmlspecialchars($commentText),
                'rating' => $rating,
                'created_formatted' => date('d/m/Y H:i')
            ]
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'บันทึกความคิดเห็นไม่สำเร็จ: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
