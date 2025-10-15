<?php
// functions.php - Общие вспомогательные функции

require_once 'database.php';

// Санитизация данных
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    if (is_string($data)) {
        $data = trim($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

// Валидация email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Проверка авторизации
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Получение текущего пользователя (переименовано!)
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

// Редирект если не авторизован
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('auth.php');
    }
}

// Хеширование пароля
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Проверка пароля
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Генерация случайного токена
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Форматирование даты
function format_date($date, $format = 'd.m.Y') {
    if (!$date) return 'Нет срока';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

// Получение значения из массива с дефолтным значением
function get_value($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Валидация длины строки
function validate_length($string, $min = 0, $max = 255) {
    $length = mb_strlen($string);
    return $length >= $min && $length <= $max;
}

// Проверка загружаемого файла
function validate_file($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mime_type, ALLOWED_TYPES);
}

// Загрузка файла
function upload_file($file, $destination) {
    if (!validate_file($file)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Вывод JSON ответа
function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Получение текущего URL
function current_url() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
?>
