<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';
include_once 'includes/header.php';

$license_plate = isset($_GET['license_plate']) ? trim($_GET['license_plate']) : '';
$violations = [];
$vehicle = null;
$searchPerformed = false;
$totalUnpaid = 0;

if (!empty($license_plate)) {
    $searchPerformed = true;
    
    // Tìm thông tin phương tiện
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE license_plate = :license_plate");
    $stmt->bindParam(':license_plate', $license_plate);
    $stmt->execute();
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle) {
        // Tìm các vi phạm của phương tiện
        $stmt = $conn->prepare("
            SELECT v.*, 
                   CASE 
                       WHEN v.status = 'Unpaid' THEN 'Chưa nộp phạt'
                       WHEN v.status = 'Processing' THEN 'Đang xử lý'
                       WHEN v.status = 'Paid' THEN 'Đã nộp phạt'
                       ELSE v.status
                   END as status_text
            FROM violations v 
            WHERE v.vehicle_id = :vehicle_id 
            ORDER BY v.violation_date DESC
        ");
        $stmt->bindParam(':vehicle_id', $vehicle['id']);
        $stmt->execute();
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính tổng tiền chưa nộp phạt
        foreach ($violations as $violation) {
            if ($violation['status'] == 'Unpaid') {
                $totalUnpaid += $violation['fine_amount'];
            }
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3 mt-3">
                    <h2 class="card-title h4 mb-0 text-center">Tra cứu vi phạm giao thông</h2>
                </div>
                <div class="card-body p-4">
                    <form action="search.php" method="GET">
                        <div class="mb-0">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-car text-primary"></i>
                                </span>
                                <input type="text" class="form-control" id="license_plate" name="license_plate" 
                                       value="<?php echo htmlspecialchars($license_plate); ?>" 
                                       placeholder="Nhập biển số xe (VD: 29A-12345)" required>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-search me-2"></i>Tra cứu
                                </button>
                            </div>
                            <div class="form-text text-muted mt-2">
                                Nhập đầy đủ biển số xe bao gồm cả ký tự đặc biệt
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($searchPerformed): ?>
        <?php if ($vehicle): ?>
            <div class="card border-0 shadow mb-4 fade-in" style="animation-delay: 0.1s;">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title h5 mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Thông tin phương tiện
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-2 col-md-3 mb-3 mb-md-0 text-center">
                            <div class="license-plate-display bg-primary text-white p-3 rounded">
                                <h4 class="mb-0"><?php echo htmlspecialchars($vehicle['license_plate']); ?></h4>
                            </div>
                        </div>
                        <div class="col-lg-10 col-md-9">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Chủ phương tiện</h6>
                                    <p class="fw-bold mb-0"><?php echo htmlspecialchars($vehicle['owner_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Loại phương tiện</h6>
                                    <p class="fw-bold mb-0"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Số CMND/CCCD</h6>
                                    <p class="fw-bold mb-0"><?php echo isset($vehicle["owner_id"]) ? htmlspecialchars($vehicle["owner_id"]) : "Chưa có thông tin"; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-1">Địa chỉ</h6>
                                    <p class="fw-bold mb-0"><?php echo isset($vehicle["owner_address"]) ? htmlspecialchars($vehicle["owner_address"]) : "Chưa có thông tin"; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($violations) > 0): ?>
                <div class="card border-0 shadow mb-4 fade-in" style="animation-delay: 0.2s;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title h5 mb-0">
                            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                            Danh sách vi phạm
                        </h3>
                        <?php if ($totalUnpaid > 0): ?>
                            <span class="badge bg-danger p-2">
                                Còn <?php echo count(array_filter($violations, function($v) { return $v['status'] == 'Unpaid'; })); ?> vi phạm chưa xử lý:
                                <span class="fw-bold"><?php echo formatMoney($totalUnpaid); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success p-2">Không có vi phạm chưa nộp phạt</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Thời gian vi phạm</th>
                                        <th>Địa điểm</th>
                                        <th>Lỗi vi phạm</th>
                                        <th>Tiền phạt</th>
                                        <th>Trạng thái</th>
                                        <th class="text-center">Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($violations as $violation): ?>
                                        <tr>
                                            <td><?php echo formatDate($violation['violation_date']); ?></td>
                                            <td><?php echo htmlspecialchars($violation['location']); ?></td>
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
                
                <?php if ($totalUnpaid > 0): ?>
                <div class="alert alert-warning fade-in" style="animation-delay: 0.3s;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading mb-1">Lưu ý quan trọng!</h5>
                            <p class="mb-0">Phương tiện của bạn hiện có <strong><?php echo count(array_filter($violations, function($v) { return $v['status'] == 'Unpaid'; })); ?> vi phạm</strong> chưa nộp phạt với tổng số tiền: <strong><?php echo formatMoney($totalUnpaid); ?></strong>. Vui lòng đến cơ quan chức năng để giải quyết các vi phạm trước thời hạn.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success fade-in" style="animation-delay: 0.3s;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading mb-1">Thông báo</h5>
                            <p class="mb-0">Phương tiện với biển số <strong><?php echo htmlspecialchars($license_plate); ?></strong> hiện không có vi phạm nào chưa xử lý.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-success fade-in">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading mb-1">Thông báo</h5>
                            <p class="mb-0">Phương tiện với biển số <strong><?php echo htmlspecialchars($license_plate); ?></strong> hiện không có vi phạm nào.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger fade-in">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading mb-1">Không tìm thấy thông tin</h5>
                        <p class="mb-0">Không tìm thấy thông tin phương tiện với biển số <strong><?php echo htmlspecialchars($license_plate); ?></strong> trong hệ thống. Vui lòng kiểm tra lại biển số xe.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card bg-light border-0">
                <div class="card-body p-4">
                    <h4 class="card-title mb-3"><i class="fas fa-question-circle me-2 text-primary"></i>Hướng dẫn tra cứu</h4>
                    <ol class="mb-0">
                        <li class="mb-2">Nhập đầy đủ biển số xe của bạn vào ô tìm kiếm (Ví dụ: 29A-12345)</li>
                        <li class="mb-2">Nhấn nút "Tra cứu" để xem thông tin vi phạm</li>
                        <li class="mb-2">Hệ thống sẽ hiển thị danh sách các vi phạm (nếu có) và trạng thái nộp phạt</li>
                        <li class="mb-2">Nhấn vào nút "Xem" để xem chi tiết từng vi phạm</li>
                        <li>Nếu có vi phạm chưa nộp phạt, vui lòng đến cơ quan chức năng để giải quyết</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.license-plate-display {
    border-radius: 8px;
    font-size: 1.5rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.fade-in {
    animation: fadeIn 0.5s ease-in forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Xóa mọi modal-backdrop khi tải trang */
body .modal-backdrop {
    display: none !important;
}

/* Đảm bảo body không bị khóa cuộn */
body.modal-open {
    overflow: auto !important;
    padding-right: 0 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xóa bất kỳ modal-backdrop nào khi trang tải xong
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.remove();
    });
    
    // Khôi phục trạng thái của body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});
</script>

<?php include_once 'includes/footer.php'; ?>