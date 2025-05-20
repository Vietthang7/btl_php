<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy số liệu thống kê
// Vi phạm theo trạng thái
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM violations GROUP BY status");
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vi phạm theo loại phương tiện
$stmt = $conn->query("SELECT v.vehicle_type, COUNT(*) as count FROM violations vio 
                    JOIN vehicles v ON vio.vehicle_id = v.id 
                    GROUP BY v.vehicle_type");
$vehicleTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vi phạm theo thời gian (6 tháng gần đây)
$stmt = $conn->query("SELECT DATE_FORMAT(violation_date, '%Y-%m') as month, COUNT(*) as count 
                    FROM violations 
                    WHERE violation_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(violation_date, '%Y-%m')
                    ORDER BY month ASC");
$timeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vi phạm theo loại vi phạm
$stmt = $conn->query("SELECT violation_type, COUNT(*) as count FROM violations GROUP BY violation_type ORDER BY count DESC LIMIT 10");
$violationTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout
$pageTitle = "Báo cáo thống kê";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar text-primary me-2"></i>Báo cáo thống kê</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> In báo cáo
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                <i class="fas fa-download me-1"></i> Xuất PDF
            </button>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-calendar me-1"></i> Thời gian
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Tuần này</a></li>
                <li><a class="dropdown-item" href="#">Tháng này</a></li>
                <li><a class="dropdown-item active" href="#">6 tháng gần đây</a></li>
                <li><a class="dropdown-item" href="#">Năm nay</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-primary"><i class="fas fa-chart-pie me-2"></i>Vi phạm theo trạng thái</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-success"><i class="fas fa-car me-2"></i>Vi phạm theo loại phương tiện</h5>
            </div>
            <div class="card-body">
                <canvas id="vehicleTypeChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-primary"><i class="fas fa-chart-line me-2"></i>Vi phạm theo thời gian (6 tháng gần đây)</h5>
            </div>
            <div class="card-body">
                <canvas id="timeChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Top loại vi phạm</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Loại vi phạm</th>
                                <th>Số lượng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violationTypeData as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['violation_type']); ?></td>
                                    <td class="fw-bold"><?php echo number_format($type['count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0 text-primary"><i class="fas fa-table me-2"></i>Tổng hợp báo cáo</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Chỉ số</th>
                        <th>Dữ liệu</th>
                        <th>Tỷ lệ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Tính tổng số vi phạm
                    $totalViolations = 0;
                    foreach ($statusData as $status) {
                        $totalViolations += $status['count'];
                    }
                    
                    // Hiển thị dữ liệu
                    foreach ($statusData as $status): 
                        $percentage = ($totalViolations > 0) ? ($status['count'] / $totalViolations * 100) : 0;
                        $statusText = '';
                        $badgeClass = '';
                        
                        switch ($status['status']) {
                            case 'Paid':
                                $statusText = 'Đã nộp phạt';
                                $badgeClass = 'bg-success';
                                break;
                            case 'Unpaid':
                                $statusText = 'Chưa nộp phạt';
                                $badgeClass = 'bg-danger';
                                break;
                            case 'Processing':
                                $statusText = 'Đang xử lý';
                                $badgeClass = 'bg-warning';
                                break;
                            default:
                                $statusText = $status['status'];
                                $badgeClass = 'bg-secondary';
                        }
                    ?>
                        <tr>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span></td>
                            <td><?php echo number_format($status['count']); ?> vi phạm</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                        <div class="progress-bar <?php echo $badgeClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Đợi trang tải hoàn toàn trước khi thực thi script
window.addEventListener('load', function() {
    // Chuẩn bị dữ liệu cho biểu đồ trạng thái
    const statusLabels = [];
    const statusData = [];
    const statusColors = [];
    
    <?php 
    foreach ($statusData as $status) {
        $color = '';
        $label = '';
        switch ($status['status']) {
            case 'Paid':
                $color = 'rgba(46, 204, 113, 0.8)';
                $label = 'Đã nộp phạt';
                break;
            case 'Unpaid':
                $color = 'rgba(231, 76, 60, 0.8)';
                $label = 'Chưa nộp phạt';
                break;
            case 'Processing':
                $color = 'rgba(243, 156, 18, 0.8)';
                $label = 'Đang xử lý';
                break;
            default:
                $color = 'rgba(52, 152, 219, 0.8)';
                $label = $status['status'];
        }
        echo "statusLabels.push('$label');";
        echo "statusData.push(" . $status['count'] . ");";
        echo "statusColors.push('$color');";
    }
    ?>
    
    // Biểu đồ trạng thái
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: statusColors,
                borderWidth: 1
            }]
        },
        options: {
            animation: {
                duration: 0 // Tắt animation để tránh scroll
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Biểu đồ loại phương tiện
    const vehicleLabels = [];
    const vehicleData = [];
    const vehicleColors = ['rgba(52, 152, 219, 0.8)', 'rgba(155, 89, 182, 0.8)', 'rgba(52, 73, 94, 0.8)', 'rgba(22, 160, 133, 0.8)'];
    
    <?php 
    $i = 0;
    foreach ($vehicleTypeData as $vehicle) {
        echo "vehicleLabels.push('" . $vehicle['vehicle_type'] . "');";
        echo "vehicleData.push(" . $vehicle['count'] . ");";
        $i++;
    }
    ?>
    
    const vehicleCtx = document.getElementById('vehicleTypeChart').getContext('2d');
    const vehicleChart = new Chart(vehicleCtx, {
        type: 'bar',
        data: {
            labels: vehicleLabels,
            datasets: [{
                label: 'Số vi phạm',
                data: vehicleData,
                backgroundColor: vehicleColors,
                borderWidth: 1
            }]
        },
        options: {
            animation: {
                duration: 0 // Tắt animation để tránh scroll
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Biểu đồ thời gian
    const timeLabels = [];
    const timeData = [];
    
    <?php 
    foreach ($timeData as $time) {
        // Chuyển đổi YYYY-MM sang định dạng tháng/năm
        $date = date_create_from_format('Y-m', $time['month']);
        $formatted = date_format($date, 'm/Y');
        echo "timeLabels.push('$formatted');";
        echo "timeData.push(" . $time['count'] . ");";
    }
    ?>
    
    const timeCtx = document.getElementById('timeChart').getContext('2d');
    const timeChart = new Chart(timeCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Số vi phạm',
                data: timeData,
                borderColor: 'rgba(52, 152, 219, 1)',
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            animation: {
                duration: 0 // Tắt animation để tránh scroll
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include_once 'layout/footer.php'; ?>