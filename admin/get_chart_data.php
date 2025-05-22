<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Số ngày cần lấy dữ liệu
$chart_days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
if ($chart_days <= 0) $chart_days = 14;
if ($chart_days > 90) $chart_days = 90; // Giới hạn tối đa 90 ngày

// Ngày hiện tại
$current_date = new DateTime();

// Mảng lưu nhãn (labels) cho biểu đồ
$chart_labels = [];
$new_violations = [];
$processed_violations = [];

// Kiểm tra xem có cột updated_at trong bảng violations không
$stmt = $conn->query("SHOW COLUMNS FROM violations LIKE 'updated_at'");
$has_updated_at = $stmt->rowCount() > 0;

// Lấy dữ liệu cho số ngày được yêu cầu
for ($i = ($chart_days - 1); $i >= 0; $i--) {
    $date = clone $current_date;
    $date->modify("-$i day");
    
    $day_str = $date->format('j/n'); // Format: ngày/tháng (vd: 1/5)
    $date_sql = $date->format('Y-m-d');
    
    // Thêm nhãn ngày
    $chart_labels[] = $day_str;
    
    // Đếm vi phạm mới trong ngày
    $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE DATE(created_at) = :date");
    $stmt->bindParam(':date', $date_sql);
    $stmt->execute();
    $new_violations[] = (int)$stmt->fetchColumn();
    
    // Đếm vi phạm đã xử lý trong ngày
    if ($has_updated_at) {
        // Nếu có cột updated_at, sử dụng nó
        $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE DATE(updated_at) = :date AND status = 'Paid'");
    } else {
        // Nếu không có cột updated_at, chỉ đếm số lượng vi phạm đã thanh toán mỗi ngày
        // Đây chỉ là một ước tính vì không biết chính xác khi nào vi phạm được xử lý
        $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE DATE(created_at) = :date AND status = 'Paid'");
    }
    $stmt->bindParam(':date', $date_sql);
    $stmt->execute();
    $processed_violations[] = (int)$stmt->fetchColumn();
}

// Chuẩn bị dữ liệu kết quả
$result = [
    'labels' => $chart_labels,
    'new_violations' => $new_violations,
    'processed_violations' => $processed_violations
];

// Trả về dữ liệu dạng JSON
header('Content-Type: application/json');
echo json_encode($result);
?>