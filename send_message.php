<?php
session_start();
require_once __DIR__ . '/admin/db.php';

// สร้าง CSRF token หากยังไม่มี
if (empty($_SESSION['contact_csrf_token'])) {
    $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
}

$notice = '';
$error = '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
          || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// ตรวจสอบความพร้อมของฐานข้อมูล
$dbReady = isset($conn) && $conn instanceof mysqli && !$conn->connect_error;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$dbReady) {
        $errorMessage = 'ระบบฐานข้อมูลไม่พร้อมใช้งาน กรุณาลองใหม่อีกครั้ง';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        $error = $errorMessage;
    } else {
        // รับข้อมูลจาก JSON หรือ POST
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $data = is_array($jsonData) ? $jsonData : $_POST;

        $name = trim($data['sender_name'] ?? '');
        $contact = trim($data['sender_contact'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if ($name === '' || $contact === '' || $subject === '' || $message === '') {
            $errorMessage = 'กรุณากรอกข้อมูลให้ครบทุกช่อง (ชื่อ, ข้อมูลติดต่อ, หัวข้อ และข้อความ)';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $errorMessage]);
                exit;
            }
            $error = $errorMessage;
        } else {
            // บันทึกลงตาราง contact_messages
            $stmt = $conn->prepare("INSERT INTO contact_messages (sender_name, sender_contact, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
            if ($stmt) {
                $stmt->bind_param("ssss", $name, $contact, $subject, $message);
                if ($stmt->execute()) {
                    $successMessage = 'ส่งข้อความของคุณเรียบร้อยแล้ว ทีมงานจะติดต่อกลับโดยเร็วที่สุดครับ ขอบคุณครับ!';
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => true,
                            'message' => $successMessage,
                            'id' => $conn->insert_id
                        ]);
                        exit;
                    }
                    $notice = $successMessage;
                } else {
                    $errorMessage = 'บันทึกข้อความไม่สำเร็จ: ' . $stmt->error;
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $errorMessage]);
                        exit;
                    }
                    $error = $errorMessage;
                }
                $stmt->close();
            } else {
                $errorMessage = 'เตรียมส่งข้อความไม่สำเร็จ: ' . $conn->error;
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $errorMessage]);
                    exit;
                }
                $error = $errorMessage;
            }
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ส่งข้อความถึงเรา | THE SPACE & ITDevTeam</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Prompt', sans-serif;
            color: #1e293b;
            min-height: 100vh;
            padding: 40px 16px;
        }
        .contact-card {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .contact-info-col {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 40px 30px;
        }
        .contact-form-col {
            padding: 40px 36px;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 22px;
        }
        .info-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.92rem;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 0.95rem;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .btn-submit {
            background: #2563eb;
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-1 mb-2">
            <i class="ph ph-arrow-left"></i> กลับสู่หน้าแรก
        </a>
        <h1 class="h3 fw-bold text-dark">ส่งข้อความถึงเรา (Contact Us)</h1>
        <p class="text-secondary">มีข้อสงสัย ต้องการคำปรึกษา หรือสนใจสั่งทำเว็บไซต์ ส่งข้อความหาเราได้ตลอด 24 ชั่วโมง</p>
    </div>

    <div class="contact-card">
        <div class="row g-0">
            <!-- คอลัมน์ซ้าย: ข้อมูลการติดต่อ -->
            <div class="col-lg-5 contact-info-col d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary text-white px-2 py-1">THE SPACE</span>
                        <span class="badge bg-info-subtle text-info px-2 py-1">ITDevTeam</span>
                    </div>
                    <h3 class="h4 fw-bold text-white mb-2">ช่องทางการติดต่อ</h3>
                    <p class="text-secondary small mb-4">ติดต่อทีมงานเพื่อปรึกษาโปรเจกต์ ออกแบบ UX/UI หรือสอบถามปัญหาการใช้งาน</p>

                    <div class="info-item">
                        <div class="info-icon"><i class="ph ph-envelope-simple"></i></div>
                        <div>
                            <div class="small text-secondary">อีเมลติดต่อ</div>
                            <div class="fw-semibold">v890x0@gmail.com</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="ph ph-phone-call"></i></div>
                        <div>
                            <div class="small text-secondary">เบอร์โทรศัพท์</div>
                            <div class="fw-semibold">065-327-6864</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="ph ph-clock"></i></div>
                        <div>
                            <div class="small text-secondary">เวลาทำการ</div>
                            <div class="fw-semibold">จันทร์ - ศุกร์ 09:00 - 18:00 น.</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="ph ph-map-pin"></i></div>
                        <div>
                            <div class="small text-secondary">พื้นที่บริการ</div>
                            <div class="fw-semibold">กรุงเทพฯ และปริมณฑล (บริการทั่วไทย)</div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-top border-secondary">
                    <a href="hok/hok-2/hok-3.html" class="btn btn-outline-info btn-sm w-100">
                        <i class="ph ph-chat-circle-dots me-1"></i> ช่องทาง Social ทั้งหมด (Link in Bio)
                    </a>
                </div>
            </div>

            <!-- คอลัมน์ขวา: แบบฟอร์มส่งข้อความ -->
            <div class="col-lg-7 contact-form-col">
                <h4 class="fw-bold mb-3"><i class="ph ph-paper-plane-tilt text-primary me-2"></i>กรอกข้อความของคุณ</h4>

                <?php if ($notice): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ph ph-check-circle me-1"></i> <?= htmlspecialchars($notice) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ph ph-warning-circle me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="sendMsgForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['contact_csrf_token']) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="sender_name">ชื่อของคุณ / ชื่อองค์กร <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sender_name" name="sender_name" placeholder="เช่น คุณสมศักดิ์ หรือ บจก. เทคโนโลยีไทย" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="sender_contact">เบอร์โทรศัพท์ หรือ อีเมลสำหรับติดต่อกลับ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sender_contact" name="sender_contact" placeholder="เช่น 081-234-5678 หรือ name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="subject">หัวข้อเรื่อง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="เช่น สอบถามคอร์สเรียน, ขอใบเสนอราคาทำเว็บไซต์" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="message">รายละเอียดข้อความ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="พิมพ์รายละเอียดความต้องการหรือข้อความที่ต้องการส่งถึงเรา..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-submit" id="submitBtn">
                            <i class="ph ph-paper-plane-right me-1"></i> ส่งข้อความถึงเราทันที
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
