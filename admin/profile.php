<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Kiểm tra đăng nhập
requireLogin();

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    
    if (empty($full_name)) {
        $error = "Vui lòng nhập họ tên đầy đủ";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET full_name = :full_name WHERE id = :id");
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            // Cập nhật session
            $_SESSION['full_name'] = $full_name;
            
            $success = true;
            $message = "Cập nhật thông tin thành công!";
            
            // Cập nhật thông tin người dùng sau khi cập nhật
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật thông tin: " . $e->getMessage();
        }
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ thông tin mật khẩu";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Mật khẩu hiện tại không đúng";
    } elseif ($new_password != $confirm_password) {
        $error = "Mật khẩu mới không khớp";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $success = true;
            $message = "Đổi mật khẩu thành công!";
        } catch (PDOException $e) {
            $error = "Lỗi khi đổi mật khẩu: " . $e->getMessage();
        }
    }
}

$pageTitle = "Hồ sơ cá nhân";
include_once 'layout/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Hồ sơ cá nhân</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thông tin cá nhân</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user['username']); ?></p>
                <p>
                    <span class="badge bg-success">Quản trị viên</span>
                </p>
                <p>
                    <small class="text-muted">Tài khoản tạo lúc: <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></small>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Cập nhật thông tin</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                        <div class="form-text text-muted">Tên đăng nhập không thể thay đổi.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ tên đầy đủ</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-primary">Cập nhật thông tin</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Đổi mật khẩu</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-danger">Đổi mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>