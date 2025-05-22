<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Debug để kiểm tra request được nhận hay không
error_log("add_vehicle.php được truy cập, phương thức: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug POST data
    error_log("POST data nhận được: " . print_r($_POST, true));
    
    $license_plate = trim($_POST['license_plate'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? '';
    
    // Validate
    if (empty($license_plate) || empty($owner_name) || empty($vehicle_type)) {
        setFlashMessage('danger', 'Vui lòng điền đầy đủ thông tin phương tiện');
        header('Location: manage_vehicles.php');
        exit;
    }
    
    // Kiểm tra xem biển số đã tồn tại chưa
    try {
        $stmt = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = :license_plate");
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            setFlashMessage('warning', "Biển số xe '$license_plate' đã tồn tại trong hệ thống");
            header('Location: manage_vehicles.php');
            exit;
        }
        
        // Thêm phương tiện mới
        $stmt = $conn->prepare("INSERT INTO vehicles (license_plate, owner_name, vehicle_type) VALUES (:license_plate, :owner_name, :vehicle_type)");
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->bindParam(':owner_name', $owner_name);
        $stmt->bindParam(':vehicle_type', $vehicle_type);
        
        $stmt->execute();
        
        setFlashMessage('success', 'Thêm phương tiện mới thành công!');
        header('Location: manage_vehicles.php');
        exit;
    } catch (PDOException $e) {
        error_log("Lỗi PDO: " . $e->getMessage());
        setFlashMessage('danger', 'Lỗi khi thêm phương tiện: ' . $e->getMessage());
        header('Location: manage_vehicles.php');
        exit;
    }
} else {
    header('Location: manage_vehicles.php');
    exit;
}
?>