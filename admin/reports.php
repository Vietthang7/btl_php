<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy thống kê theo trạng thái
$stmt = $conn->query("SELECT status, COUNT(*) as count, SUM(fine_amount) as total 
                      FROM violations GROUP BY status ORDER BY FIELD(status, 'Unpaid', 'Processing', 'Paid')");
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thống kê theo loại phương tiện
$stmt = $conn->query("SELECT v.vehicle_type, COUNT(vio.id) as violation_count, SUM(vio.fine_amount) as total_fine
                      FROM vehicles v
                      LEFT JOIN violations vio ON v.id = vio.vehicle_id
                      GROUP BY v.vehicle_type
                      ORDER BY violation_count DESC");
$vehicle_type_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thống kê theo thời gian (6 tháng gần nhất)
$stmt = $conn->query("SELECT DATE_FORMAT(violation_date, '%Y-%m') as month, COUNT(*) as count, SUM(fine_amount) as total
                      FROM violations 
                      WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(violation_date, '%Y-%m')
                      ORDER BY month ASC");
$time_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thống kê vi phạm phổ biến
$stmt = $conn->query("SELECT violation_type, COUNT(*) as count, SUM(fine_amount) as total_fine
                      FROM violations 
                      GROUP BY violation_type
                      ORDER BY count DESC
                      LIMIT 10");
$common_violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Báo cáo thống kê";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Báo cáo thống kê</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-print">
                <i class="fas fa-print"></i> In báo cáo
            </button>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thống kê theo trạng thái</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="250"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Trạng thái</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Tổng tiền phạt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($stat['status'] == 'Unpaid') echo 'Chưa nộp phạt';
                                        elseif ($stat['status'] == 'Processing') echo 'Đang xử lý';
                                        elseif ($stat['status'] == 'Paid') echo 'Đã nộp phạt';
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo number_format($stat['count']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($stat['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Thống kê theo loại phương tiện</h5>
            </div>
            <div class="card-body">
                <canvas id="vehicleTypeChart" height="250"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Loại phương tiện</th>
                                <th class="text-center">Số vi phạm</th>
                                <th class="text-end">Tổng tiền phạt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicle_type_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $vehicle_type_map = [
                                            'Car' => 'Ô tô',
                                            'Motorcycle' => 'Xe máy',
                                            'Truck' => 'Xe tải',
                                            'Bus' => 'Xe khách',
                                            'Other' => 'Khác'
                                        ];
                                        echo $vehicle_type_map[$stat['vehicle_type']] ?? $stat['vehicle_type'];
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo number_format($stat['violation_count']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($stat['total_fine']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Thống kê theo thời gian (6 tháng gần nhất)</h5>
            </div>
            <div class="card-body">
                <canvas id="timeChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Top 10 vi phạm phổ biến</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Loại vi phạm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Tổng tiền phạt</th>
                                <th class="text-center">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_violations = array_sum(array_column($common_violations, 'count'));
                            foreach ($common_violations as $index => $violation): 
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                    <td class="text-center"><?php echo number_format($violation['count']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($violation['total_fine']); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $percentage = ($violation['count'] / $total_violations) * 100;
                                        echo number_format($percentage, 1) . '%';
                                        ?>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar bg-danger" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart cho thống kê theo trạng thái
    var statusLabels = [];
    var statusData = [];
    var statusColors = [];
    
    <?php foreach ($status_stats as $stat): ?>
        <?php if ($stat['status'] == 'Unpaid'): ?>
            statusLabels.push('Chưa nộp phạt');
            statusColors.push('rgba(220, 53, 69, 0.8)');
        <?php elseif ($stat['status'] == 'Processing'): ?>
            statusLabels.push('Đang xử lý');
            statusColors.push('rgba(255, 193, 7, 0.8)');
        <?php elseif ($stat['status'] == 'Paid'): ?>
            statusLabels.push('Đã nộp phạt');
            statusColors.push('rgba(40, 167, 69, 0.8)');
        <?php endif; ?>
        statusData.push(<?php echo $stat['count']; ?>);
    <?php endforeach; ?>
    
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
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
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Chart cho thống kê theo loại phương tiện
    var vehicleTypeLabels = [];
    var vehicleTypeData = [];
    var vehicleTypeColors = [
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(153, 102, 255, 0.8)'
    ];
    
    <?php foreach ($vehicle_type_stats as $stat): ?>
        <?php 
        $vehicle_type_map = [
            'Car' => 'Ô tô',
            'Motorcycle' => 'Xe máy',
            'Truck' => 'Xe tải',
            'Bus' => 'Xe khách',
            'Other' => 'Khác'
        ];
        $label = $vehicle_type_map[$stat['vehicle_type']] ?? $stat['vehicle_type'];
        ?>
        vehicleTypeLabels.push('<?php echo $label; ?>');
        vehicleTypeData.push(<?php echo $stat['violation_count']; ?>);
    <?php endforeach; ?>
    
    var vehicleTypeCtx = document.getElementById('vehicleTypeChart').getContext('2d');
    var vehicleTypeChart = new Chart(vehicleTypeCtx, {
        type: 'doughnut',
        data: {
            labels: vehicleTypeLabels,
            datasets: [{
                data: vehicleTypeData,
                backgroundColor: vehicleTypeColors,
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
    
    // Chart cho thống kê theo thời gian
    var timeLabels = [];
    var timeData = [];
    var fineData = [];
    
    <?php foreach ($time_stats as $stat): ?>
        <?php
        // Chuyển đổi định dạng tháng từ yyyy-mm sang mm/yyyy
        $date = DateTime::createFromFormat('Y-m', $stat['month']);
        $formatted_month = $date ? $date->format('m/Y') : $stat['month'];
        ?>
        timeLabels.push('<?php echo $formatted_month; ?>');
        timeData.push(<?php echo $stat['count']; ?>);
        fineData.push(<?php echo $stat['total']; ?>);
    <?php endforeach; ?>
    
    var timeCtx = document.getElementById('timeChart').getContext('2d');
    var timeChart = new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: timeLabels,
            datasets: [
                {
                    label: 'Số vi phạm',
                    data: timeData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Tiền phạt (VNĐ)',
                    data: fineData,
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Số vi phạm'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'Tiền phạt (VNĐ)'
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            return value.toLocaleString() + ' VNĐ';
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include_once 'layout/footer.php'; ?>