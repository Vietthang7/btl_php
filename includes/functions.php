<?php
/**
 * Các function tiện ích cho hệ thống
 * File này chứa các function được sử dụng trên toàn bộ hệ thống
 */

/**
 * Hiển thị flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
        $icon = '';
        
        switch ($type) {
            case 'success':
                $icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
                $icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'warning':
                $icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            default:
                $icon = '<i class="fas fa-info-circle me-2"></i>';
                break;
        }
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $icon . $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Xóa flash message sau khi hiển thị
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Tạo flash message
 * 
 * @param string $message Nội dung message
 * @param string $type Loại message (success, info, warning, danger)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Định dạng số tiền
 * 
 * @param float $amount Số tiền cần định dạng
 * @return string Chuỗi số tiền đã định dạng
 */
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

/**
 * Định dạng ngày tháng
 * 
 * @param string $date Chuỗi ngày tháng
 * @param bool $showTime Có hiển thị giờ hay không
 * @return string Chuỗi ngày tháng đã định dạng
 */
function formatDate($date, $showTime = false) {
    if (!$date) return '';
    
    $format = $showTime ? 'd/m/Y H:i' : 'd/m/Y';
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

/**
 * Kiểm tra và yêu cầu login
 * Redirect to login page if user is not logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setFlashMessage('Bạn cần đăng nhập để tiếp tục', 'warning');
        header('Location: login.php');
        exit;
    }
}

/**
 * Sanitize input để tránh XSS
 * 
 * @param string $input Chuỗi input cần sanitize
 * @return string Chuỗi đã được sanitize
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate biển số xe
 * 
 * @param string $licensePlate Biển số xe
 * @return bool True nếu hợp lệ, false nếu không hợp lệ
 */
function validateLicensePlate($licensePlate) {
    // Mẫu regex cho biển số xe Việt Nam
    // Ví dụ: 29A-12345, 30F-123.45
    $pattern = '/^[0-9]{2}[A-Z]-[0-9]{4,5}$/';
    return preg_match($pattern, $licensePlate);
}

/**
 * Tạo slug từ chuỗi tiếng Việt
 * 
 * @param string $string Chuỗi cần tạo slug
 * @return string Chuỗi slug
 */
function createSlug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#([^a-z0-9]+)#',
    );
    
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        '-',
    );
    
    $string = strtolower($string);
    $string = preg_replace($search, $replace, $string);
    $string = preg_replace('/(-)+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Kiểm tra CSRF token để bảo vệ form
 * 
 * @return bool True nếu token hợp lệ, false nếu không hợp lệ
 */
function validateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token'])) {
        return false;
    }
    
    if ($_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Tạo CSRF token 
 * 
 * @return string Token đã được tạo
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Hiển thị input token CSRF trong form
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Log lỗi vào file
 * 
 * @param string $message Nội dung lỗi
 * @param string $level Mức độ lỗi (error, info, warning)
 */
function logError($message, $level = 'error') {
    $logFile = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';
    $dir = dirname($logFile);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level]: $message" . PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}

/**
 * Kiểm tra quyền admin
 * 
 * @return bool True nếu user có quyền admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect với flash message
 * 
 * @param string $url URL cần redirect
 * @param string $message Nội dung thông báo
 * @param string $type Loại thông báo
 */
function redirectWithMessage($url, $message, $type = 'info') {
    setFlashMessage($message, $type);
    header("Location: $url");
    exit;
}

/**
 * Kiểm tra request là AJAX hay không
 * 
 * @return bool True nếu là AJAX request
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Caching dữ liệu tĩnh
 */
function outputCache($filename, $content, $expiration = 3600) {
    $cacheDir = __DIR__ . '/../cache/';
    
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . md5($filename) . '.cache';
    file_put_contents($cacheFile, $content);
    
    // Set expiration time
    touch($cacheFile, time() + $expiration);
}

/**
 * Lấy dữ liệu từ cache
 */
function getCache($filename) {
    $cacheDir = __DIR__ . '/../cache/';
    $cacheFile = $cacheDir . md5($filename) . '.cache';
    
    if (file_exists($cacheFile) && (filemtime($cacheFile) > time())) {
        return file_get_contents($cacheFile);
    }
    
    return false;
}