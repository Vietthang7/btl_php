<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Xử lý xóa vi phạm
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM violations WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        setFlashMessage('success', 'Xóa vi phạm thành công!');
        header('Location: manage_violations.php');
        exit;
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Lỗi khi xóa vi phạm: ' . $e->getMessage());
        header('Location: manage_violations.php');
        exit;
    }
}

// Xử lý cập nhật trạng thái
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    
    if (in_array($status, ['Unpaid', 'Processing', 'Paid'])) {
        try {
            $stmt = $conn->prepare("UPDATE violations SET status = :status WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            setFlashMessage('success', 'Cập nhật trạng thái thành công!');
            header('Location: manage_violations.php');
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage());
            header('Location: manage_violations.php');
            exit;
        }
    }
}

// Lấy danh sách vi phạm
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

$pageTitle = "Quản lý vi phạm";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Quản lý vi phạm giao thông</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_violation.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Thêm vi phạm mới
            </a>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="card mb-4">
    <div class="card-header bg-white">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="Unpaid" <?php echo $status_filter == 'Unpaid' ? 'selected' : ''; ?>>Chưa nộp phạt</option>
                    <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Đã nộp phạt</option>
                </select>
            </div>
            <div class="col-md-5 text-end">
                <a href="manage_violations.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Biển số xe</th>
                        <th>Chủ xe</th>
                        <th>Thời gian vi phạm</th>
                        <th>Loại vi phạm</th>
                        <th>Địa điểm</th>
                        <th>Tiền phạt</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($violations) > 0): ?>
                        <?php foreach ($violations as $index => $violation): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($violation['license_plate']); ?></td>
                                <td><?php echo htmlspecialchars($violation['owner_name']); ?></td>
                                <td><?php echo formatDate($violation['violation_date']); ?></td>
                                <td><?php echo htmlspecialchars($violation['violation_type']); ?></td>
                                <td><?php echo htmlspecialchars($violation['location']); ?></td>
                                <td><?php echo formatMoney($violation['fine_amount']); ?></td>
                                <td>
                                    <?php if ($violation['status'] == 'Paid'): ?>
                                        <span class="badge bg-success">Đã nộp phạt</span>
                                    <?php elseif ($violation['status'] == 'Processing'): ?>
                                        <span class="badge bg-warning">Đang xử lý</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Chưa nộp phạt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="edit_violation.php?id=<?php echo $violation['id']; ?>">
                                                    <i class="fas fa-edit text-primary"></i> Sửa
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal<?php echo $violation['id']; ?>">
                                                    <i class="fas fa-trash-alt text-danger"></i> Xóa
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="manage_violations.php?action=update_status&id=<?php echo $violation['id']; ?>&status=Paid">
                                                    <i class="fas fa-check-circle text-success"></i> Đánh dấu đã nộp
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="manage_violations.php?action=update_status&id=<?php echo $violation['id']; ?>&status=Processing">
                                                    <i class="fas fa-sync text-warning"></i> Đánh dấu đang xử lý
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="manage_violations.php?action=update_status&id=<?php echo $violation['id']; ?>&status=Unpaid">
                                                    <i class="fas fa-times-circle text-danger"></i> Đánh dấu chưa nộp
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Modal Xác nhận xóa -->
                                    <div class="modal fade" id="confirmDeleteModal<?php echo $violation['id']; ?>" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="confirmDeleteModalLabel">Xác nhận xóa</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Bạn có chắc chắn muốn xóa vi phạm này không?</p>
                                                    <ul>
                                                        <li><strong>Biển số xe:</strong> <?php echo htmlspecialchars($violation['license_plate']); ?></li>
                                                        <li><strong>Loại vi phạm:</strong> <?php echo htmlspecialchars($violation['violation_type']); ?></li>
                                                        <li><strong>Thời gian:</strong> <?php echo formatDate($violation['violation_date']); ?></li>
                                                    </ul>
                                                    <p class="text-danger"><strong>Lưu ý:</strong> Hành động này không thể hoàn tác.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <a href="manage_violations.php?action=delete&id=<?php echo $violation['id']; ?>" class="btn btn-danger">Xác nhận xóa</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">Không tìm thấy vi phạm nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max($page - 2, 1);
                    $end_page = min($start_page + 4, $total_pages);
                    if ($end_page - $start_page < 4) {
                        $start_page = max($end_page - 4, 1);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'layout/footer.php'; ?>