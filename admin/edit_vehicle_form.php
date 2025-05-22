<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Kiểm tra ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('danger', 'Không tìm thấy phương tiện!');
    header('Location: manage_vehicles.php');
    exit;
}

$id = (int)$_GET['id'];

// Lấy thông tin phương tiện
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy phương tiện
if (!$vehicle) {
    setFlashMessage('danger', 'Không tìm thấy phương tiện!');
    header('Location: manage_vehicles.php');
    exit;
}

$pageTitle = "Sửa thông tin phương tiện";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Sửa thông tin phương tiện</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_vehicles.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="card">
    <div class="card-body">
        <form action="edit_vehicle.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $vehicle['id']; ?>">
            <div class="mb-3">
                <label for="license_plate" class="form-label">Biển số xe <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="license_plate" name="license_plate" value="<?php echo htmlspecialchars($vehicle['license_plate']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="owner_name" class="form-label">Chủ phương tiện <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($vehicle['owner_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="vehicle_type" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                    <option value="Car" <?php echo $vehicle['vehicle_type'] == 'Car' ? 'selected' : ''; ?>>Ô tô</option>
                    <option value="Motorcycle" <?php echo $vehicle['vehicle_type'] == 'Motorcycle' ? 'selected' : ''; ?>>Xe máy</option>
                    <option value="Truck" <?php echo $vehicle['vehicle_type'] == 'Truck' ? 'selected' : ''; ?>>Xe tải</option>
                    <option value="Bus" <?php echo $vehicle['vehicle_type'] == 'Bus' ? 'selected' : ''; ?>>Xe khách</option>
                    <option value="Other" <?php echo $vehicle['vehicle_type'] == 'Other' ? 'selected' : ''; ?>>Khác</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="manage_vehicles.php" class="btn btn-secondary ms-2">Hủy</a>
        </form>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>