<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $stmt = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = :license_plate");
    $stmt->bindParam(':license_plate', $license_plate);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        setFlashMessage('warning', "Biển số xe '$license_plate' đã tồn tại trong hệ thống");
        header('Location: manage_vehicles.php');
        exit;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO vehicles (license_plate, owner_name, vehicle_type) VALUES (:license_plate, :owner_name, :vehicle_type)");
        $stmt->bindParam(':license_plate', $license_plate);
        $stmt->bindParam(':owner_name', $owner_name);
        $stmt->bindParam(':vehicle_type', $vehicle_type);
        
        $stmt->execute();
        
        setFlashMessage('success', 'Thêm phương tiện mới thành công!');
        header('Location: manage_vehicles.php');
        exit;
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Lỗi khi thêm phương tiện: ' . $e->getMessage());
        header('Location: manage_vehicles.php');
        exit;
    }
} else {
    header('Location: manage_vehicles.php');
    exit;
}
?>