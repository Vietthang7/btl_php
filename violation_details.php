<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';
include_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$violation_id = (int)$_GET['id'];
$violation = getViolationById($conn, $violation_id);

if (!$violation) {
    header('Location: index.php');
    exit;
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="search.php?license_plate=<?php echo urlencode($violation['license_plate']); ?>">Tra cứu</a></li>
                <li class="breadcrumb-item active">Chi tiết vi phạm</li>
            </ol>
        </nav>
        <h2>Chi tiết vi phạm giao thông</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Thông tin phương tiện</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 200px;">Biển số xe:</th>
                                <td><strong><?php echo htmlspecialchars($violation['license_plate']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Chủ phương tiện:</th>
                                <td><?php echo htmlspecialchars($violation['owner_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Loại phương tiện:</th>
                                <td><?php echo htmlspecialchars($violation['vehicle_type']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert <?php echo $violation['status'] == 'Paid' ? 'alert-success' : ($violation['status'] == 'Processing' ? 'alert-warning' : 'alert-danger'); ?>">
                            <h5 class="alert-heading">Trạng thái xử lý:</h5>
                            <p class="mb-0 h4">
                                <?php if ($violation['status'] == 'Paid'): ?>
                                    <i class="fas fa-check-circle"></i> Đã nộp phạt
                                <?php elseif ($violation['status'] == 'Processing'): ?>
                                    <i class="fas fa-sync"></i> Đang xử lý
                                <?php else: ?>
                                    <i class="fas fa-exclamation-circle"></i> Chưa nộp phạt
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Thông tin vi phạm</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 200px;">Thời gian vi phạm:</th>
                                <td><?php echo formatDate($violation['violation_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Địa điểm:</th>
                                <td><?php echo htmlspecialchars($violation['location']); ?></td>
                            </tr>
                            <tr>
                                <th>Lỗi vi phạm:</th>
                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Số tiền phạt:</th>
                                <td><strong class="text-danger"><?php echo formatMoney($violation['fine_amount']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Mô tả chi tiết</h5>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($violation['description'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Hướng dẫn nộp phạt</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Nộp trực tiếp tại cơ quan CSGT</h5>
                        <ul>
                            <li>Mang theo giấy tờ tùy thân và giấy tờ xe</li>
                            <li>Thời gian làm việc: 8h00 - 17h00 các ngày trong tuần (trừ ngày lễ)</li>
                            <li>Địa chỉ: Phòng CSGT - Công an tỉnh/thành phố</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Nộp phạt trực tuyến qua cổng dịch vụ công</h5>
                        <ul>
                            <li>Truy cập website: <a href="https://dichvucong.gov.vn" target="_blank">https://dichvucong.gov.vn</a></li>
                            <li>Đăng nhập và thực hiện theo hướng dẫn</li>
                            <li>Mã vi phạm: <strong>VP<?php echo str_pad($violation['id'], 8, '0', STR_PAD_LEFT); ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <a href="search.php?license_plate=<?php echo urlencode($violation['license_plate']); ?>" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <a href="javascript:window.print();" class="btn btn-primary">
                <i class="fas fa-print"></i> In thông tin vi phạm
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>