<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy thống kê
$stats = [];

// Tổng số phương tiện
$stmt = $conn->query("SELECT COUNT(*) FROM vehicles");
$stats['vehicles'] = $stmt->fetchColumn();

// Tổng số vi phạm
$stmt = $conn->query("SELECT COUNT(*) FROM violations");
$stats['violations'] = $stmt->fetchColumn();

// Vi phạm chưa nộp phạt
$stmt = $conn->query("SELECT COUNT(*) FROM violations WHERE status = 'Unpaid'");
$stats['unpaid'] = $stmt->fetchColumn();

// Vi phạm đã nộp phạt
$stmt = $conn->query("SELECT COUNT(*) FROM violations WHERE status = 'Paid'");
$stats['paid'] = $stmt->fetchColumn();

// Vi phạm đang xử lý
$stmt = $conn->query("SELECT COUNT(*) FROM violations WHERE status = 'Processing'");
$stats['processing'] = $stmt->fetchColumn();

// Tổng tiền phạt
$stmt = $conn->query("SELECT SUM(fine_amount) FROM violations");
$stats['total_fine'] = $stmt->fetchColumn() ?: 0;

// Tổng tiền đã thu
$stmt = $conn->query("SELECT SUM(fine_amount) FROM violations WHERE status = 'Paid'");
$stats['collected_fine'] = $stmt->fetchColumn() ?: 0;

// Vi phạm mới nhất
$stmt = $conn->query("SELECT v.*, vh.license_plate, vh.owner_name 
FROM violations v 
JOIN vehicles vh ON v.vehicle_id = vh.id 
ORDER BY v.created_at DESC LIMIT 5");
$recent_violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout
$pageTitle = "Trang quản trị";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Bảng điều khiển</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_violation.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Thêm vi phạm mới
            </a>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Tổng phương tiện</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['vehicles']); ?></h2>
                    </div>
                    <i class="fas fa-car fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Tổng vi phạm</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['violations']); ?></h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Đã nộp phạt</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['paid']); ?></h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Chưa nộp phạt</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['unpaid']); ?></h2>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thống kê vi phạm</h5>
            </div>
            <div class="card-body">
                <canvas id="violationChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Thống kê tài chính</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Tổng tiền phạt:</th>
                        <td class="text-end"><?php echo formatMoney($stats['total_fine']); ?></td>
                    </tr>
                    <tr>
                        <th>Đã thu:</th>
                        <td class="text-end text-success"><?php echo formatMoney($stats['collected_fine']); ?></td>
                    </tr>
                    <tr>
                        <th>Còn lại:</th>
                        <td class="text-end text-danger"><?php echo formatMoney($stats['total_fine'] - $stats['collected_fine']); ?></td>
                    </tr>
                    <tr>
                        <th>Tỷ lệ thu:</th>
                        <td class="text-end">
                            <?php 
                            $percentage = ($stats['total_fine'] > 0) ? ($stats['collected_fine'] / $stats['total_fine'] * 100) : 0;
                            echo number_format($percentage, 1) . '%';
                            ?>
                        </td>
                    </tr>
                </table>
                <div class="progress mt-3" style="height: 30px;">
                    <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%">
                        <?php echo number_format($percentage, 1); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Vi phạm mới nhất</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Biển số xe</th>
                        <th>Chủ xe</th>
                        <th>Thời gian vi phạm</th>
                        <th>Loại vi phạm</th>
                        <th>Tiền phạt</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_violations) > 0): ?>
                        <?php foreach ($recent_violations as $violation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                <td><?php echo htmlspecialchars($violation['owner_name']); ?></td>
                                <td><?php echo formatDate($violation['violation_date']); ?></td>
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
                                    <a href="edit_violation.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Không có vi phạm nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-end mt-3">
            <a href="manage_violations.php" class="btn btn-outline-primary">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart cho thống kê vi phạm
    var ctx = document.getElementById('violationChart').getContext('2d');
    var violationChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Đã nộp phạt', 'Đang xử lý', 'Chưa nộp phạt'],
            datasets: [{
                data: [
                    <?php echo $stats['paid']; ?>,
                    <?php echo $stats['processing']; ?>,
                    <?php echo $stats['unpaid']; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include_once 'layout/footer.php'; ?>