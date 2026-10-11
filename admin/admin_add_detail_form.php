<?php
// admin_add_detail_form.php
?>
<div class="card mt-4 border shadow-sm rounded-3">
    <div class="card-header bg-dark text-white font-weight-bold">
        🌐 ส่วนข้อมูลเทคนิค & รายละเอียดเชิงลึกของเว็บไซต์ (สำหรับแสดงในหน้าดูข้อมูล)
    </div>
    <div class="card-body bg-light">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label text-secondary font-weight-bold">ประเภทของ UX UI</label>
                <input type="text" name="uxui_type" class="form-control" placeholder="เช่น Dashboard, e-Commerce, Clean Minimal">
            </div>
            <div class="col-md-4">
                <label class="form-label text-secondary font-weight-bold">สไตล์ของ UX UI</label>
                <input type="text" name="uxui_style" class="form-control" placeholder="เช่น Dark Mode, Light Web, Pastel">
            </div>
            <div class="col-md-4">
                <label class="form-label text-secondary font-weight-bold">จำนวนหน้าทั้งหมด</label>
                <input type="number" name="page_count" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-6">
                <label class="form-label text-secondary font-weight-bold">จุดเด่นของงาน</label>
                <textarea name="highlights" class="form-control" rows="3" placeholder="ระบุจุดเด่น คั่นด้วยเครื่องหมายจุลภาค , เช่น โหลดเร็ว, มีอนิเมชัน, คลีน สบายตา"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-secondary font-weight-bold">สิ่งที่จะได้รับในไฟล์</label>
                <textarea name="file_contents" class="form-control" rows="3" placeholder="ระบุสิ่งที่จะได้รับ เช่น ซอร์สโค้ด PHP ทั้งหมด, ฐานข้อมูล .sql, คู่มือการติดตั้ง"></textarea>
            </div>
        </div>
    </div>
</div>
