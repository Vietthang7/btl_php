<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_violations.php');
    exit;
}

$violation_id = (int)$_GET['id'];

// Lấy thông tin vi phạm
$stmt = $conn->prepare("SELECT v.*, vh.license_plate, vh.owner_name, vh.vehicle_type 
                        FROM violations v 
                        JOIN vehicles vh ON v.vehicle_id = vh.id 
                        WHERE v.id = :id");
$stmt->bindParam(':id', $violation_id);
$stmt->execute();
$violation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$violation) {
    setFlashMessage('danger', 'Không tìm thấy thông tin vi phạm!');
    header('Location: manage_violations.php');
    exit;
}

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violation_date = $_POST['violation_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $fine_amount = $_POST['fine_amount'] ?? '';
    $status = $_POST['status'] ?? 'Unpaid';
    $description = $_POST['description'] ?? '';
    
    // Validate
    if (empty($violation_date) || empty($location) || empty($violation_type) || empty($fine_amount)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE violations SET 
                                    violation_date = :violation_date,
                                    location = :location,
                                    violation_type = :violation_type,
                                    fine_amount = :fine_amount,
                                    status = :status,
                                    description = :description
                                    WHERE id = :id");
            $stmt->bindParam(':violation_date', $violation_date);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':violation_type', $violation_type);
            $stmt->bindParam(':fine_amount', $fine_amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $violation_id);
            
            $stmt->execute();
            
            setFlashMessage('success', 'Cập nhật thông tin vi phạm thành công!');
            header('Location: manage_violations.php');
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật vi phạm: " . $e->getMessage();
        }
    }
}

$pageTitle = "Sửa thông tin vi phạm";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Sửa thông tin vi phạm</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="manage_violations.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">Thông tin phương tiện</h5>
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
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Sửa thông tin vi phạm</h5>
    </div>
    <div class="card-body">
        <form action="" method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="violation_date" class="form-label">Thời gian vi phạm <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" id="violation_date" name="violation_date" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($violation['violation_date'])); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="location" class="form-label">Địa điểm vi phạm <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($violation['location']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="violation_type" class="form-label">Loại vi phạm <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="violation_type" name="violation_type" 
                           value="<?php echo htmlspecialchars($violation['violation_type']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="fine_amount" class="form-label">Số tiền phạt (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="fine_amount" name="fine_amount" 
                           value="<?php echo $violation['fine_amount']; ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Unpaid" <?php echo $violation['status'] == 'Unpaid' ? 'selected' : ''; ?>>Chưa nộp phạt</option>
                        <option value="Processing" <?php echo $violation['status'] == 'Processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="Paid" <?php echo $violation['status'] == 'Paid' ? 'selected' : ''; ?>>Đã nộp phạt</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả chi tiết</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($violation['description']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="manage_violations.php" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>