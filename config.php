<?php
// config.php - Основные настройки приложения

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskhub_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки сессии
session_set_cookie_params([
    'lifetime' => 86400, // 24 часа
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false,    // true для HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Базовые пути
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
define('BASE_PATH', __DIR__);

// Настройки загрузки файлов
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Режим разработки (true - показывать ошибки, false - скрывать)
define('DEBUG_MODE', true);

// Настройка отображения ошибок
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Функция редиректа
function redirect($url) {
    header("Location: $url");
    exit();
}

// CSRF защита
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>