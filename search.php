<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';
include_once 'includes/header.php';

$license_plate = isset($_GET['license_plate']) ? trim($_GET['license_plate']) : '';
$vehicle = null;
$violations = [];

if (!empty($license_plate)) {
    $vehicle = getVehicleByLicensePlate($conn, $license_plate);
    
    if ($vehicle) {
        $violations = getViolationsByVehicleId($conn, $vehicle['id']);
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Tra cứu vi phạm giao thông</h2>
        <p>Nhập biển số xe để tra cứu thông tin vi phạm</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="search.php" method="GET" class="row g-3">
                    <div class="col-md-9">
                        <input type="text" class="form-control" id="license_plate" name="license_plate" 
                               placeholder="Nhập biển số xe (VD: 29A-12345)" value="<?php echo htmlspecialchars($license_plate); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Tra cứu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($license_plate)): ?>
    <?php if ($vehicle): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Thông tin phương tiện</h4>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th style="width: 200px;">Biển số xe:</th>
                                <td><strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Chủ phương tiện:</th>
                                <td><?php echo htmlspecialchars($vehicle['owner_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Loại phương tiện:</th>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <?php if (count($violations) > 0): ?>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">Danh sách vi phạm (<?php echo count($violations); ?>)</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Thời gian vi phạm</th>
                                            <th>Địa điểm</th>
                                            <th>Lỗi vi phạm</th>
                                            <th>Số tiền phạt</th>
                                            <th>Trạng thái</th>
                                            <th>Chi tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($violations as $violation): ?>
                                            <tr>
                                                <td><?php echo formatDate($violation['violation_date']); ?></td>
                                                <td><?php echo htmlspecialchars($violation['location']); ?></td>
                                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                                <td><?php echo formatMoney($violation['fine_amount']); ?></td>
                                                <td>
                                                    <?php if ($violation['status'] == 'Paid'): ?>
                                                        <span class="badge bg-success">Đã nộp phạt</span>
                                                    <?php elseif ($violation['status'] == 'Processing'): ?>
                                                        <span class="badge bg-warning">Đang xử lý</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Chưa nộp phạt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="violation_details.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> Xem
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Không tìm thấy vi phạm!</h4>
                        <p>Phương tiện với biển số <strong><?php echo htmlspecialchars($license_plate); ?></strong> không có vi phạm giao thông trong hệ thống.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <h4 class="alert-heading">Không tìm thấy phương tiện!</h4>
            <p>Không tìm thấy thông tin phương tiện với biển số <strong><?php echo htmlspecialchars($license_plate); ?></strong> trong hệ thống.</p>
            <hr>
            <p class="mb-0">Vui lòng kiểm tra lại biển số xe và thử lại.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>