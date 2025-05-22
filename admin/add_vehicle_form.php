<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

$pageTitle = "Thêm phương tiện mới";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Thêm phương tiện mới</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_vehicles.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="card">
    <div class="card-body">
        <form action="add_vehicle.php" method="POST">
            <div class="mb-3">
                <label for="license_plate" class="form-label">Biển số xe <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="license_plate" name="license_plate" required placeholder="Nhập biển số xe (VD: 29A-12345)">
            </div>
            <div class="mb-3">
                <label for="owner_name" class="form-label">Chủ phương tiện <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="owner_name" name="owner_name" required placeholder="Nhập tên chủ phương tiện">
            </div>
            <div class="mb-3">
                <label for="vehicle_type" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                    <option value="">-- Chọn loại phương tiện --</option>
                    <option value="Car">Ô tô</option>
                    <option value="Motorcycle">Xe máy</option>
                    <option value="Truck">Xe tải</option>
                    <option value="Bus">Xe khách</option>
                    <option value="Other">Khác</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Thêm phương tiện</button>
            <a href="manage_vehicles.php" class="btn btn-secondary ms-2">Hủy</a>
        </form>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>