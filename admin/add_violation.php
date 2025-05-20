<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

$success = false;
$error = '';
$vehicle = null;

// Xử lý form tìm kiếm phương tiện
if (isset($_GET['license_plate']) && !empty($_GET['license_plate'])) {
    $license_plate = trim($_GET['license_plate']);
    $vehicle = getVehicleByLicensePlate($conn, $license_plate);
    
    if (!$vehicle) {
        $error = "Không tìm thấy phương tiện với biển số '$license_plate'";
    }
}

// Xử lý form thêm vi phạm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_violation'])) {
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $violation_date = $_POST['violation_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $fine_amount = $_POST['fine_amount'] ?? '';
    $status = $_POST['status'] ?? 'Unpaid';
    $description = $_POST['description'] ?? '';
    
    // Validate
    if (empty($vehicle_id) || empty($violation_date) || empty($location) || 
        empty($violation_type) || empty($fine_amount)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO violations (vehicle_id, violation_date, location, violation_type, fine_amount, status, description) 
                                    VALUES (:vehicle_id, :violation_date, :location, :violation_type, :fine_amount, :status, :description)");
            $stmt->bindParam(':vehicle_id', $vehicle_id);
            $stmt->bindParam(':violation_date', $violation_date);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':violation_type', $violation_type);
            $stmt->bindParam(':fine_amount', $fine_amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':description', $description);
            
            $stmt->execute();
            
            $success = true;
            setFlashMessage('success', 'Thêm vi phạm mới thành công!');
            header('Location: manage_violations.php');
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi khi thêm vi phạm: " . $e->getMessage();
        }
    }
}

// Xử lý form thêm phương tiện mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $license_plate = trim($_POST['license_plate'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? '';
    
    // Validate
    if (empty($license_plate) || empty($owner_name) || empty($vehicle_type)) {
        $error = "Vui lòng điền đầy đủ thông tin phương tiện";
    } else {
        // Kiểm tra xem biển số đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = :license_plate");
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Biển số xe '$license_plate' đã tồn tại trong hệ thống";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO vehicles (license_plate, owner_name, vehicle_type) VALUES (:license_plate, :owner_name, :vehicle_type)");
                $stmt->bindParam(':license_plate', $license_plate);
                $stmt->bindParam(':owner_name', $owner_name);
                $stmt->bindParam(':vehicle_type', $vehicle_type);
                
                $stmt->execute();
                
                // Lấy thông tin phương tiện vừa thêm
                $vehicle = getVehicleByLicensePlate($conn, $license_plate);
                
                $success = true;
                $message = "Thêm phương tiện mới thành công!";
            } catch (PDOException $e) {
                $error = "Lỗi khi thêm phương tiện: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Thêm vi phạm mới";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Thêm vi phạm mới</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success && isset($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Tìm kiếm phương tiện</h5>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" id="license_plate" name="license_plate" 
                       placeholder="Nhập biển số xe (VD: 29A-12345)" 
                       value="<?php echo isset($_GET['license_plate']) ? htmlspecialchars($_GET['license_plate']) : ''; ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </form>
        
        <div class="mt-3">
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="fas fa-plus"></i> Thêm phương tiện mới
            </button>
        </div>
    </div>
</div>

<?php if ($vehicle): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Thông tin phương tiện</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">ID phương tiện:</th>
                            <td><?php echo $vehicle['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Biển số xe:</th>
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

    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Thêm thông tin vi phạm</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="violation_date" class="form-label">Thời gian vi phạm <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="violation_date" name="violation_date" required>
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Địa điểm vi phạm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location" required placeholder="Nhập địa điểm xảy ra vi phạm">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="violation_type" class="form-label">Loại vi phạm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="violation_type" name="violation_type" required placeholder="Nhập loại vi phạm">
                    </div>
                    <div class="col-md-6">
                        <label for="fine_amount" class="form-label">Số tiền phạt (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="fine_amount" name="fine_amount" required placeholder="Nhập số tiền phạt">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Unpaid">Chưa nộp phạt</option>
                            <option value="Processing">Đang xử lý</option>
                            <option value="Paid">Đã nộp phạt</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả chi tiết</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Nhập mô tả chi tiết về vi phạm"></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="manage_violations.php" class="btn btn-secondary">Hủy</a>
                    <button type="submit" name="add_violation" class="btn btn-primary">Thêm vi phạm</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['license_plate'])): ?>
    <div class="alert alert-warning">
        <h4 class="alert-heading">Không tìm thấy phương tiện!</h4>
        <p>Không tìm thấy thông tin phương tiện với biển số <strong><?php echo htmlspecialchars($_GET['license_plate']); ?></strong> trong hệ thống.</p>
        <hr>
        <p class="mb-0">Bạn có thể <a href="#" data-bs-toggle="modal" data-bs-target="#addVehicleModal">thêm phương tiện mới</a> vào hệ thống.</p>
    </div>
<?php endif; ?>

<!-- Modal Thêm phương tiện mới -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addVehicleModalLabel">Thêm phương tiện mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="new_license_plate" class="form-label">Biển số xe <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_license_plate" name="license_plate" required placeholder="Nhập biển số xe (VD: 29A-12345)">
                    </div>
                    <div class="mb-3">
                        <label for="owner_name" class="form-label">Chủ phương tiện <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="owner_name" name="owner_name" required placeholder="Nhập tên chủ phương tiện">
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                        <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                            <option value="">-- Chọn loại phương tiện --</option>
                            <option value="Car">Ô tô</option>
                            <option value="Motorcycle">Xe máy</option>
                            <option value="Truck">Xe tải</option>
                            <option value="Bus">Xe khách</option>
                            <option value="Other">Khác</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_vehicle" class="btn btn-success">Thêm phương tiện</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>