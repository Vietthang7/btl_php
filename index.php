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
        $days = 7;
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
    case 'month':
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
        break;
}

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
        <h1 style="font-weight: 700; letter-spacing: 1px;">Tra cứu phương tiện vi phạm giao thông</h1>
        <p class="lead" style="font-size: 1.3rem;">Hệ thống tra cứu thông tin vi phạm giao thông trực tuyến - Nhanh chóng, chính xác và bảo mật</p>
    </div>
</div>

<!-- Tình hình giao thông và tin tức: Hai card cân bằng, luôn cao bằng nhau, có margin 2 bên -->
<div class="row mb-4 align-items-stretch gx-4 px-4">
    <div class="col-md-6 mb-3 d-flex">
        <div class="card shadow traffic-card border-0 flex-fill h-100">
            <div class="card-header bg-gradient-primary text-white" style="background: linear-gradient(90deg, #1e88e5 60%, #42a5f5); border-radius: .5rem .5rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-traffic-light me-2"></i>Tình hình giao thông hôm nay</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush traffic-list">
                    <li class="list-group-item"><span class="badge bg-success me-2">&bull;</span><strong>Quốc lộ 1A (TP.HCM - Long An):</strong> Lưu lượng phương tiện đông, di chuyển chậm tại một số nút giao lớn vào giờ cao điểm.</li>
                    <li class="list-group-item"><span class="badge bg-success me-2">&bull;</span><strong>Đại lộ Võ Văn Kiệt:</strong> Thông thoáng, không xảy ra ùn tắc.</li>
                    <li class="list-group-item"><span class="badge bg-warning me-2">&bull;</span><strong>Cầu Sài Gòn:</strong> Tai nạn nhỏ vào sáng nay đã được xử lý, giao thông đã ổn định trở lại.</li>
                    <li class="list-group-item"><span class="badge bg-danger me-2">&bull;</span><strong>Ngã tư Hàng Xanh:</strong> Kẹt xe nhẹ vào giờ tan tầm, nên chọn lộ trình thay thế.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3 d-flex">
        <div class="card shadow border-0 news-card flex-fill h-100">
            <div class="card-header bg-gradient-warning text-white" style="background: linear-gradient(90deg, #ffa000 60%, #ffca28); border-radius: .5rem .5rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>Tin tức giao thông</h4>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3"><span class="fw-bold text-primary">24/05/2025:</span> Sắp khởi công mở rộng tuyến đường Nguyễn Oanh (Q.Gò Vấp) để giảm ùn tắc khu vực cửa ngõ phía Bắc thành phố.</li>
                    <li class="mb-3"><span class="fw-bold text-primary">23/05/2025:</span> CSGT TP.HCM tăng cường kiểm tra nồng độ cồn các tuyến đường trung tâm vào buổi tối.</li>
                    <li class="mb-3"><span class="fw-bold text-primary">22/05/2025:</span> Đề xuất lắp đặt thêm camera giám sát tại các nút giao trọng điểm để xử lý vi phạm giao thông.</li>
                    <li><span class="fw-bold text-primary">21/05/2025:</span> Đường Nguyễn Văn Linh đoạn qua Q.7 thông xe trở lại sau thời gian sửa chữa mặt đường.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- End: Tình hình giao thông và tin tức -->

<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="card border-primary shadow-lg search-card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0 text-center"><i class="fas fa-search me-2"></i>Tra cứu vi phạm</h3>
            </div>
            <div class="card-body">
                <form action="search.php" method="GET">
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">Biển số xe</label>
                        <input type="text" class="form-control form-control-lg" id="license_plate" name="license_plate" placeholder="Nhập biển số xe (VD: 29A-12345)" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-search"></i> Tra cứu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Thêm phần biểu đồ thống kê vi phạm -->
<div class="row mb-5">
    <div class="col-md-8 mb-3">
        <div class="card h-100 shadow border-0">
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
    <div class="col-md-4 mb-3">
        <div class="card h-100 shadow border-0">
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

<!-- Ưu điểm hệ thống -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow feature-card border-0">
            <div class="card-body text-center">
                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                <h4>Tra cứu nhanh chóng</h4>
                <p>Hệ thống tra cứu thông tin vi phạm giao thông trực tuyến, giúp người dân dễ dàng kiểm tra các lỗi vi phạm và xử lý kịp thời.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow feature-card border-0">
            <div class="card-body text-center">
                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                <h4>Dữ liệu chính xác</h4>
                <p>Cơ sở dữ liệu được cập nhật liên tục từ các cơ quan chức năng, đảm bảo thông tin chính xác và mới nhất cho người dùng.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow feature-card border-0">
            <div class="card-body text-center">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h4>An toàn & Bảo mật</h4>
                <p>Thông tin tra cứu được bảo mật, đảm bảo quyền riêng tư của người dân khi sử dụng dịch vụ.</p>
            </div>
        </div>
    </div>
</div>
<!-- End: Ưu điểm hệ thống -->

<!-- Thêm style cho các card bổ sung -->
<style>
.traffic-card .card-header,
.news-card .card-header {
    border-radius: .5rem .5rem 0 0;
    font-weight: 600;
    font-size: 1.15rem;
}
.traffic-list .list-group-item {
    background: transparent;
    border: none;
    font-size: 1.08rem;
    padding-left: 0;
    padding-right: 0;
}
.news-card .card-body ul li {
    font-size: 1.02rem;
}
.feature-card .card-body i {
    color: #1976d2 !important;
    text-shadow: 0 2px 8px rgba(33, 150, 243, 0.14);
}
.search-card {
    border-width: 2px !important;
    border-radius: 1.25rem;
}
/* CÂN 2 CARD ĐẦU TRANG LUÔN BẰNG NHAU + margin 2 bên */
.row.mb-4.align-items-stretch > [class^="col-md-"] {
    display: flex;
    flex-direction: column;
}
.row.mb-4.align-items-stretch .card {
    flex: 1 1 auto;
    height: 100%;
}
@media (max-width: 767.98px) {
    .row.mb-4.align-items-stretch {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
    .row.mb-4.align-items-stretch > [class^="col-md-"] {
        display: block;
    }
}
</style>

<!-- Thêm script cho biểu đồ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx1 = document.getElementById('violationChart').getContext('2d');
    var violationChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                label: 'Số lượng vi phạm',
                data: <?php echo $chart_data_json; ?>,
                backgroundColor: 'rgba(66, 165, 245, 0.15)',
                borderColor: 'rgba(33, 150, 243, 0.9)',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(33, 150, 243, 1)',
                pointRadius: 5,
                fill: true,
                tension: 0.33
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
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
                    backgroundColor: 'rgba(33, 150, 243, 0.95)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    bodySpacing: 7,
                    callbacks: {
                        label: function(context) {
                            return 'Số vi phạm: ' + context.raw;
                        }
                    }
                }
            }
        }
    });

    var ctx2 = document.getElementById('topViolationsChart').getContext('2d');
    var topViolationsChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?php echo $top_types_json; ?>,
            datasets: [{
                data: <?php echo $top_counts_json; ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.85)',
                    'rgba(33, 150, 243, 0.85)',
                    'rgba(255, 206, 86, 0.85)',
                    'rgba(75, 192, 192, 0.85)',
                    'rgba(156, 39, 176, 0.85)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(33, 150, 243, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(156, 39, 176, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 13 },
                        boxWidth: 14,
                        padding: 17
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

    // Style cho chart container
    document.head.insertAdjacentHTML('beforeend', `
        <style>
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
        </style>
    `);
});
</script>

<?php include_once 'includes/footer.php'; ?>