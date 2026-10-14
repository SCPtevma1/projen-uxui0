<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = '';
$error = '';
$uploadDirectory = __DIR__ . '/../uploads/services';
$uploadUrlPrefix = 'uploads/services/';

$loadServiceImages = static function (int $serviceId) use ($conn): array {
    $stmt = $conn->prepare(
        'SELECT id, image_path, sort_order FROM service_carousel_images WHERE service_id = ? ORDER BY sort_order, id'
    );
    if (!$stmt) {
        die('Unable to prepare image lookup: ' . $conn->error);
    }
    $stmt->bind_param('i', $serviceId);
    if (!$stmt->execute()) {
        die('Unable to load carousel images: ' . $stmt->error);
    }
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $images;
};

$removeStoredImageFile = static function (string $imagePath) use ($uploadUrlPrefix, $uploadDirectory): bool {
    if (!str_starts_with($imagePath, $uploadUrlPrefix)) {
        return true;
    }
    $filename = basename($imagePath);
    if ($filename === '' || $filename === '.' || $filename === '..') {
        return false;
    }
    $path = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
    return !is_file($path) || unlink($path);
};

$service = [
    'id' => 0,
    'eyebrow' => '',
    'title' => '',
    'description' => '',
    'web_type' => '',
    'tech_tags' => '',
    'image_url' => '',
    'link_url' => '',
    'uxui_type' => '',
    'uxui_style' => '',
    'page_count' => 1,
    'highlights' => '',
    'file_contents' => '',
    'price' => '0.00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $imagesToDelete = $id ? $loadServiceImages((int) $id) : [];
            $stmt = $conn->prepare('DELETE FROM services WHERE id = ?');
            if (!$id || !$stmt) {
                $error = 'ไม่สามารถเตรียมคำสั่งลบข้อมูลได้: ' . $conn->error;
            } else {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $notice = 'ลบรายการเว็บไซต์แล้ว';
                    foreach ($imagesToDelete as $image) {
                        if (!$removeStoredImageFile($image['image_path'])) {
                            $error = 'ลบรายการแล้ว แต่ไม่สามารถลบไฟล์รูปภาพ ' . basename($image['image_path']) . ' ได้';
                            break;
                        }
                    }
                } else {
                    $error = 'ลบรายการไม่สำเร็จ: ' . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'save') {
            $postText = static function (string $key): string {
                $value = $_POST[$key] ?? '';
                return is_string($value) ? trim($value) : '';
            };
            $postedTags = $_POST['tech_tags'] ?? [];
            $postedTags = is_array($postedTags) ? array_filter($postedTags, 'is_string') : [];
            $id = (int) ($_POST['id'] ?? 0);
            $service = [
                'id' => $id,
                'eyebrow' => $postText('eyebrow'),
                'title' => $postText('title'),
                'description' => $postText('description'),
                'web_type' => $postText('web_type'),
                'tech_tags' => implode(', ', array_map('trim', $postedTags)),
                'image_url' => $postText('image_url'),
                'link_url' => $postText('link_url'),
                'uxui_type' => $postText('uxui_type'),
                'uxui_style' => $postText('uxui_style'),
                'page_count' => max(1, (int) ($_POST['page_count'] ?? 1)),
                'highlights' => $postText('highlights'),
                'file_contents' => $postText('file_contents'),
                'price' => $postText('price'),
            ];
            $price = filter_var($service['price'], FILTER_VALIDATE_FLOAT);
            $existingImages = $id > 0 ? $loadServiceImages($id) : [];
            $removeIds = $_POST['remove_gallery_images'] ?? [];
            $removeIds = is_array($removeIds) ? array_values(array_unique(array_filter(
                array_map(static fn($value) => filter_var($value, FILTER_VALIDATE_INT), $removeIds)
            ))) : [];
            $existingImageIds = array_map(static fn($image) => (int) $image['id'], $existingImages);
            $removeIdsValid = count(array_diff($removeIds, $existingImageIds)) === 0;
            $fileErrors = [];
            $uploadFiles = [];
            $files = $_FILES['carousel_images'] ?? null;
            if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
                if (count($files['name']) > 10) {
                    $fileErrors[] = 'เลือกอัปโหลดได้ไม่เกิน 10 รูปต่อครั้ง';
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo === false) {
                    $fileErrors[] = 'ไม่สามารถตรวจสอบชนิดไฟล์รูปภาพได้';
                } else {
                    foreach ($files['name'] as $index => $originalName) {
                        $uploadError = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                        if ($uploadError === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        if ($uploadError !== UPLOAD_ERR_OK) {
                            $fileErrors[] = 'อัปโหลดรูป ' . (string) $originalName . ' ไม่สำเร็จ (รหัส ' . (int) $uploadError . ')';
                            continue;
                        }
                        $temporaryPath = $files['tmp_name'][$index] ?? '';
                        $fileSize = (int) ($files['size'][$index] ?? 0);
                        $mimeType = is_uploaded_file($temporaryPath) ? finfo_file($finfo, $temporaryPath) : false;
                        $extensionByMime = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp',
                        ];
                        if ($fileSize < 1 || $fileSize > 5 * 1024 * 1024) {
                            $fileErrors[] = 'รูป ' . (string) $originalName . ' ต้องมีขนาดไม่เกิน 5 MB';
                        } elseif (!is_string($mimeType) || !isset($extensionByMime[$mimeType])) {
                            $fileErrors[] = 'รูป ' . (string) $originalName . ' ต้องเป็น JPEG, PNG, GIF หรือ WebP';
                        } else {
                            $uploadFiles[] = [
                                'temporary_path' => $temporaryPath,
                                'extension' => $extensionByMime[$mimeType],
                            ];
                        }
                    }
                    finfo_close($finfo);
                }
            } elseif (isset($_FILES['carousel_images']) && ($_FILES['carousel_images']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $fileErrors[] = 'รูปแบบข้อมูลไฟล์อัปโหลดไม่ถูกต้อง';
            }
            if (count($existingImages) - count($removeIds) + count($uploadFiles) > 10) {
                $fileErrors[] = 'เว็บไซต์หนึ่งรายการมีรูปใน Carousel ได้ไม่เกิน 10 รูป';
            }
            if (!$removeIdsValid) {
                $fileErrors[] = 'พบรายการรูปภาพที่ต้องการลบไม่ถูกต้อง';
            }

            $imagesAfterRemoval = array_values(array_filter(
                $existingImages,
                static fn($image) => !in_array((int) $image['id'], $removeIds, true)
            ));
            $removedImagePaths = array_map(static fn($image) => $image['image_path'], array_filter(
                $existingImages,
                static fn($image) => in_array((int) $image['id'], $removeIds, true)
            ));
            $imageScheme = strtolower((string) parse_url($service['image_url'], PHP_URL_SCHEME));
            $localImage = str_starts_with($service['image_url'], $uploadUrlPrefix) &&
                is_file($uploadDirectory . DIRECTORY_SEPARATOR . basename($service['image_url']));
            $imageBeingRemoved = in_array($service['image_url'], $removedImagePaths, true);
            $validRemoteImage = filter_var($service['image_url'], FILTER_VALIDATE_URL) &&
                in_array($imageScheme, ['http', 'https'], true);
            $validLocalImage = $localImage && !$imageBeingRemoved;
            $validImage = $validLocalImage || $validRemoteImage || $uploadFiles || $imagesAfterRemoval;
            $linkParts = parse_url($service['link_url']);
            $linkScheme = is_array($linkParts) ? strtolower((string) ($linkParts['scheme'] ?? '')) : '';
            $validLink = $service['link_url'] !== '' && is_array($linkParts) &&
                !preg_match('/[\x00-\x20]/', $service['link_url']) &&
                ($linkScheme === '' || in_array($linkScheme, ['http', 'https', 'mailto', 'tel'], true));

            if ($service['title'] === '' || $service['description'] === '' ||
                $service['web_type'] === '' || !$validImage || !$validLink || $fileErrors ||
                $price === false || $price < 0) {
                $error = $fileErrors
                    ? implode(' ', $fileErrors)
                    : 'กรุณากรอกข้อมูลที่จำเป็น ใช้ URL รูปภาพ HTTP/HTTPS (หรืออัปโหลดรูป) และลิงก์ที่ปลอดภัย พร้อมระบุราคาเป็นตัวเลขตั้งแต่ 0 ขึ้นไป';
            } else {
                $movedImagePaths = [];
                if ($uploadFiles && !is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $error = 'สร้างโฟลเดอร์สำหรับจัดเก็บรูปภาพไม่สำเร็จ';
                }
                foreach ($uploadFiles as $uploadFile) {
                    if ($error !== '') {
                        break;
                    }
                    $filename = bin2hex(random_bytes(16)) . '.' . $uploadFile['extension'];
                    $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                    if (!move_uploaded_file($uploadFile['temporary_path'], $targetPath)) {
                        $error = 'บันทึกไฟล์รูปภาพไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ uploads/services';
                        break;
                    }
                    $movedImagePaths[] = $uploadUrlPrefix . $filename;
                }
                if ($service['image_url'] === '' || $imageBeingRemoved ||
                    (!$validLocalImage && !$validRemoteImage)) {
                    $service['image_url'] = $movedImagePaths[0] ??
                        ($imagesAfterRemoval[0]['image_path'] ?? '');
                }
                if ($error !== '') {
                    foreach ($movedImagePaths as $imagePath) {
                        $removeStoredImageFile($imagePath);
                    }
                } else {
                    $conn->begin_transaction();
                    $stmt = null;
                    if ($id > 0) {
                        $stmt = $conn->prepare(
                            'UPDATE services SET eyebrow=?, title=?, description=?, web_type=?, tech_tags=?, image_url=?, link_url=?, uxui_type=?, uxui_style=?, page_count=?, highlights=?, file_contents=?, price=? WHERE id=?'
                        );
                        if ($stmt) {
                            $stmt->bind_param(
                                'sssssssssissdi',
                                $service['eyebrow'],
                                $service['title'],
                                $service['description'],
                                $service['web_type'],
                                $service['tech_tags'],
                                $service['image_url'],
                                $service['link_url'],
                                $service['uxui_type'],
                                $service['uxui_style'],
                                $service['page_count'],
                                $service['highlights'],
                                $service['file_contents'],
                                $price,
                                $id
                            );
                        }
                    } else {
                        $stmt = $conn->prepare(
                            'INSERT INTO services (eyebrow, title, description, web_type, tech_tags, image_url, link_url, uxui_type, uxui_style, page_count, highlights, file_contents, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        if ($stmt) {
                            $stmt->bind_param(
                                'sssssssssissd',
                                $service['eyebrow'],
                                $service['title'],
                                $service['description'],
                                $service['web_type'],
                                $service['tech_tags'],
                                $service['image_url'],
                                $service['link_url'],
                                $service['uxui_type'],
                                $service['uxui_style'],
                                $service['page_count'],
                                $service['highlights'],
                                $service['file_contents'],
                                $price
                            );
                        }
                    }

                    if (!$stmt) {
                        $error = 'เตรียมคำสั่งบันทึกข้อมูลไม่สำเร็จ: ' . $conn->error;
                        $conn->rollback();
                        foreach ($movedImagePaths as $imagePath) {
                            $removeStoredImageFile($imagePath);
                        }
                    } else {
                        if ($stmt->execute()) {
                            $savedServiceId = $id > 0 ? $id : $conn->insert_id;
                            $imageError = false;
                            if ($removeIds) {
                                $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
                                $deleteImages = $conn->prepare(
                                    "DELETE FROM service_carousel_images WHERE service_id = ? AND id IN ($placeholders)"
                                );
                                if (!$deleteImages) {
                                    $error = 'เตรียมลบรูปภาพเดิมไม่สำเร็จ: ' . $conn->error;
                                    $imageError = true;
                                } else {
                                    $types = 'i' . str_repeat('i', count($removeIds));
                                    $params = array_merge([$savedServiceId], $removeIds);
                                    $deleteImages->bind_param($types, ...$params);
                                    if (!$deleteImages->execute()) {
                                        $error = 'ลบรูปภาพเดิมไม่สำเร็จ: ' . $deleteImages->error;
                                        $imageError = true;
                                    }
                                    $deleteImages->close();
                                }
                            }
                            if (!$imageError && $movedImagePaths) {
                                $maxOrder = count($existingImages);
                                foreach ($movedImagePaths as $offset => $imagePath) {
                                    $insertImage = $conn->prepare(
                                        'INSERT INTO service_carousel_images (service_id, image_path, sort_order) VALUES (?, ?, ?)'
                                    );
                                    if (!$insertImage) {
                                        $error = 'เตรียมบันทึกรูปภาพไม่สำเร็จ: ' . $conn->error;
                                        $imageError = true;
                                        break;
                                    }
                                    $sortOrder = $maxOrder + $offset;
                                    $insertImage->bind_param('isi', $savedServiceId, $imagePath, $sortOrder);
                                    if (!$insertImage->execute()) {
                                        $error = 'บันทึกรูปภาพไม่สำเร็จ: ' . $insertImage->error;
                                        $imageError = true;
                                    }
                                    $insertImage->close();
                                    if ($imageError) {
                                        break;
                                    }
                                }
                            }
                            if ($imageError) {
                                $conn->rollback();
                                foreach ($movedImagePaths as $imagePath) {
                                    $removeStoredImageFile($imagePath);
                                }
                            } elseif ($conn->commit()) {
                                foreach ($existingImages as $image) {
                                    if (in_array((int) $image['id'], $removeIds, true) &&
                                        !$removeStoredImageFile($image['image_path'])) {
                                        $error = 'บันทึกข้อมูลแล้ว แต่ไม่สามารถลบไฟล์รูปภาพเดิมได้';
                                        break;
                                    }
                                }
                                if ($error === '') {
                                    header('Location: manage_services.php?saved=1');
                                    exit;
                                }
                            } else {
                                $error = 'ยืนยันการบันทึกข้อมูลไม่สำเร็จ: ' . $conn->error;
                                $conn->rollback();
                                foreach ($movedImagePaths as $imagePath) {
                                    $removeStoredImageFile($imagePath);
                                }
                            }
                        } else {
                            $error = 'บันทึกข้อมูลไม่สำเร็จ: ' . $stmt->error;
                            $conn->rollback();
                            foreach ($movedImagePaths as $imagePath) {
                                $removeStoredImageFile($imagePath);
                            }
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    $stmt = $conn->prepare('SELECT * FROM services WHERE id = ?');
    if (!$id || !$stmt) {
        $error = 'ไม่พบรายการเว็บไซต์ที่ต้องการแก้ไข';
    } else {
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $error = 'อ่านข้อมูลเว็บไซต์ไม่สำเร็จ: ' . $stmt->error;
        } else {
            $result = $stmt->get_result();
            $loadedService = $result->fetch_assoc();
            if ($loadedService) {
                $service = array_merge($service, $loadedService);
            } else {
                $error = 'ไม่พบรายการเว็บไซต์ที่ต้องการแก้ไข';
            }
        }
        $stmt->close();
    }
}

if (isset($_GET['saved'])) {
    $notice = 'บันทึกข้อมูลเว็บไซต์เรียบร้อยแล้ว';
}

$services = $conn->query('SELECT id, title, description, image_url, link_url, price FROM services ORDER BY id DESC');
if (!$services) {
    $error = 'โหลดรายการเว็บไซต์ไม่สำเร็จ: ' . $conn->error;
}
$selectedTags = array_map('trim', explode(',', (string) $service['tech_tags']));
$tagOptions = ['PHP', 'MySQL', 'HTML5/CSS3', 'XAMPP', 'JavaScript', 'Bootstrap'];
$galleryImages = (int) $service['id'] > 0 ? $loadServiceImages((int) $service['id']) : [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการเว็บไซต์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; color: #172033; }
        .sidebar { min-height: 100vh; background: #1e293b; }
        .sidebar a { color: #cbd5e1; display: block; padding: 12px 16px; text-decoration: none; border-radius: 8px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .panel { border: 0; border-radius: 16px; box-shadow: 0 8px 24px #0f172a0d; }
        .section-label { color: #64748b; font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        @media (max-width: 767px) { .sidebar { min-height: auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php" class="active"><i class="bi bi-window-stack me-2"></i> จัดการเว็บไซต์</a>
            <a href="manage_comments.php"><i class="bi bi-chat-left-text me-2"></i> จัดการความคิดเห็น</a>
            <a href="manage_footer.php"><i class="bi bi-file-earmark-text me-2"></i> จัดการ Footer</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>
        <main class="col-md-9 col-lg-10 p-3 p-lg-4">
            <header class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div><h1 class="h3 mb-1">จัดการเว็บไซต์</h1><p class="text-secondary mb-0">เพิ่มและแก้ไขเนื้อหาที่แสดงบนหน้าเว็บไซต์</p></div>
                <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </header>

            <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <section class="panel card p-3 p-lg-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div><span class="section-label">Service content</span><h2 class="h4 mt-1"><?= $service['id'] ? 'แก้ไขข้อมูลเว็บไซต์' : 'เพิ่มเว็บไซต์ใหม่' ?></h2></div>
                    <?php if ($service['id']): ?><a href="manage_services.php" class="btn btn-outline-secondary btn-sm">ยกเลิกการแก้ไข</a><?php endif; ?>
                </div>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                    <div class="col-md-4"><label class="form-label">คำกำกับ</label><input class="form-control" name="eyebrow" value="<?= htmlspecialchars($service['eyebrow']) ?>" placeholder="เช่น Standard / แนะนำ"></div>
                    <div class="col-md-5"><label class="form-label">ชื่อเว็บไซต์ <span class="text-danger">*</span></label><input class="form-control" name="title" required value="<?= htmlspecialchars($service['title']) ?>"></div>
                    <div class="col-md-3"><label class="form-label">ราคา (บาท) <span class="text-danger">*</span></label><input class="form-control" type="number" name="price" min="0" step="0.01" required value="<?= htmlspecialchars((string) $service['price']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">ประเภทเว็บไซต์ <span class="text-danger">*</span></label>
                        <select class="form-select" name="web_type" required>
                            <option value="">เลือกประเภท</option>
                            <?php foreach (['เว็บไซต์บริษัท / องค์กร', 'ระบบร้านค้าออนไลน์ (E-Commerce)', 'เว็บแอปพลิเคชัน / ระบบภายใน (POS)', 'บล็อก / เว็บไซต์ข่าวสาร', 'Landing Page / Portfolio'] as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= $service['web_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">URL รูปภาพปก (ไม่บังคับ)</label><input class="form-control" type="text" name="image_url" value="<?= htmlspecialchars($service['image_url']) ?>" placeholder="ใช้ URL หรืออัปโหลดรูปด้านล่าง"></div>
                    <div class="col-12">
                        <label class="form-label" for="carousel-images">รูปภาพ Carousel (เลือกได้หลายไฟล์)</label>
                        <input class="form-control" type="file" id="carousel-images" name="carousel_images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <div class="form-text">รองรับ JPEG, PNG, GIF, WebP ขนาดไม่เกิน 5 MB ต่อรูป และไม่เกิน 10 รูปต่อเว็บไซต์</div>
                        <?php if ($galleryImages): ?>
                            <div class="row g-2 mt-2">
                                <?php foreach ($galleryImages as $image): ?>
                                    <div class="col-6 col-md-3 col-xl-2">
                                        <label class="border rounded p-2 d-block h-100">
                                            <img src="../<?= htmlspecialchars($image['image_path']) ?>" alt="รูปใน Carousel" class="w-100 rounded object-fit-cover" style="height: 90px">
                                            <span class="form-check mt-2 mb-0">
                                                <input class="form-check-input" type="checkbox" name="remove_gallery_images[]" value="<?= (int) $image['id'] ?>">
                                                <span class="form-check-label small">ลบรูปนี้</span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12"><label class="form-label">คำอธิบายสั้น <span class="text-danger">*</span></label><textarea class="form-control" name="description" rows="2" required><?= htmlspecialchars($service['description']) ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">ประเภท UX/UI</label><input class="form-control" name="uxui_type" value="<?= htmlspecialchars($service['uxui_type']) ?>" placeholder="เช่น Dashboard, E-Commerce"></div>
                    <div class="col-md-4"><label class="form-label">สไตล์ UX/UI</label><input class="form-control" name="uxui_style" value="<?= htmlspecialchars($service['uxui_style']) ?>" placeholder="เช่น Minimal, Modern"></div>
                    <div class="col-md-2"><label class="form-label">จำนวนหน้า</label><input class="form-control" type="number" name="page_count" min="1" value="<?= (int) $service['page_count'] ?>"></div>
                    <div class="col-12"><label class="form-label">เครื่องมือ/เทคโนโลยี</label><div class="d-flex flex-wrap gap-3">
                        <?php foreach ($tagOptions as $index => $tag): $tagId = 'service-tag-' . $index; ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="tech_tags[]" value="<?= htmlspecialchars($tag) ?>" id="<?= $tagId ?>" <?= in_array($tag, $selectedTags, true) ? 'checked' : '' ?>><label class="form-check-label" for="<?= $tagId ?>"><?= htmlspecialchars($tag) ?></label></div>
                        <?php endforeach; ?>
                    </div></div>
                    <div class="col-12"><label class="form-label">📌 จุดเด่นของงาน</label><textarea class="form-control" name="highlights" rows="4" placeholder="เพิ่มจุดเด่นหนึ่งรายการต่อหนึ่งบรรทัด"><?= htmlspecialchars($service['highlights']) ?></textarea></div>
                    <div class="col-12"><label class="form-label">📦 สิ่งที่จะได้รับในไฟล์</label><textarea class="form-control" name="file_contents" rows="4" placeholder="ระบุไฟล์ ซอร์สโค้ด และเอกสารที่ลูกค้าจะได้รับ"><?= htmlspecialchars($service['file_contents']) ?></textarea></div>
                    <div class="col-md-8"><label class="form-label">ลิงก์/ช่องทางสั่งซื้อ <span class="text-danger">*</span></label><input class="form-control" name="link_url" required value="<?= htmlspecialchars($service['link_url']) ?>" placeholder="เช่น https://line.me/... หรือหน้าเพจติดต่อ"></div>
                    <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-floppy me-1"></i><?= $service['id'] ? 'บันทึกการแก้ไข' : 'เพิ่มเว็บไซต์' ?></button></div>
                </form>
            </section>

            <section class="panel card p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4 mb-0">เว็บไซต์ทั้งหมด</h2><span class="badge text-bg-light"><?= $services ? $services->num_rows : 0 ?> รายการ</span></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>เว็บไซต์</th><th>ราคา</th><th>ลิงก์</th><th class="text-end">จัดการ</th></tr></thead>
                        <tbody>
                        <?php if ($services && $services->num_rows): while ($row = $services->fetch_assoc()): ?>
                            <tr>
                                <td><div class="d-flex align-items-center gap-3"><img src="<?= htmlspecialchars($row['image_url']) ?>" alt="" width="64" height="48" class="rounded object-fit-cover"><div><strong><?= htmlspecialchars($row['title']) ?></strong><div class="small text-secondary"><?= htmlspecialchars($row['description']) ?></div></div></div></td>
                                <td class="fw-semibold">฿<?= number_format((float) $row['price'], 2) ?></td>
                                <td><a href="detail.php?id=<?= (int) $row['id'] ?>">ดูรายละเอียด</a></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $row['id'] ?>">แก้ไข</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันลบเว็บไซต์นี้?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">ยังไม่มีเว็บไซต์ เพิ่มรายการแรกได้จากแบบฟอร์มด้านบน</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
