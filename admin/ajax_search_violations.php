<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Nhận tham số tìm kiếm từ AJAX request
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (vh.license_plate LIKE :search OR vh.owner_name LIKE :search OR v.violation_type LIKE :search OR v.location LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    $where .= " AND v.status = :status";
    $params[':status'] = $status_filter;
}

// Tổng số vi phạm
$query = "SELECT COUNT(*) FROM violations v 
          JOIN vehicles vh ON v.vehicle_id = vh.id 
          WHERE $where";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Danh sách vi phạm
$query = "SELECT v.*, vh.license_plate, vh.owner_name 
          FROM violations v 
          JOIN vehicles vh ON v.vehicle_id = vh.id 
          WHERE $where 
          ORDER BY v.violation_date DESC
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu HTML cho bảng kết quả
$html = '';

if (count($violations) > 0) {
    foreach ($violations as $index => $violation) {
        $html .= '<tr class="result-row">';
        $html .= '<td>' . ($offset + $index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($violation['license_plate']) . '</td>';
        $html .= '<td>' . htmlspecialchars($violation['owner_name']) . '</td>';
        $html .= '<td>' . formatDate($violation['violation_date']) . '</td>';
        $html .= '<td>' . htmlspecialchars($violation['violation_type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($violation['location']) . '</td>';
        $html .= '<td>' . formatMoney($violation['fine_amount']) . '</td>';
        
        // Trạng thái
        $html .= '<td>';
        if ($violation['status'] == 'Paid') {
            $html .= '<span class="badge bg-success">Đã nộp phạt</span>';
        } elseif ($violation['status'] == 'Processing') {
            $html .= '<span class="badge bg-warning">Đang xử lý</span>';
        } else {
            $html .= '<span class="badge bg-danger">Chưa nộp phạt</span>';
        }
        $html .= '</td>';
        
        // Thao tác
        $html .= '<td>';
        $html .= '<div class="dropdown">';
        $html .= '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        $html .= '<i class="fas fa-cog"></i>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end">';
        
        // Sửa
        $html .= '<li>';
        $html .= '<a class="dropdown-item" href="edit_violation.php?id=' . $violation['id'] . '">';
        $html .= '<i class="fas fa-edit text-primary"></i> Sửa';
        $html .= '</a>';
        $html .= '</li>';
        $html .= '<li><hr class="dropdown-divider"></li>';
        
        // Xóa
        $html .= '<li>';
        $html .= '<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal' . $violation['id'] . '">';
        $html .= '<i class="fas fa-trash-alt text-danger"></i> Xóa';
        $html .= '</a>';
        $html .= '</li>';
        $html .= '<li><hr class="dropdown-divider"></li>';
        
        // Cập nhật trạng thái
        $html .= '<li>';
        $html .= '<a class="dropdown-item" href="manage_violations.php?action=update_status&id=' . $violation['id'] . '&status=Paid">';
        $html .= '<i class="fas fa-check-circle text-success"></i> Đánh dấu đã nộp';
        $html .= '</a>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<a class="dropdown-item" href="manage_violations.php?action=update_status&id=' . $violation['id'] . '&status=Processing">';
        $html .= '<i class="fas fa-sync text-warning"></i> Đánh dấu đang xử lý';
        $html .= '</a>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<a class="dropdown-item" href="manage_violations.php?action=update_status&id=' . $violation['id'] . '&status=Unpaid">';
        $html .= '<i class="fas fa-times-circle text-danger"></i> Đánh dấu chưa nộp';
        $html .= '</a>';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        // Modal xác nhận xóa
        $html .= '<div class="modal fade" id="confirmDeleteModal' . $violation['id'] . '" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">';
        $html .= '<div class="modal-dialog">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header bg-danger text-white">';
        $html .= '<h5 class="modal-title" id="confirmDeleteModalLabel">Xác nhận xóa</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<p>Bạn có chắc chắn muốn xóa vi phạm này không?</p>';
        $html .= '<ul>';
        $html .= '<li><strong>Biển số xe:</strong> ' . htmlspecialchars($violation['license_plate']) . '</li>';
        $html .= '<li><strong>Loại vi phạm:</strong> ' . htmlspecialchars($violation['violation_type']) . '</li>';
        $html .= '<li><strong>Thời gian:</strong> ' . formatDate($violation['violation_date']) . '</li>';
        $html .= '</ul>';
        $html .= '<p class="text-danger"><strong>Lưu ý:</strong> Hành động này không thể hoàn tác.</p>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>';
        $html .= '<a href="manage_violations.php?action=delete&id=' . $violation['id'] . '" class="btn btn-danger">Xác nhận xóa</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr>';
    $html .= '<td colspan="9" class="text-center py-4">Không tìm thấy vi phạm nào</td>';
    $html .= '</tr>';
}

// Tạo phân trang
$pagination = '';
if ($total_pages > 1) {
    $pagination .= '<nav>';
    $pagination .= '<ul class="pagination justify-content-center mb-0">';
    
    // Previous buttons
    if ($page > 1) {
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link page-link-ajax" href="javascript:void(0);" data-page="1">';
        $pagination .= '<i class="fas fa-angle-double-left"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link page-link-ajax" href="javascript:void(0);" data-page="' . ($page - 1) . '">';
        $pagination .= '<i class="fas fa-angle-left"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    } else {
        $pagination .= '<li class="page-item disabled">';
        $pagination .= '<a class="page-link" href="javascript:void(0);">';
        $pagination .= '<i class="fas fa-angle-double-left"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
        $pagination .= '<li class="page-item disabled">';
        $pagination .= '<a class="page-link" href="javascript:void(0);">';
        $pagination .= '<i class="fas fa-angle-left"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    }
    
    // Page numbers
    $start_page = max($page - 2, 1);
    $end_page = min($start_page + 4, $total_pages);
    if ($end_page - $start_page < 4) {
        $start_page = max($end_page - 4, 1);
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = $i == $page ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '">';
        $pagination .= '<a class="page-link page-link-ajax" href="javascript:void(0);" data-page="' . $i . '">' . $i . '</a>';
        $pagination .= '</li>';
    }
    
    // Next buttons
    if ($page < $total_pages) {
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link page-link-ajax" href="javascript:void(0);" data-page="' . ($page + 1) . '">';
        $pagination .= '<i class="fas fa-angle-right"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link page-link-ajax" href="javascript:void(0);" data-page="' . $total_pages . '">';
        $pagination .= '<i class="fas fa-angle-double-right"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    } else {
        $pagination .= '<li class="page-item disabled">';
        $pagination .= '<a class="page-link" href="javascript:void(0);">';
        $pagination .= '<i class="fas fa-angle-right"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
        $pagination .= '<li class="page-item disabled">';
        $pagination .= '<a class="page-link" href="javascript:void(0);">';
        $pagination .= '<i class="fas fa-angle-double-right"></i>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
}

// Tạo HTML mẫu cho hàng dữ liệu trống - được sử dụng để giữ chiều cao bảng
$placeholderRows = '';
for ($i = 0; $i < $limit; $i++) {
    $placeholderRows .= '<tr class="placeholder-row" style="height: 53px;"><td colspan="9"></td></tr>';
}

// Trả về kết quả dưới dạng JSON
$response = [
    'html' => $html,
    'pagination' => $pagination,
    'total' => $total,
    'page' => $page,
    'total_pages' => $total_pages,
    'placeholderRows' => $placeholderRows,
    'limit' => $limit
];

header('Content-Type: application/json');
echo json_encode($response);
?>