<?php
// index.php - Главная страница с задачами
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


// Получаем параметры фильтрации
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';

// Подготовка условий для SQL
$where_conditions = ["user_id = :user_id", "type = 'personal'"];
$params = [':user_id' => $user['id']];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "priority = :priority";
    $params[':priority'] = $priority_filter;
}

$where_sql = implode(' AND ', $where_conditions);

// Получаем задачи из базы
try {
    global $pdo;
    
    // Получаем задачи
    $sql = "SELECT * FROM tasks WHERE $where_sql ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    // Получаем проекты для фильтра
    $projects_sql = "SELECT * FROM projects WHERE created_by = :user_id ORDER BY name";
    $projects_stmt = $pdo->prepare($projects_sql);
    $projects_stmt->execute([':user_id' => $user['id']]);
    $projects = $projects_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tasks = [];
    $projects = [];
}

// Обработка добавления новой задачи
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Ошибка безопасности';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $project_id = sanitize($_POST['project_id'] ?? null);
        
        if (empty($title)) {
            $errors[] = 'Название задачи обязательно';
        } else {
            try {
                $sql = "INSERT INTO tasks (user_id, type, title, description, priority, deadline, project_id) 
        VALUES (:user_id, 'personal', :title, :description, :priority, :deadline, :project_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':title' => $title,
                    ':description' => $description,
                    ':priority' => $priority,
                    ':deadline' => $deadline ?: null,
                    ':project_id' => $project_id ?: null
                ]);
                
                $success = 'Задача успешно добавлена!';
                redirect('index.php'); // Перезагружаем страницу
                
            } catch (PDOException $e) {
                error_log("Task creation error: " . $e->getMessage());
                $errors[] = 'Ошибка при добавлении задачи';
            }
        }
    }
}

// Обработка изменения статуса задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if ($task_id && $new_status) {
        try {
            $sql = "UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $task_id,
                ':user_id' => $user['id']
            ]);
            
            // Возвращаем JSON ответ для AJAX
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
    <title>TaskHub - Мои задачи</title>
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
                <a href="index.php" class="nav-link active">Мои задачи</a>
                <a href="board.php" class="nav-link">Доска</a>
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
                <h1 class="page-title">Мои задачи</h1>
                <button class="btn btn-primary" id="addTaskBtn">
                    <i class='bx bx-plus'></i> Добавить задачу
                </button>
            </div>
            
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

            <div class="filters">
                <div class="filter-group">
                    <label for="statusFilter">Статус:</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все</option>
                        <option value="planned" <?php echo $status_filter === 'planned' ? 'selected' : ''; ?>>Запланированы</option>
                        <option value="progress" <?php echo $status_filter === 'progress' ? 'selected' : ''; ?>>В работе</option>
                        <option value="done" <?php echo $status_filter === 'done' ? 'selected' : ''; ?>>Выполнены</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="priorityFilter">Приоритет:</label>
                    <select id="priorityFilter" class="filter-select">
                        <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>Все</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>Высокий</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Средний</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Низкий</option>
                    </select>
                </div>
                
                <button class="btn btn-secondary" id="applyFilters">
                    <i class='bx bx-filter'></i> Применить
                </button>
            </div>
            <div class="tasks-list" id="tasksList">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <i class='bx bx-task'></i>
                        <h3>Задач пока нет</h3>
                        <p>Создайте свою первую задачу</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <div class="task-card" data-priority="<?php echo $task['priority']; ?>" data-status="<?php echo $task['status']; ?>" data-task-id="<?php echo $task['id']; ?>">
                        <div class="task-checkbox">
                            <input type="checkbox" id="task-<?php echo $task['id']; ?>" 
                                <?php echo $task['status'] === 'done' ? 'checked' : ''; ?>
                                onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.checked)">
                            <label for="task-<?php echo $task['id']; ?>"></label>
                        </div>
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
                                <?php if ($task['project_id']): ?>
                                    <span class="task-project">
                                        <?php 
                                        $project_name = 'Проект';
                                        foreach ($projects as $project) {
                                            if ($project['id'] == $task['project_id']) {
                                                $project_name = $project['name'];
                                                break;
                                            }
                                        }
                                        echo htmlspecialchars($project_name);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="btn-icon" title="Редактировать" onclick="editTask(<?php echo $task['id']; ?>)">
                                <i class='bx bx-edit'></i>
                            </button>
                            <button class="btn-icon" title="Удалить" onclick="deleteTask(<?php echo $task['id']; ?>)">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Модальное окно добавления задачи -->
   <!-- Модальное окно добавления задачи -->
<div class="modal" id="taskModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Добавить новую задачу</h3>
            <button class="modal-close" id="modalClose">&times;</button>
        </div>
        <form id="taskForm" method="POST" onsubmit="return validateTaskForm()">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="add_task" value="1">
            <input type="hidden" name="type" value="personal">
            
            <div class="form-group">
                <label for="taskTitle">Название задачи *</label>
                <input type="text" id="taskTitle" name="title" required placeholder="Введите название задачи" class="modal-input">
                <span class="error-message" id="titleError"></span>
            </div>
            
            <div class="form-group">
                <label for="taskDescription">Описание</label>
                <textarea id="taskDescription" name="description" rows="3" placeholder="Описание задачи (необязательно)" class="modal-textarea"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="taskPriority">Приоритет</label>
                    <select id="taskPriority" name="priority" class="modal-select">
                        <option value="high">Высокий</option>
                        <option value="medium" selected>Средний</option>
                        <option value="low">Низкий</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="taskDeadline">Срок выполнения</label>
                    <input type="date" id="taskDeadline" name="deadline" class="modal-input">
                </div>
            </div>
            
            <?php if (!empty($projects)): ?>
            <div class="form-group">
                <label for="taskProject">Проект</label>
                <select id="taskProject" name="project_id" class="modal-select">
                    <option value="">Без проекта</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelTask">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить задачу</button>
            </div>
        </form>
    </div>
</div>

    <footer class="footer">
        <div class="container">
            <p>© 2025 TaskHub.</p>
        </div>
    </footer>

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