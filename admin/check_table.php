<?php
include_once 'config/database.php';

// Kiểm tra cấu trúc bảng violations
try {
    $sql = "DESCRIBE violations";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Cấu trúc bảng violations:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>