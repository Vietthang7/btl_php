<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    $violation_date = trim($_POST['violation_date'] ?? '');
    $violation_time = trim($_POST['violation_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $violation_type = trim($_POST['violation_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fine_amount = isset($_POST['fine_amount']) ? (float)$_POST['fine_amount'] : 0;
    
    // Validate
    $errors = [];
    
    if (empty($vehicle_id)) {
        $errors[] = "Vui lòng chọn phương tiện";
    }
    
    if (empty($violation_date)) {
        $errors[] = "Vui lòng chọn ngày vi phạm";
    }
    
    if (empty($violation_time)) {
        $errors[] = "Vui lòng chọn giờ vi phạm";
    }
    
    if (empty($location)) {
        $errors[] = "Vui lòng nhập địa điểm vi phạm";
    }
    
    if (empty($violation_type)) {
        $errors[] = "Vui lòng chọn loại vi phạm";
    }
    
    if ($fine_amount <= 0) {
        $errors[] = "Số tiền phạt phải lớn hơn 0";
    }
    
    if (count($errors) > 0) {
        setFlashMessage(implode("<br>", $errors), 'danger');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    try {
        // Kết hợp ngày và giờ
        $violation_date_full = $violation_date . ' ' . $violation_time;
        
        // Thêm vi phạm - ĐÃ SỬA: sử dụng violation_date thay vì violation_datetime
        $stmt = $conn->prepare("
            INSERT INTO violations 
            (vehicle_id, violation_date, location, violation_type, description, fine_amount, status) 
            VALUES 
            (:vehicle_id, :violation_date, :location, :violation_type, :description, :fine_amount, :status)
        ");
        
        $stmt->bindParam(':vehicle_id', $vehicle_id);
        $stmt->bindParam(':violation_date', $violation_date_full); // ĐÃ SỬA: sử dụng violation_date thay vì violation_datetime
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':violation_type', $violation_type);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':fine_amount', $fine_amount);
        $stmt->bindValue(':status', 'Unpaid'); // Sử dụng giá trị mặc định
        
        $stmt->execute();
        
        setFlashMessage('Thêm vi phạm mới thành công!', 'success');
        header('Location: manage_violations.php');
        exit;
    } catch (PDOException $e) {
        error_log("Lỗi PDO: " . $e->getMessage());
        setFlashMessage('Lỗi khi thêm vi phạm: ' . $e->getMessage(), 'danger');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    // Lấy biển số xe nếu được truyền từ URL
    $license_plate = trim($_GET['license_plate'] ?? '');
    $vehicle_id = 0;
    
    if (!empty($license_plate)) {
        // Tìm vehicle_id từ biển số xe
        $stmt = $conn->prepare("SELECT id, owner_name FROM vehicles WHERE license_plate = :license_plate");
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->execute();
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle) {
            $vehicle_id = $vehicle['id'];
        }
    }
    
    // Lấy danh sách phương tiện để hiển thị trong form
    $stmt = $conn->query("SELECT id, license_plate, owner_name FROM vehicles ORDER BY license_plate");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Thêm vi phạm mới";
    include_once 'layout/header.php';
    ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Thêm vi phạm mới</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="manage_violations.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
    
    <?php displayFlashMessage(); ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form action="add_violation.php" method="POST" id="addViolationForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="vehicle_id" class="form-label">Phương tiện vi phạm <span class="text-danger">*</span></label>
                        <select class="form-select" id="vehicle_id" name="vehicle_id" required data-bs-toggle="tooltip" data-bs-placement="top" title="Chọn phương tiện vi phạm">
                            <option value="">-- Chọn phương tiện --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" <?php echo $vehicle['id'] == $vehicle_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['owner_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="violation_date" class="form-label">Ngày vi phạm <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="violation_date" name="violation_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="violation_time" class="form-label">Giờ vi phạm <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="violation_time" name="violation_time" required value="<?php echo date('H:i'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Địa điểm vi phạm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="location" name="location" required placeholder="Nhập địa điểm vi phạm">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="violation_type" class="form-label">Loại vi phạm <span class="text-danger">*</span></label>
                        <select class="form-select" id="violation_type" name="violation_type" required>
                            <option value="">-- Chọn loại vi phạm --</option>
                            <option value="Vượt đèn đỏ">Vượt đèn đỏ</option>
                            <option value="Vượt quá tốc độ">Vượt quá tốc độ</option>
                            <option value="Đi ngược chiều">Đi ngược chiều</option>
                            <option value="Lấn làn">Lấn làn</option>
                            <option value="Không đội mũ bảo hiểm">Không đội mũ bảo hiểm</option>
                            <option value="Không có giấy phép lái xe">Không có giấy phép lái xe</option>
                            <option value="Không có giấy đăng ký xe">Không có giấy đăng ký xe</option>
                            <option value="Vi phạm nồng độ cồn">Vi phạm nồng độ cồn</option>
                            <option value="Đỗ xe không đúng nơi quy định">Đỗ xe không đúng nơi quy định</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fine_amount" class="form-label">Số tiền phạt (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="fine_amount" name="fine_amount" required placeholder="Nhập số tiền phạt" min="10000" step="10000" value="100000">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả chi tiết</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Nhập mô tả chi tiết về vi phạm (nếu có)"></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="manage_violations.php" class="btn btn-secondary me-md-2">Hủy</a>
                    <button type="submit" class="btn btn-primary" id="submitViolation">Thêm vi phạm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const addViolationForm = document.getElementById("addViolationForm");
        if (addViolationForm) {
            addViolationForm.addEventListener("submit", function(event) {
                // Validate form
                const vehicle_id = document.getElementById("vehicle_id").value;
                const violation_date = document.getElementById("violation_date").value;
                const violation_time = document.getElementById("violation_time").value;
                const location = document.getElementById("location").value;
                const violation_type = document.getElementById("violation_type").value;
                const fine_amount = document.getElementById("fine_amount").value;
                
                const errors = [];
                
                if (!vehicle_id) {
                    errors.push("Vui lòng chọn phương tiện");
                }
                
                if (!violation_date) {
                    errors.push("Vui lòng chọn ngày vi phạm");
                }
                
                if (!violation_time) {
                    errors.push("Vui lòng chọn giờ vi phạm");
                }
                
                if (!location) {
                    errors.push("Vui lòng nhập địa điểm vi phạm");
                }
                
                if (!violation_type) {
                    errors.push("Vui lòng chọn loại vi phạm");
                }
                
                if (fine_amount <= 0) {
                    errors.push("Số tiền phạt phải lớn hơn 0");
                }
                
                if (errors.length > 0) {
                    event.preventDefault();
                    alert("Vui lòng kiểm tra lại thông tin:\n- " + errors.join("\n- "));
                }
            });
        }
        
        // Format số tiền phạt
        const fineInput = document.getElementById("fine_amount");
        if (fineInput) {
            fineInput.addEventListener("change", function() {
                if (this.value < 10000) {
                    this.value = 10000;
                }
            });
        }
    });
    </script>

    <?php include_once 'layout/footer.php';
}
?>