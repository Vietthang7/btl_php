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
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="fas fa-plus"></i> Thêm phương tiện
            </button>
        </div>
    </div>
</div>

<?php displayFlashMessage(); ?>

<div class="card mb-4">
    <div class="card-header bg-white">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm biển số, chủ xe..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="vehicle_type" onchange="this.form.submit()">
                    <option value="">Tất cả loại phương tiện</option>
                    <option value="Car" <?php echo $vehicle_type == 'Car' ? 'selected' : ''; ?>>Ô tô</option>
                    <option value="Motorcycle" <?php echo $vehicle_type == 'Motorcycle' ? 'selected' : ''; ?>>Xe máy</option>
                    <option value="Truck" <?php echo $vehicle_type == 'Truck' ? 'selected' : ''; ?>>Xe tải</option>
                    <option value="Bus" <?php echo $vehicle_type == 'Bus' ? 'selected' : ''; ?>>Xe khách</option>
                    <option value="Other" <?php echo $vehicle_type == 'Other' ? 'selected' : ''; ?>>Khác</option>
                </select>
            </div>
            <div class="col-md-5 text-end">
                <a href="manage_vehicles.php" class="btn btn-outline-secondary">
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editVehicleModal<?php echo $vehicle['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="add_violation.php?license_plate=<?php echo urlencode($vehicle['license_plate']); ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <?php if ($vehicle['violation_count'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#deleteVehicleModal<?php echo $vehicle['id']; ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Modal Sửa phương tiện -->
                                    <div class="modal fade" id="editVehicleModal<?php echo $vehicle['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Sửa thông tin phương tiện</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="edit_vehicle.php" method="POST">
                                                        <input type="hidden" name="id" value="<?php echo $vehicle['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="license_plate<?php echo $vehicle['id']; ?>" class="form-label">Biển số xe</label>
                                                            <input type="text" class="form-control" id="license_plate<?php echo $vehicle['id']; ?>" name="license_plate" value="<?php echo htmlspecialchars($vehicle['license_plate']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="owner_name<?php echo $vehicle['id']; ?>" class="form-label">Chủ phương tiện</label>
                                                            <input type="text" class="form-control" id="owner_name<?php echo $vehicle['id']; ?>" name="owner_name" value="<?php echo htmlspecialchars($vehicle['owner_name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="vehicle_type<?php echo $vehicle['id']; ?>" class="form-label">Loại phương tiện</label>
                                                            <select class="form-select" id="vehicle_type<?php echo $vehicle['id']; ?>" name="vehicle_type" required>
                                                                <option value="Car" <?php echo $vehicle['vehicle_type'] == 'Car' ? 'selected' : ''; ?>>Ô tô</option>
                                                                <option value="Motorcycle" <?php echo $vehicle['vehicle_type'] == 'Motorcycle' ? 'selected' : ''; ?>>Xe máy</option>
                                                                <option value="Truck" <?php echo $vehicle['vehicle_type'] == 'Truck' ? 'selected' : ''; ?>>Xe tải</option>
                                                                <option value="Bus" <?php echo $vehicle['vehicle_type'] == 'Bus' ? 'selected' : ''; ?>>Xe khách</option>
                                                                <option value="Other" <?php echo $vehicle['vehicle_type'] == 'Other' ? 'selected' : ''; ?>>Khác</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="d-grid">
                                                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Xóa phương tiện -->
                                    <div class="modal fade" id="deleteVehicleModal<?php echo $vehicle['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Xác nhận xóa</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Bạn có chắc chắn muốn xóa phương tiện có biển số <strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong> không?</p>
                                                    <p class="text-danger"><strong>Lưu ý:</strong> Hành động này không thể hoàn tác.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <a href="manage_vehicles.php?action=delete&id=<?php echo $vehicle['id']; ?>" class="btn btn-danger">Xác nhận xóa</a>
                                                </div>
                                            </div>
                                        </div>
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

<!-- Modal Thêm phương tiện mới -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addVehicleModalLabel">Thêm phương tiện mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="add_vehicle.php" method="POST">
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">Biển số xe <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" required placeholder="Nhập biển số xe (VD: 29A-12345)">
                    </div>
                    <div class="mb-3">
                        <label for="owner_name" class="form-label">Chủ phương tiện <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="owner_name" name="owner_name" required placeholder="Nhập tên chủ phương tiện">
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                        <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                            <option value="">-- Chọn loại phương tiện --</option>
                            <option value="Car">Ô tô</option>
                            <option value="Motorcycle">Xe máy</option>
                            <option value="Truck">Xe tải</option>
                            <option value="Bus">Xe khách</option>
                            <option value="Other">Khác</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Thêm phương tiện</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>