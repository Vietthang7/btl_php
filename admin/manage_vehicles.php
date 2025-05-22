<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

// Xử lý xóa phương tiện
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Kiểm tra xem phương tiện có vi phạm hay không
        $stmt = $conn->prepare("SELECT COUNT(*) FROM violations WHERE vehicle_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $violation_count = $stmt->fetchColumn();
        
        if ($violation_count > 0) {
            setFlashMessage('warning', 'Không thể xóa phương tiện này vì có ' . $violation_count . ' vi phạm liên quan!');
        } else {
            $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            setFlashMessage('success', 'Xóa phương tiện thành công!');
        }
        
        header('Location: manage_vehicles.php');
        exit;
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Lỗi khi xóa phương tiện: ' . $e->getMessage());
        header('Location: manage_vehicles.php');
        exit;
    }
}

// Lấy danh sách phương tiện
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$vehicle_type = isset($_GET['vehicle_type']) ? $_GET['vehicle_type'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (license_plate LIKE :search OR owner_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($vehicle_type)) {
    $where .= " AND vehicle_type = :vehicle_type";
    $params[':vehicle_type'] = $vehicle_type;
}

// Tổng số phương tiện
$query = "SELECT COUNT(*) FROM vehicles WHERE $where";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Danh sách phương tiện
$query = "SELECT v.*, (SELECT COUNT(*) FROM violations WHERE vehicle_id = v.id) AS violation_count 
          FROM vehicles v 
          WHERE $where 
          ORDER BY v.created_at DESC
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Quản lý phương tiện";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Quản lý phương tiện</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_vehicle_form.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Thêm phương tiện
            </a>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div style="width: 280px;">
                <form action="" method="GET" id="searchForm" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Tìm kiếm biển số, chủ xe..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary px-3" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <?php if (!empty($vehicle_type)): ?>
                        <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($vehicle_type); ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <div style="width: 280px;">
                <form action="" method="GET" id="filterForm">
                    <select class="form-select" name="vehicle_type" id="vehicleTypeSelect" onchange="this.form.submit()">
                        <option value="">Tất cả loại phương tiện</option>
                        <option value="Car" <?php echo $vehicle_type == 'Car' ? 'selected' : ''; ?>>Ô tô</option>
                        <option value="Motorcycle" <?php echo $vehicle_type == 'Motorcycle' ? 'selected' : ''; ?>>Xe máy</option>
                        <option value="Truck" <?php echo $vehicle_type == 'Truck' ? 'selected' : ''; ?>>Xe tải</option>
                        <option value="Bus" <?php echo $vehicle_type == 'Bus' ? 'selected' : ''; ?>>Xe khách</option>
                        <option value="Other" <?php echo $vehicle_type == 'Other' ? 'selected' : ''; ?>>Khác</option>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <div style="width: 140px;" class="text-end">
                <a href="manage_vehicles.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt me-1"></i> Làm mới
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>STT</th>
                        <th>Biển số xe</th>
                        <th>Chủ phương tiện</th>
                        <th>Loại phương tiện</th>
                        <th>Số lượng vi phạm</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($vehicles) > 0): ?>
                        <?php foreach ($vehicles as $index => $vehicle): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vehicle['owner_name']); ?></td>
                                <td>
                                    <?php
                                    $vehicle_type_map = [
                                        'Car' => '<i class="fas fa-car"></i> Ô tô',
                                        'Motorcycle' => '<i class="fas fa-motorcycle"></i> Xe máy',
                                        'Truck' => '<i class="fas fa-truck"></i> Xe tải',
                                        'Bus' => '<i class="fas fa-bus"></i> Xe khách',
                                        'Other' => '<i class="fas fa-car-side"></i> Khác'
                                    ];
                                    echo $vehicle_type_map[$vehicle['vehicle_type']] ?? $vehicle['vehicle_type'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($vehicle['violation_count'] > 0): ?>
                                        <a href="manage_violations.php?search=<?php echo urlencode($vehicle['license_plate']); ?>" class="badge bg-danger text-decoration-none">
                                            <?php echo $vehicle['violation_count']; ?> vi phạm
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Không vi phạm</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($vehicle['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_vehicle_form.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="add_violation.php?license_plate=<?php echo urlencode($vehicle['license_plate']); ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <?php if ($vehicle['violation_count'] == 0): ?>
                                            <a href="javascript:void(0);" onclick="if(confirm('Bạn có chắc chắn muốn xóa phương tiện này?')) window.location.href='manage_vehicles.php?action=delete&id=<?php echo $vehicle['id']; ?>'" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">Không tìm thấy phương tiện nào</td>
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
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($vehicle_type) ? '&vehicle_type=' . urlencode($vehicle_type) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($vehicle_type) ? '&vehicle_type=' . urlencode($vehicle_type) : ''; ?>">
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
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($vehicle_type) ? '&vehicle_type=' . urlencode($vehicle_type) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($vehicle_type) ? '&vehicle_type=' . urlencode($vehicle_type) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($vehicle_type) ? '&vehicle_type=' . urlencode($vehicle_type) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Thêm CSS tùy chỉnh để cải thiện giao diện -->
<style>
.card-header .d-flex {
    gap: 20px;  /* Khoảng cách giữa các phần tử */
}

.input-group {
    width: 100%;
}

.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    border-color: #86b7fe;
}
</style>

<?php include_once 'layout/footer.php'; ?>