<?php
// Hàm lấy thông tin phương tiện theo biển số
function getVehicleByLicensePlate($conn, $licensePlate) {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE license_plate = :license_plate");
    $stmt->bindParam(':license_plate', $licensePlate);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm lấy danh sách vi phạm theo ID phương tiện
function getViolationsByVehicleId($conn, $vehicleId) {
    $stmt = $conn->prepare("SELECT * FROM violations WHERE vehicle_id = :vehicle_id ORDER BY violation_date DESC");
    $stmt->bindParam(':vehicle_id', $vehicleId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy thông tin vi phạm theo ID
function getViolationById($conn, $id) {
    $stmt = $conn->prepare("SELECT v.*, vh.license_plate, vh.owner_name, vh.vehicle_type 
                            FROM violations v 
                            JOIN vehicles vh ON v.vehicle_id = vh.id 
                            WHERE v.id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm định dạng số tiền
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// Hàm format ngày tháng
function formatDate($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

// Hàm kiểm tra người dùng đã đăng nhập chưa
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect nếu chưa đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /admin/login.php");
        exit;
    }
}

// Hàm tạo thông báo
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Hàm hiển thị thông báo
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        echo '<div class="alert alert-' . $message['type'] . ' alert-dismissible fade show" role="alert">';
        echo $message['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}
?>