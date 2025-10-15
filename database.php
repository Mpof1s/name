<?php
// database.php - Подключение и работа с базой данных

require_once 'config.php';

class Database {
    private $pdo;
    private static $instance = null;
    
    // Приватный конструктор (singleton pattern)
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            // Устанавливаем атрибуты PDO
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (PDOException $e) {
            // Логируем ошибку, но не показываем пользователю
            error_log("Database connection error: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            } else {
                die("Ошибка подключения к серверу. Пожалуйста, попробуйте позже.");
            }
        }
    }
    
    // Получение экземпляра класса (singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Получение PDO соединения
    public function getConnection() {
        return $this->pdo;
    }
    
    // Выполнение запроса с подготовленными statement
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
    
    // Получение одной записи
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Получение всех записей
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Вставка записи и возврат ID
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    // Проверка существования записи
    public function exists($table, $conditions) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $where);
        $result = $this->fetch($sql, $params);
        
        return $result['count'] > 0;
    }
}

// Создаем глобальное подключение
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Ошибка инициализации базы данных");
}
?>