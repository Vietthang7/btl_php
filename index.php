<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';

// Lấy thống kê vi phạm theo thời gian
$time_period = isset($_GET['period']) ? $_GET['period'] : 'month'; // Mặc định là theo tháng
$chart_data = [];
$chart_labels = [];

// Xử lý dữ liệu cho biểu đồ
switch($time_period) {
    case 'week':
        // Lấy dữ liệu 7 ngày gần nhất
        $days = 7;
        $current_date = new DateTime();
        
        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i day");
            
            $day_str = $date->format('d/m'); // Định dạng: ngày/tháng
            $date_sql = $date->format('Y-m-d');
            
            // Thêm nhãn ngày
            $chart_labels[] = $day_str;
            
            // Đếm vi phạm trong ngày
            $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE DATE(violation_date) = :date");
            $stmt->bindParam(':date', $date_sql);
            $stmt->execute();
            $chart_data[] = (int)$stmt->fetchColumn();
        }
        break;
        
    case 'month':
        // Lấy dữ liệu 30 ngày gần nhất
        $days = 30;
        $current_date = new DateTime();
        
        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i day");
            
            $day_str = $date->format('d/m');
            $date_sql = $date->format('Y-m-d');
            
            $chart_labels[] = $day_str;
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE DATE(violation_date) = :date");
            $stmt->bindParam(':date', $date_sql);
            $stmt->execute();
            $chart_data[] = (int)$stmt->fetchColumn();
        }
        break;
        
    case 'year':
        // Lấy dữ liệu 12 tháng gần nhất
        $current_date = new DateTime();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i month");
            
            $month_str = $date->format('m/Y');
            $month_start = $date->format('Y-m-01');
            $month_end = $date->format('Y-m-t');
            
            $chart_labels[] = $month_str;
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM violations 
                                    WHERE violation_date BETWEEN :start_date AND :end_date");
            $stmt->bindParam(':start_date', $month_start);
            $stmt->bindParam(':end_date', $month_end);
            $stmt->execute();
            $chart_data[] = (int)$stmt->fetchColumn();
        }
        break;
        
    case 'custom':
        // Xử lý khoảng thời gian tùy chọn nếu cần
        // ...
        break;
}

// Chuyển đổi thành JSON để sử dụng trong JavaScript
$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);

// Lấy top 5 loại vi phạm phổ biến nhất
$stmt = $conn->query("SELECT violation_type, COUNT(*) as count 
                     FROM violations 
                     GROUP BY violation_type 
                     ORDER BY count DESC 
                     LIMIT 5");
$top_violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$top_types = [];
$top_counts = [];

foreach ($top_violations as $violation) {
    $top_types[] = $violation['violation_type'];
    $top_counts[] = $violation['count'];
}

$top_types_json = json_encode($top_types);
$top_counts_json = json_encode($top_counts);

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

<!-- Thêm phần biểu đồ thống kê vi phạm -->
<div class="row mb-5">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <h4 class="mb-0 text-primary">
                    <i class="fas fa-chart-bar me-2"></i>Thống kê vi phạm giao thông
                </h4>
                <div class="btn-group">
                    <a href="?period=week" class="btn btn-sm <?php echo $time_period == 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">7 ngày</a>
                    <a href="?period=month" class="btn btn-sm <?php echo $time_period == 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">30 ngày</a>
                    <a href="?period=year" class="btn btn-sm <?php echo $time_period == 'year' ? 'btn-primary' : 'btn-outline-primary'; ?>">12 tháng</a>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="violationChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h4 class="mb-0 text-primary">
                    <i class="fas fa-exclamation-circle me-2"></i>Top vi phạm phổ biến
                </h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="topViolationsChart"></canvas>
                </div>
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

<!-- Thêm script cho biểu đồ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ thống kê vi phạm theo thời gian
    var ctx1 = document.getElementById('violationChart').getContext('2d');
    var violationChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                label: 'Số lượng vi phạm',
                data: <?php echo $chart_data_json; ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(54, 162, 235, 1)',
                pointRadius: 4,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
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
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    bodySpacing: 5,
                    callbacks: {
                        label: function(context) {
                            return 'Số vi phạm: ' + context.raw;
                        }
                    }
                }
            }
        }
    });
    
    // Biểu đồ top vi phạm phổ biến
    var ctx2 = document.getElementById('topViolationsChart').getContext('2d');
    var topViolationsChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?php echo $top_types_json; ?>,
            datasets: [{
                data: <?php echo $top_counts_json; ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 12
                        },
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
                            return `${context.label}: ${value} vi phạm (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});

// Thêm style cho chart container
document.head.insertAdjacentHTML('beforeend', `
    <style>
    .chart-container {
        position: relative;
        margin: auto;
        height: 300px;
    }
    </style>
`);
</script>

<?php include_once 'includes/footer.php'; ?>