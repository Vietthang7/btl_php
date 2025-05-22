<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';
include_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 text-center mb-5 mt-3">
        <h1>Tra cứu phương tiện vi phạm giao thông</h1>
        <p class="lead">Hệ thống tra cứu thông tin vi phạm giao thông trực tuyến</p>
    </div>
</div>

<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0 text-center">Tra cứu vi phạm</h3>
            </div>
            <div class="card-body">
                <form action="search.php" method="GET">
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">Biển số xe</label>
                        <input type="text" class="form-control form-control-lg" id="license_plate" name="license_plate" placeholder="Nhập biển số xe (VD: 29A-12345)" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Tra cứu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                <h4>Tra cứu nhanh chóng</h4>
                <p>Hệ thống tra cứu thông tin vi phạm giao thông trực tuyến, giúp người dân dễ dàng kiểm tra các lỗi vi phạm.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                <h4>Dữ liệu chính xác</h4>
                <p>Cơ sở dữ liệu được cập nhật liên tục từ các cơ quan chức năng, đảm bảo thông tin chính xác và mới nhất.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h4>An toàn & Bảo mật</h4>
                <p>Thông tin tra cứu được bảo mật, đảm bảo quyền riêng tư của người dân khi sử dụng dịch vụ.</p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>