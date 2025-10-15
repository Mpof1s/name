<?php
// projects.php - Проекты компании
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

// Получаем все проекты компании
try {
    global $pdo;
    
    // Получаем проекты
    $sql = "SELECT p.*, u.first_name, u.last_name 
            FROM projects p 
            LEFT JOIN users u ON p.created_by = u.id 
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $projects = $stmt->fetchAll();
    
    // Получаем общие задачи (проектные)
    $tasks_sql = "SELECT t.*, p.name as project_name, u.first_name, u.last_name 
                 FROM tasks t 
                 LEFT JOIN projects p ON t.project_id = p.id 
                 LEFT JOIN users u ON t.assigned_to = u.id 
                 WHERE t.type = 'project' 
                 ORDER BY t.created_at DESC";
    $tasks_stmt = $pdo->prepare($tasks_sql);
    $tasks_stmt->execute();
    $project_tasks = $tasks_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $projects = [];
    $project_tasks = [];
}

// Обработка создания нового проекта
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Ошибка безопасности';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Название проекта обязательно';
        } else {
            try {
                $sql = "INSERT INTO projects (name, description, created_by) 
                        VALUES (:name, :description, :user_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':user_id' => $user['id']
                ]);
                
                $success = 'Проект успешно создан!';
                redirect('projects.php'); // Перезагружаем страницу
                
            } catch (PDOException $e) {
                error_log("Project creation error: " . $e->getMessage());
                $errors[] = 'Ошибка при создании проекта';
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
    <title>TaskHub - Проекты</title>
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
                <a href="projects.php" class="nav-link active">Проекты</a>
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
    <h1 class="page-title">Проекты компании</h1>
    <button class="btn btn-primary" id="openProjectModalBtn">
    <i class='bx bx-plus'></i> Новый проект
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

            <!-- Сетка проектов -->
            <div class="projects-grid" id="projectsGrid">
                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <i class='bx bx-folder'></i>
                        <h3>Проектов пока нет</h3>
                        <p>Создайте первый проект для вашей команды</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                    <div class="project-card" data-status="<?php echo $project['status']; ?>">
                        <div class="project-header">
                            <h3 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h3>
                            <span class="project-status status-<?php echo $project['status']; ?>">
                                <?php 
                                $status_names = [
                                    'active' => 'Активный',
                                    'completed' => 'Завершен',
                                    'archived' => 'В архиве'
                                ];
                                echo $status_names[$project['status']];
                                ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($project['description'])): ?>
                            <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="project-stats">
                            <div class="stat">
                                <i class='bx bx-task'></i>
                                <span>
                                    <?php 
                                    $task_count = 0;
                                    foreach ($project_tasks as $task) {
                                        if ($task['project_id'] == $project['id']) {
                                            $task_count++;
                                        }
                                    }
                                    echo $task_count . ' задач';
                                    ?>
                                </span>
                            </div>
                            <div class="stat">
                                <i class='bx bx-user'></i>
                                <span><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></span>
                            </div>
                            <div class="stat">
                                <i class='bx bx-calendar'></i>
                                <span><?php echo date('d.m.Y', strtotime($project['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="project-actions">
                            <button class="btn btn-secondary" onclick="viewProject(<?php echo $project['id']; ?>)">
                                <i class='bx bx-show'></i> Просмотр
                            </button>
                            <button class="btn btn-primary" onclick="editProject(<?php echo $project['id']; ?>)">
                                <i class='bx bx-edit'></i> Редактировать
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Список задач проектов -->
            <div class="section-header">
                <h2>Задачи проектов</h2>
            </div>

            <div class="tasks-list">
                <?php if (empty($project_tasks)): ?>
                    <div class="empty-state">
                        <i class='bx bx-task'></i>
                        <h3>Задач в проектах пока нет</h3>
                        <p>Начните добавлять задачи к проектам</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($project_tasks as $task): ?>
                    <div class="task-card" data-priority="<?php echo $task['priority']; ?>" data-status="<?php echo $task['status']; ?>">
                        <div class="task-content">
                            <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                            <?php if (!empty($task['description'])): ?>
                                <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                            <?php endif; ?>
                            <div class="task-meta">
                                <span class="task-project"><?php echo htmlspecialchars($task['project_name']); ?></span>
                                <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                    <?php 
                                    $priority_names = ['high' => 'Высокий', 'medium' => 'Средний', 'low' => 'Низкий'];
                                    echo $priority_names[$task['priority']];
                                    ?>
                                </span>
                                <?php if ($task['assigned_to']): ?>
                                    <span class="task-assignee">
                                        <i class='bx bx-user'></i>
                                        <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($task['deadline']): ?>
                                    <span class="task-deadline">
                                        <i class='bx bx-calendar'></i> 
                                        <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="task-actions">
                            <span class="task-status status-<?php echo $task['status']; ?>">
                                <?php 
                                $status_names = [
                                    'planned' => 'В плане',
                                    'progress' => 'В работе', 
                                    'done' => 'Готово'
                                ];
                                echo $status_names[$task['status']];
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Модальное окно создания проекта -->
    <div class="modal" id="projectModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Создать новый проект</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="projectForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="create_project" value="1">
                
                <div class="form-group">
                    <label for="projectName">Название проекта *</label>
                    <input type="text" id="projectName" name="name" required placeholder="Введите название проекта">
                </div>
                
                <div class="form-group">
                    <label for="projectDescription">Описание проекта</label>
                    <textarea id="projectDescription" name="description" rows="3" placeholder="Описание проекта (необязательно)"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelProject">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать проект</button>
                </div>
            </form>
        </div>
    </div>
<!-- Модальное окно создания проекта -->
<div class="modal" id="projectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Создать новый проект</h3>
            <button class="modal-close" id="modalClose">&times;</button>
        </div>
        <form id="projectForm" method="POST" class="modal-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="create_project" value="1">
            
            <div class="form-group">
                <label for="projectName">Название проекта *</label>
                <input type="text" id="projectName" name="name" required 
                       placeholder="Введите название проекта" class="modal-input">
            </div>
            
            <div class="form-group">
                <label for="projectDescription">Описание проекта</label>
                <textarea id="projectDescription" name="description" rows="3" 
                          placeholder="Описание проекта (необязательно)" class="modal-textarea"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelProject">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать проект</button>
            </div>
        </form>
    </div>
</div>

<script>
// Простой и понятный JavaScript прямо в HTML
document.addEventListener('DOMContentLoaded', function() {
    console.log('Projects page loaded');
    
    // Элементы
    const openBtn = document.getElementById('openProjectModalBtn');
    const modal = document.getElementById('projectModal');
    const closeBtn = document.getElementById('modalClose');
    const cancelBtn = document.getElementById('cancelProject');
    const form = document.getElementById('projectForm');
    
    // Открытие модального окна
    if (openBtn && modal) {
        openBtn.addEventListener('click', function() {
            console.log('Opening modal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Фокус на поле ввода
            const nameInput = document.getElementById('projectName');
            if (nameInput) {
                setTimeout(() => nameInput.focus(), 100);
            }
        });
    } else {
        console.error('Open button or modal not found');
    }
    
    // Закрытие модального окна
    function closeModal() {
        console.log('Closing modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Очистка формы
            if (form) {
                form.reset();
            }
        }
    }
    
    // Вешаем обработчики закрытия
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    // Закрытие по клику вне окна
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });
    
    // Валидация формы
    if (form) {
        form.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('projectName');
            if (nameInput && !nameInput.value.trim()) {
                e.preventDefault();
                alert('Название проекта обязательно!');
                nameInput.focus();
            }
        });
    }
    
    // Функции для кнопок проектов
    window.viewProject = function(projectId) {
        alert('Просмотр проекта ' + projectId + ' будет доступен позже');
    };
    
    window.editProject = function(projectId) {
        alert('Редактирование проекта ' + projectId + ' будет доступно позже');
    };
    
    window.deleteProject = function(projectId, projectName) {
        if (confirm('Удалить проект "' + projectName + '"?')) {
            alert('Удаление проекта ' + projectId + ' будет доступно позже');
        }
    };
});
</script>
    <footer class="footer">
        <div class="container">
            <p>© 2024 TaskHub. Slade Digital Practice.</p>
        </div>
    </footer>

    <script src="projects.js"></script>
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