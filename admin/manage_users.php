<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน</title>
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
            <a href="manage_users.php" class="active"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</a>
            <a href="manage_products.php"><i class="bi bi-box-seam me-2"></i> รายการสินค้า</a>
            <a href="manage_services.php"><i class="bi bi-box-seam me-2"></i> จัดการเว็บไซต์</a>
            <a href="Settings.php"><i class="bi bi-gear me-2"></i> ตั้งค่าระบบ</a>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                <h3>จัดการผู้ใช้งาน</h3>
                <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
            <div class="card card-custom p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">รายชื่อผู้ใช้งาน</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus me-1"></i> เพิ่มผู้ใช้งาน</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>#</th><th>ชื่อผู้ใช้งาน</th><th>อีเมล</th><th>สิทธิ์</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
                        <tbody>
                            <tr><td>1</td><td>v890x0</td><td>v890x0@gmail.com</td><td><span class="badge text-bg-primary">Admin</span></td><td><span class="badge text-bg-success">ใช้งาน</span></td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                            <tr><td>2</td><td>user_demo</td><td>user_demo@gmail.com</td><td><span class="badge text-bg-secondary">ผู้ใช้งาน</span></td><td><span class="badge text-bg-warning">รอตรวจสอบ</span></td><td><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">เพิ่มผู้ใช้งาน</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form><div class="modal-body"><label class="form-label">ชื่อผู้ใช้งาน</label><input class="form-control mb-3" required><label class="form-label">อีเมล</label><input type="email" class="form-control mb-3" required><label class="form-label">สิทธิ์</label><select class="form-select"><option>ผู้ใช้งาน</option><option>Admin</option></select></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary">บันทึก</button></div></form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
