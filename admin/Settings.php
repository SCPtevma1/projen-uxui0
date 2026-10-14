<?php
session_start();
if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ตั้งค่าระบบ</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<style>
		body { background-color: #f8f9fa; }
		.sidebar { min-height: 100vh; background-color: #1e293b; }
		.sidebar a { color: #94a3b8; text-decoration: none; padding: 12px 20px; display: block; }
		.sidebar a:hover, .sidebar a.active { background-color: #334155; color: #fff; border-radius: 6px; }
		.card-custom { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
	</style>
</head>
<body>
<div class="container-fluid"><div class="row">
	<aside class="col-md-3 col-lg-2 sidebar p-3">
		<a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
		<a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
		<a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
        <a href="manage_services.php"><i class="bi bi-box-seam me-2"></i> จัดการเว็บไซต์</a>
		<a href="manage_footer.php"><i class="bi bi-file-earmark-text me-2"></i> จัดการ Footer</a>
		<a href="Settings.php" class="active"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
	</aside>
	<main class="col-md-9 col-lg-10 p-4">
		<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom"><h3>ตั้งค่าระบบ</h3><a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></div>
		<div class="card card-custom p-4" style="max-width: 800px;">
			<h5 class="mb-4">ข้อมูลเว็บไซต์</h5>
			<form>
				<div class="mb-3"><label class="form-label">ชื่อเว็บไซต์</label><input type="text" class="form-control" value="Projen"></div>
				<div class="mb-3"><label class="form-label">อีเมลติดต่อ</label><input type="email" class="form-control" value="v890x0@gmail.com"></div>
				<div class="mb-3"><label class="form-label">คำอธิบายเว็บไซต์</label><textarea class="form-control" rows="3">ระบบจัดการบริการและสินค้า</textarea></div>
				<div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" role="switch" id="maintenance"><label class="form-check-label" for="maintenance">เปิดโหมดปิดปรับปรุงระบบ</label></div>
				<button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> บันทึกการตั้งค่า</button>
			</form>
		</div>
	</main>
</div></div>
</body>
</html>