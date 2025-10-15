<?php
// profile.php - Добавляем в самое начало файла
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

$errors = [];
$success = '';

// Обработка формы профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Валидация CSRF токена
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу.';
    } else {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $department = sanitize($_POST['department'] ?? 'development');
        $bio = sanitize($_POST['bio'] ?? '');
        
        // Валидация данных
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $errors[] = 'Имя, фамилия и email обязательны для заполнения';
        } elseif (!is_valid_email($email)) {
            $errors[] = 'Неверный формат email';
        } else {
            try {
                global $pdo;
                
                // Проверяем email на уникальность (кроме текущего пользователя)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user['id']]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'Email уже занят другим пользователем';
                } else {
                    // Обновляем профиль
                    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, position = ?, department = ?, bio = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$firstName, $lastName, $email, $position, $department, $bio, $user['id']])) {
                        $success = 'Профиль успешно обновлен!';
                        
                        // Обновляем данные в сессии
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                        
                        // Перезагружаем данные пользователя
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $user = $stmt->fetch();
                    } else {
                        $errors[] = 'Ошибка при обновлении профиля';
                    }
                }
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $errors[] = 'Ошибка сервера. Пожалуйста, попробуйте позже.';
            }
        }
    }
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('auth.php');
}

// Генерация CSRF токена
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHub - Мой профиль</title>
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
                <h1 class="page-title">Мой профиль</h1>
                <a href="profile.php?logout=true" class="btn btn-secondary" onclick="return confirm('Вы уверены, что хотите выйти?')">
                    <i class='bx bx-log-out'></i> Выйти
                </a>
            </div>
            
            <!-- ДОБАВЛЯЕМ ВЫВОД СООБЩЕНИЙ -->
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

            <div class="profile-content">
                <!-- Блок информации о пользователе -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <img src="images/avatar-placeholder.png" alt="Аватар" class="avatar-image" id="avatarImage">
                            <button class="avatar-upload" id="avatarUploadBtn">
                                <i class='bx bx-camera'></i>
                            </button>
                            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        </div>
                        <!-- В блоке с информацией о пользователе -->
                        <div class="profile-info">
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h2>
                            <p class="profile-email">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <p class="profile-role">
                                <?php echo htmlspecialchars($user['position'] ?: 'Пользователь'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number">24</div>
                            <div class="stat-label">Задач выполнено</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">8</div>
                            <div class="stat-label">Активных проектов</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">12</div>
                            <div class="stat-label">Дней в системе</div>
                        </div>
                    </div>
                </div>

                <!-- Форма редактирования профиля -->
                <div class="profile-card">
                    <h3 class="card-title">Редактировать профиль</h3>

                    <form class="profile-form" id="profileForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">Имя</label>
                                <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Фамилия</label>
                                <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    
                        <div class="form-group">
                            <label for="position">Должность</label>
                            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($user['position']); ?>">
                        </div>
                    
                        <div class="form-group">
                            <label for="department">Отдел</label>
                            <select id="department" name="department">
                                <option value="development" <?php echo ($user['department'] === 'development') ? 'selected' : ''; ?>>Разработка</option>
                                <option value="design" <?php echo ($user['department'] === 'design') ? 'selected' : ''; ?>>Дизайн</option>
                                <option value="management" <?php echo ($user['department'] === 'management') ? 'selected' : ''; ?>>Менеджмент</option>
                                <option value="marketing" <?php echo ($user['department'] === 'marketing') ? 'selected' : ''; ?>>Маркетинг</option>
                                <option value="hr" <?php echo ($user['department'] === 'hr') ? 'selected' : ''; ?>>HR</option>
                            </select>
                        </div>
                    
                        <div class="form-group">
                            <label for="bio">О себе</label>
                            <textarea id="bio" name="bio" rows="3" placeholder="Расскажите о себе..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>
                    
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="cancelEdit">Отмена</button>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>

                <!-- Смена пароля -->
                <div class="profile-card">
                    <h3 class="card-title">Смена пароля</h3>

                    <form class="password-form" id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword">Текущий пароль</label>
                            <div class="input-with-icon">
                                <i class='bx bx-lock-alt'></i>
                                <input type="password" id="currentPassword" required>
                                <button type="button" class="toggle-password">
                                    <i class='bx bx-hide'></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="newPassword">Новый пароль</label>
                            <div class="input-with-icon">
                                <i class='bx bx-lock-alt'></i>
                                <input type="password" id="newPassword" required>
                                <button type="button" class="toggle-password">
                                    <i class='bx bx-hide'></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Подтвердите пароль</label>
                            <div class="input-with-icon">
                                <i class='bx bx-lock-alt'></i>
                                <input type="password" id="confirmPassword" required>
                                <button type="button" class="toggle-password">
                                    <i class='bx bx-hide'></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Сменить пароль</button>
                        </div>
                    </form>
                </div>

                <!-- Настройки уведомлений -->
                <div class="profile-card">
                    <h3 class="card-title">Настройки уведомлений</h3>

                    <div class="notification-settings">
                        <label class="checkbox-container">
                            <input type="checkbox" name="email_notifications" checked>
                            <span class="checkmark"></span>
                            Email-уведомления
                        </label>

                        <label class="checkbox-container">
                            <input type="checkbox" name="task_assignments" checked>
                            <span class="checkmark"></span>
                            Уведомления о новых задачах
                        </label>

                        <label class="checkbox-container">
                            <input type="checkbox" name="deadline_reminders" checked>
                            <span class="checkmark"></span>
                            Напоминания о дедлайнах
                        </label>

                        <label class="checkbox-container">
                            <input type="checkbox" name="project_updates">
                            <span class="checkmark"></span>
                            Обновления проектов
                        </label>

                        <label class="checkbox-container">
                            <input type="checkbox" name="motivation_daily" checked>
                            <span class="checkmark"></span>
                            Ежедневная мотивация
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="saveNotifications">Сохранить
                            настройки</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© 2025 TaskHub</p>
        </div>
    </footer>

    <script src="profile.js"></script>
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