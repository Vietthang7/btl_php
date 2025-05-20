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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
    <div>
        <h1 class="h2 mb-0">Bảng điều khiển</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bảng điều khiển</li>
            </ol>
        </nav>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_violation.php" class="btn btn-sm btn-primary d-flex align-items-center">
                <i class="fas fa-plus-circle me-2"></i> Thêm vi phạm mới
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                <i class="fas fa-file-export me-2"></i> Xuất báo cáo
            </button>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-primary fw-bold">Phương tiện</div>
                    <div class="icon-box bg-primary bg-opacity-10">
                        <i class="fas fa-car fa-lg text-primary"></i>
                    </div>
                </div>
                <h2 class="mb-1 fw-bold"><?php echo number_format($stats['vehicles']); ?></h2>
                <div class="text-muted small">Tổng số phương tiện đã đăng ký</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-start border-danger border-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-danger fw-bold">Vi phạm</div>
                    <div class="icon-box bg-danger bg-opacity-10">
                        <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
                    </div>
                </div>
                <h2 class="mb-1 fw-bold"><?php echo number_format($stats['violations']); ?></h2>
                <div class="text-muted small">Tổng số vi phạm đã ghi nhận</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-start border-success border-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-success fw-bold">Đã xử lý</div>
                    <div class="icon-box bg-success bg-opacity-10">
                        <i class="fas fa-check-circle fa-lg text-success"></i>
                    </div>
                </div>
                <h2 class="mb-1 fw-bold"><?php echo number_format($stats['paid']); ?></h2>
                <div class="text-muted small">Số vi phạm đã hoàn thành xử lý</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-start border-warning border-4 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-warning fw-bold">Chưa xử lý</div>
                    <div class="icon-box bg-warning bg-opacity-10">
                        <i class="fas fa-clock fa-lg text-warning"></i>
                    </div>
                </div>
                <h2 class="mb-1 fw-bold"><?php echo number_format($stats['unpaid']); ?></h2>
                <div class="text-muted small">Số vi phạm đang chờ xử lý</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-chart-line me-2"></i>Thống kê vi phạm
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownTimeRange" data-bs-toggle="dropdown">
                        30 ngày qua
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownTimeRange">
                        <li><a class="dropdown-item" href="#">7 ngày qua</a></li>
                        <li><a class="dropdown-item active" href="#">30 ngày qua</a></li>
                        <li><a class="dropdown-item" href="#">Quý này</a></li>
                        <li><a class="dropdown-item" href="#">Năm nay</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="violationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0 text-success">
                    <i class="fas fa-money-bill-wave me-2"></i>Thống kê tài chính
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <table class="table table-borderless">
                        <tr>
                            <th class="text-muted ps-0">Tổng tiền phạt:</th>
                            <td class="text-end fw-bold"><?php echo formatMoney($stats['total_fine']); ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted ps-0">Đã thu:</th>
                            <td class="text-end text-success fw-bold"><?php echo formatMoney($stats['collected_fine']); ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted ps-0">Còn lại:</th>
                            <td class="text-end text-danger fw-bold"><?php echo formatMoney($stats['total_fine'] - $stats['collected_fine']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <?php 
                $percentage = ($stats['total_fine'] > 0) ? ($stats['collected_fine'] / $stats['total_fine'] * 100) : 0;
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Tiến độ thu phạt</span>
                        <span class="fw-bold"><?php echo number_format($percentage, 1); ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%; border-radius: 5px;"></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="financialChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-danger">
            <i class="fas fa-list-alt me-2"></i>Vi phạm mới nhất
        </h5>
        <a href="manage_violations.php" class="btn btn-sm btn-outline-primary">
            Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Biển số xe</th>
                        <th>Chủ xe</th>
                        <th>Thời gian vi phạm</th>
                        <th>Loại vi phạm</th>
                        <th>Tiền phạt</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_violations) > 0): ?>
                        <?php foreach ($recent_violations as $violation): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                <td><?php echo htmlspecialchars($violation['owner_name']); ?></td>
                                <td><?php echo formatDate($violation['violation_date']); ?></td>
                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                <td class="fw-bold"><?php echo formatMoney($violation['fine_amount']); ?></td>
                                <td>
                                    <?php if ($violation['status'] == 'Paid'): ?>
                                        <span class="badge bg-success">Đã nộp phạt</span>
                                    <?php elseif ($violation['status'] == 'Processing'): ?>
                                        <span class="badge bg-warning">Đang xử lý</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Chưa nộp phạt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="edit_violation.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_violation.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">Không có vi phạm nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart cho thống kê vi phạm
    var ctx1 = document.getElementById('violationsChart').getContext('2d');
    var violationsChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['1/5', '2/5', '3/5', '4/5', '5/5', '6/5', '7/5', '8/5', '9/5', '10/5', '11/5', '12/5', '13/5', '14/5'],
            datasets: [{
                label: 'Vi phạm mới',
                data: [5, 7, 4, 9, 6, 3, 8, 10, 6, 5, 7, 9, 8, 12],
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                borderColor: 'rgba(231, 76, 60, 1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(231, 76, 60, 1)',
                pointRadius: 4,
                tension: 0.3,
                fill: true
            }, {
                label: 'Đã xử lý',
                data: [2, 4, 3, 5, 4, 2, 5, 7, 4, 3, 5, 6, 7, 9],
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(46, 204, 113, 1)',
                pointRadius: 4,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 10,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    padding: 10,
                    bodySpacing: 5,
                    usePointStyle: true
                }
            }
        }
    });
    
    // Chart cho thống kê tài chính (doughnut)
    var ctx2 = document.getElementById('financialChart').getContext('2d');
    var financialChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Đã thu', 'Chưa thu'],
            datasets: [{
                data: [
                    <?php echo $stats['collected_fine']; ?>,
                    <?php echo $stats['total_fine'] - $stats['collected_fine']; ?>
                ],
                backgroundColor: [
                    'rgba(46, 204, 113, 0.8)',
                    'rgba(231, 76, 60, 0.8)'
                ],
                borderColor: [
                    'rgba(46, 204, 113, 1)',
                    'rgba(231, 76, 60, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value.toLocaleString('vi-VN')} đ (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include_once 'layout/footer.php'; ?>