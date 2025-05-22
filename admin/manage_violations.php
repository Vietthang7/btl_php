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

// Lấy danh sách vi phạm để hiển thị ban đầu
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
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center" style="gap: 20px;">
            <div style="width: 250px;">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" id="searchBtn" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div style="width: 250px;">
                <select class="form-select" id="statusFilter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="Unpaid" <?php echo $status_filter == 'Unpaid' ? 'selected' : ''; ?>>Chưa nộp phạt</option>
                    <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Đã nộp phạt</option>
                </select>
            </div>
            
            <div style="width: 140px;" class="text-end">
                <button id="refreshBtn" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt me-1"></i> Làm mới
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0 position-relative">
        <!-- Loading overlay -->
        <div id="loadingOverlay" class="position-absolute w-100 h-100 d-none">
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>STT</th>
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
                <tbody id="violationsTableBody">
                    <?php if (count($violations) > 0): ?>
                        <?php foreach ($violations as $index => $violation): ?>
                            <tr class="result-row">
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
                                        <ul class="dropdown-menu dropdown-menu-end">
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
                        <?php for ($i = count($violations); $i < $limit; $i++): ?>
                            <tr class="placeholder-row" style="height: 53px;"><td colspan="9"></td></tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">Không tìm thấy vi phạm nào</td>
                        </tr>
                        <?php for ($i = 1; $i < $limit; $i++): ?>
                            <tr class="placeholder-row" style="height: 53px;"><td colspan="9"></td></tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white" id="paginationContainer">
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="javascript:void(0);" data-page="1">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="javascript:void(0);" data-page="<?php echo $page - 1; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="javascript:void(0);">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link" href="javascript:void(0);">
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
                            <a class="page-link page-link-ajax" href="javascript:void(0);" data-page="<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="javascript:void(0);" data-page="<?php echo $page + 1; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-ajax" href="javascript:void(0);" data-page="<?php echo $total_pages; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="javascript:void(0);">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link" href="javascript:void(0);">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<style>
/* CSS để làm mượt việc tìm kiếm AJAX */
.card-body {
    position: relative;
    min-height: 570px; /* Đảm bảo chiều cao bảng luôn nhất quán */
}

#loadingOverlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.7);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.3s ease;
}

.placeholder-row {
    opacity: 0;
    height: 53px; /* Chiều cao cố định cho mỗi hàng */
}

.result-row {
    animation: fadeIn 0.3s ease forwards;
    transition: background-color 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Tinh chỉnh giao diện phân trang */
.page-link {
    cursor: pointer;
    transition: all 0.2s ease;
}

.page-link:active {
    transform: scale(0.95);
}

/* Làm cho select box và input tìm kiếm trông đồng nhất */
.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Cải thiện độ tương phản cho các badge */
.badge.bg-warning {
    color: #000;
}
</style>

<!-- JavaScript cho tìm kiếm Ajax -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lưu trữ chiều cao bảng ban đầu
    const tableBodyHeight = document.getElementById('violationsTableBody').offsetHeight;
    
    // Các biến chung
    let currentPage = <?php echo $page; ?>;
    let searchTimer;
    let isLoading = false;
    
    // Hàm fetch dữ liệu AJAX
    function fetchViolations(page = 1) {
        if (isLoading) return;
        isLoading = true;
        
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        
        // Giữ nguyên các hàng nhưng làm mờ đi
        const tableRows = document.querySelectorAll('#violationsTableBody tr');
        tableRows.forEach(row => {
            row.style.opacity = '0.3';
        });
        
        // Hiển thị overlay loading
        document.getElementById('loadingOverlay').classList.remove('d-none');
        
        // Fetch dữ liệu từ API
        fetch(`ajax_search_violations.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                // Cập nhật nội dung bảng nhưng mà không thay đổi chiều cao
                let tableContent = data.html;
                
                // Thêm các hàng trống để duy trì chiều cao của bảng
                if (data.total > 0) {
                    const rowCount = tableContent.split('<tr class="result-row">').length - 1;
                    if (rowCount < data.limit) {
                        for (let i = rowCount; i < data.limit; i++) {
                            tableContent += '<tr class="placeholder-row" style="height: 53px;"><td colspan="9"></td></tr>';
                        }
                    }
                } else {
                    tableContent = '<tr><td colspan="9" class="text-center py-4">Không tìm thấy vi phạm nào</td></tr>';
                    for (let i = 1; i < data.limit; i++) {
                        tableContent += '<tr class="placeholder-row" style="height: 53px;"><td colspan="9"></td></tr>';
                    }
                }
                
                document.getElementById('violationsTableBody').innerHTML = tableContent;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                
                // Cập nhật trang hiện tại
                currentPage = data.page;
                
                // Gắn sự kiện cho các nút phân trang mới
                attachPaginationEvents();
                
                // Khởi tạo lại các dropdown và modal của Bootstrap
                initializeBootstrapComponents();
                
                // Cập nhật URL với tham số tìm kiếm (để có thể bookmark hoặc chia sẻ kết quả tìm kiếm)
                const url = new URL(window.location);
                url.searchParams.set('search', search);
                url.searchParams.set('status', status);
                url.searchParams.set('page', page);
                history.pushState({}, '', url);
                
                // Ẩn overlay loading sau một chút delay để transition mượt mà hơn
                setTimeout(() => {
                    document.getElementById('loadingOverlay').classList.add('d-none');
                    isLoading = false;
                }, 300);
            })
            .catch(error => {
                console.error('Lỗi khi tải dữ liệu:', error);
                document.getElementById('violationsTableBody').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Đã xảy ra lỗi khi tải dữ liệu. Vui lòng thử lại.</td></tr>';
                document.getElementById('loadingOverlay').classList.add('d-none');
                isLoading = false;
            });
    }
    
    // Khởi tạo lại các dropdown và modal Bootstrap
    function initializeBootstrapComponents() {
        // Khởi tạo lại dropdowns
        document.querySelectorAll('.dropdown-toggle').forEach(dropdownToggle => {
            new bootstrap.Dropdown(dropdownToggle);
        });
        
        // Khởi tạo lại modals
        document.querySelectorAll('.modal').forEach(modalEl => {
            new bootstrap.Modal(modalEl);
        });
    }
    
    // Gắn sự kiện cho các nút phân trang
    function attachPaginationEvents() {
        const pageLinks = document.querySelectorAll('.page-link-ajax');
        pageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (isLoading) return;
                
                const page = parseInt(this.getAttribute('data-page'));
                fetchViolations(page);
            });
        });
    }
    
    // Xử lý sự kiện tìm kiếm khi nhập
    document.getElementById('searchInput').addEventListener('input', function() {
        // Sử dụng debounce để tránh gửi quá nhiều yêu cầu AJAX
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            fetchViolations(1); // Reset về trang 1 khi tìm kiếm
        }, 500);
    });
    
    // Xử lý sự kiện nhấn nút tìm kiếm
    document.getElementById('searchBtn').addEventListener('click', function() {
        fetchViolations(1); // Reset về trang 1 khi tìm kiếm
    });
    
    // Xử lý sự kiện khi thay đổi bộ lọc trạng thái
    document.getElementById('statusFilter').addEventListener('change', function() {
        fetchViolations(1); // Reset về trang 1 khi thay đổi bộ lọc
    });
    
    // Xử lý sự kiện nhấn nút làm mới
    document.getElementById('refreshBtn').addEventListener('click', function() {
        // Visual feedback khi nhấn nút
        this.classList.add('active');
        setTimeout(() => this.classList.remove('active'), 200);
        
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        fetchViolations(1);
    });
    
    // Xử lý sự kiện nhấn Enter trong ô tìm kiếm
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            fetchViolations(1);
        }
    });
    
    // Gắn sự kiện cho các nút phân trang ban đầu
    attachPaginationEvents();
});
</script>

<?php include_once 'layout/footer.php'; ?>