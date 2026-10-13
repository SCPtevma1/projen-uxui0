<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า</title>
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
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> ภาพรวมระบบ</a>
            <a href="manage_users.php"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php" class="active"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php"><i class="bi bi-box-seam me-2"></i> จัดการเว็บไซต์</a>
            <a href="manage_footer.php">footer</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <h3>รายการสินค้า</h3>
                <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
            <div class="card card-custom p-3">
                <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">สินค้าทั้งหมด</h5><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> เพิ่มสินค้า</button></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th>ราคา</th><th>สต็อก</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                    <tbody>
                        <tr><td>1</td><td>แพ็กเกจเริ่มต้น</td><td>บริการ</td><td>฿990</td><td>ไม่จำกัด</td><td><span class="badge text-bg-success">พร้อมขาย</span></td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                        <tr><td>2</td><td>แพ็กเกจองค์กร</td><td>บริการ</td><td>฿2,490</td><td>ไม่จำกัด</td><td><span class="badge text-bg-success">พร้อมขาย</span></td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                        <tr><td>3</td><td>บริการออกแบบพิเศษ</td><td>บริการเสริม</td><td>฿5,000</td><td>5</td><td><span class="badge text-bg-warning">ใกล้หมด</span></td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                    </tbody>
                </table></div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
