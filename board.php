<?php
// board.php - Личная канбан-доска
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

// Получаем задачи пользователя
try {
    global $pdo;
    
    // Только личные задачи
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id AND type = 'personal' ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user['id']]);
    $tasks = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tasks = [];
}

// Группируем задачи по статусу
$tasks_by_status = [
    'planned' => [],
    'progress' => [],
    'done' => []
];

foreach ($tasks as $task) {
    $tasks_by_status[$task['status']][] = $task;
}

// Обработка изменения статуса через drag&drop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_status'])) {
    $task_id = $_POST['task_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if ($task_id && $new_status && in_array($new_status, ['planned', 'progress', 'done'])) {
        try {
            $sql = "UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $task_id,
                ':user_id' => $user['id']
            ]);
            
            // JSON ответ для AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                json_response(['success' => true]);
            }
            
        } catch (PDOException $e) {
            error_log("Status update error: " . $e->getMessage());
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                json_response(['success' => false, 'error' => 'Ошибка обновления'], 500);
            }
        }
    }
    exit;
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHub - Доска задач</title>
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
                <a href="board.php" class="nav-link active">Доска</a>
                <a href="projects.php" class="nav-link">Проекты</a>
                <a href="motivation.php" class="nav-link">Мотивация</a>
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
                <h1 class="page-title">Доска задач</h1>
                <a href="index.php" class="btn btn-primary">
                    <i class='bx bx-plus'></i> Добавить задачу
                </a>
            </div>
            <div class="kanban-board" id="kanbanBoard">
            
                <div class="kanban-column">
                    <div class="column-header">
                        <h2 class="column-title">В плане</h2>
                        <span class="column-counter"><?php echo count($tasks_by_status['planned']); ?></span>
                    </div>
                    <div class="column-content" data-status="planned">
                        <?php foreach ($tasks_by_status['planned'] as $task): ?>
                        <div class="task-card" draggable="true" data-task-id="<?php echo $task['id']; ?>">
                            <div class="task-content">
                                <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                        <?php 
                                        $priority_names = ['high' => 'Высокий', 'medium' => 'Средний', 'low' => 'Низкий'];
                                        echo $priority_names[$task['priority']];
                                        ?>
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="task-deadline">
                                            <i class='bx bx-calendar'></i> 
                                            <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Колонка: В работе -->
                <div class="kanban-column">
                    <div class="column-header">
                        <h2 class="column-title">В работе</h2>
                        <span class="column-counter"><?php echo count($tasks_by_status['progress']); ?></span>
                    </div>
                    <div class="column-content" data-status="progress">
                        <?php foreach ($tasks_by_status['progress'] as $task): ?>
                        <div class="task-card" draggable="true" data-task-id="<?php echo $task['id']; ?>">
                            <div class="task-content">
                                <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                        <?php echo $priority_names[$task['priority']]; ?>
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="task-deadline">
                                            <i class='bx bx-calendar'></i> 
                                            <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Колонка: Готово -->
                <div class="kanban-column">
                    <div class="column-header">
                        <h2 class="column-title">Готово</h2>
                        <span class="column-counter"><?php echo count($tasks_by_status['done']); ?></span>
                    </div>
                    <div class="column-content" data-status="done">
                        <?php foreach ($tasks_by_status['done'] as $task): ?>
                        <div class="task-card" draggable="true" data-task-id="<?php echo $task['id']; ?>">
                            <div class="task-content">
                                <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                        <?php echo $priority_names[$task['priority']]; ?>
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="task-deadline">
                                            <i class='bx bx-calendar'></i> 
                                            <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 TaskHub.</p>
        </div>
    </footer>

    <script src="board.js"></script>
    <script src="script.js"></script>
    <!-- Подключаем систему уведомлений -->
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
