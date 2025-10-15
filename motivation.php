<?php
// motivation.php - Мотивационная доска
require_once 'config.php';
require_once 'functions.php';

// Проверяем авторизацию
require_login();

// Получаем данные текущего пользователя
$user = get_current_user_data();
if (!$user) {
    session_destroy();
    redirect('auth.php');
}

// Получаем мотивационные сообщения
try {
    global $pdo;
    
    $sql = "SELECT m.*, u.first_name, u.last_name 
            FROM motivations m 
            LEFT JOIN users u ON m.user_id = u.id 
            ORDER BY m.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $motivations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $motivations = [];
}

// Обработка добавления сообщения
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_motivation'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Ошибка безопасности';
    } else {
        $text = sanitize($_POST['text'] ?? '');
        
        if (empty($text)) {
            $errors[] = 'Сообщение не может быть пустым';
        } elseif (strlen($text) > 500) {
            $errors[] = 'Сообщение слишком длинное (максимум 500 символов)';
        } else {
            try {
                $sql = "INSERT INTO motivations (user_id, text) VALUES (:user_id, :text)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':text' => $text
                ]);
                
                $success = 'Сообщение добавлено!';
                redirect('motivation.php'); // Перезагружаем страницу
                
            } catch (PDOException $e) {
                error_log("Motivation creation error: " . $e->getMessage());
                $errors[] = 'Ошибка при добавлении сообщения';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHub - Мотивация</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">TaskHub</a>
            <nav class="nav">
                <a href="index.php" class="nav-link">Мои задачи</a>
                <a href="board.php" class="nav-link">Доска</a>
                <a href="projects.php" class="nav-link">Проекты</a>
                <a href="motivation.php" class="nav-link active">Мотивация</a>
            </nav>
            <a href="profile.php" class="user-profile">
                <img src="images/avatar-placeholder.png" alt="Профиль" class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($user['first_name']); ?></span>
            </a>
            <button class="menu-toggle" aria-label="Открыть меню">
                <i class='bx bx-menu'></i>
            </button>
        </div>
    </header>
<!-- Мобильное меню -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-content">
        <div class="mobile-menu-header">
            <h3>Меню</h3>
            <button class="mobile-menu-close">&times;</button>
        </div>
        
        <nav class="mobile-nav">
            <a href="index.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <i class='bx bx-task'></i>
                Мои задачи
            </a>
            <a href="board.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'board.php' ? 'active' : ''; ?>">
                <i class='bx bx-board'></i>
                Доска
            </a>
            <a href="projects.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'projects.php' ? 'active' : ''; ?>">
                <i class='bx bx-folder'></i>
                Проекты
            </a>
            <a href="motivation.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'motivation.php' ? 'active' : ''; ?>">
                <i class='bx bx-trophy'></i>
                Мотивация
            </a>
        </nav>
        
        <div class="mobile-user-section">
            <?php if (isset($user) && $user): ?>
                <a href="profile.php" class="mobile-user-profile">
                    <img src="images/avatar-placeholder.png" alt="Профиль" class="mobile-user-avatar">
                    <div class="mobile-user-info">
                        <div class="mobile-user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="mobile-user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </a>
                <a href="logout.php" class="mobile-nav-link">
                    <i class='bx bx-log-out'></i>
                    Выйти
                </a>
            <?php else: ?>
                <div class="mobile-auth-buttons">
                    <a href="auth.php?action=login" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class='bx bx-log-in'></i>
                        Войти
                    </a>
                    <a href="auth.php?action=register" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                        <i class='bx bx-user-plus'></i>
                        Регистрация
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Мотивация ✨</h1>
                <button class="btn btn-primary" id="addMotivationBtn">
                    <i class='bx bx-plus'></i> Добавить мотивацию
                </button>
            </div>

            <!-- Вывод сообщений -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <?php endif; ?>

            <!-- Форма добавления мотивации -->
            <div class="motivation-form" id="motivationForm" style="display: none;">
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="add_motivation" value="1">
                        
                        <textarea 
                            id="motivationText" 
                            name="text" 
                            placeholder="Поделитесь мотивационной мыслью, цитатой или советом... Не забывайте про смайлики! 😊🚀🌟" 
                            rows="4"
                            maxlength="500"
                            class="motivation-textarea"
                            oninput="updateCharCount()"
                        ></textarea>
                        
                        <div class="form-controls">
                            <div class="char-counter">
                                <span id="charCount">0</span>/500 символов
                            </div>
                            <div class="form-buttons">
                                <button type="button" class="btn btn-secondary" onclick="cancelMotivation()">Отмена</button>
                                <button type="submit" class="btn btn-primary">Опубликовать</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Сетка мотивационных сообщений -->
            <div class="motivation-grid" id="motivationGrid">
                <?php if (empty($motivations)): ?>
                    <div class="empty-state">
                        <i class='bx bx-message-rounded'></i>
                        <h3>Мотивационных сообщений пока нет</h3>
                        <p>Будьте первым, кто поделится мотивацией!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($motivations as $motivation): ?>
                    <div class="motivation-card">
                        <div class="motivation-content">
                            <p class="motivation-text"><?php echo nl2br(htmlspecialchars($motivation['text'])); ?></p>
                        </div>
                        <div class="motivation-meta">
                            <span class="motivation-author"><?php echo htmlspecialchars($motivation['first_name'] . ' ' . $motivation['last_name']); ?></span>
                            <span class="motivation-date">• <?php echo date('d.m.Y H:i', strtotime($motivation['created_at'])); ?></span>
                            
                            <?php if ($motivation['user_id'] == $user['id']): ?>
                            <span class="motivation-actions">
                                <button class="btn-icon" onclick="editMotivation(<?php echo $motivation['id']; ?>)" title="Редактировать">
                                    <i class='bx bx-edit'></i>
                                </button>
                                <button class="btn-icon" onclick="deleteMotivation(<?php echo $motivation['id']; ?>)" title="Удалить">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($motivation['likes'] > 0): ?>
                        <div class="motivation-likes">
                            <i class='bx bx-heart'></i>
                            <span><?php echo $motivation['likes']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 TaskHub.</p>
        </div>
    </footer>

    <script>
    // JavaScript для мотивации
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Motivation page loaded');
        
        const addBtn = document.getElementById('addMotivationBtn');
        const motivationForm = document.getElementById('motivationForm');
        const textarea = document.getElementById('motivationText');
        const charCount = document.getElementById('charCount');
        
        // Открытие формы
        if (addBtn && motivationForm) {
            addBtn.addEventListener('click', function() {
                motivationForm.style.display = 'block';
                addBtn.style.display = 'none';
                
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            });
        }
        
        // Обновление счетчика символов
        window.updateCharCount = function() {
            if (textarea && charCount) {
                const length = textarea.value.length;
                charCount.textContent = length;
                
                if (length > 450) {
                    charCount.style.color = '#DC2626';
                } else if (length > 400) {
                    charCount.style.color = '#F59E0B';
                } else {
                    charCount.style.color = '#6B7280';
                }
            }
        };
        
        // Отмена добавления
        window.cancelMotivation = function() {
            if (motivationForm) {
                motivationForm.style.display = 'none';
                addBtn.style.display = 'flex';
            }
            
            if (textarea) {
                textarea.value = '';
            }
            
            if (charCount) {
                charCount.textContent = '0';
                charCount.style.color = '#6B7280';
            }
        };
        
        // Инициализация счетчика
        if (charCount) {
            charCount.textContent = '0';
        }
    });
    
    // Функции для действий с сообщениями
    function editMotivation(motivationId) {
        alert('Редактирование сообщения ' + motivationId + ' будет доступно в следующей версии');
    }
    
    function deleteMotivation(motivationId) {
        if (confirm('Вы уверены, что хотите удалить это сообщение?')) {
            alert('Удаление сообщения ' + motivationId + ' будет доступно в следующей версии');
        }
    }
    
    function likeMotivation(motivationId) {
        alert('Лайк сообщения ' + motivationId + ' будет доступен в следующей версии');
    }
    </script>
    <!-- Подключаем систему уведомлений -->
    <script src="script.js"></script>
<script src="notifications.js"></script>

<!-- Инициализируем уведомления из PHP -->
<?php if (!empty($success)): ?>
<script>
    notifications.success('<?php echo addslashes($success); ?>');
</script>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<script>
    <?php foreach ($errors as $error): ?>
        notifications.error('<?php echo addslashes($error); ?>');
    <?php endforeach; ?>
</script>
<?php endif; ?>
</body>
</html>