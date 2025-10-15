<?php
// auth.php - Добавляем в самое начало файла
require_once 'config.php';
require_once 'functions.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$success = '';

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Валидация CSRF токена
    if (!validate_csrf_token($csrf_token)) {
        $errors[] = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
    } else {
        // Обработка входа
        if ($action === 'login') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Валидация данных
            if (empty($email) || empty($password)) {
                $errors[] = 'Все поля обязательны для заполнения';
            } elseif (!is_valid_email($email)) {
                $errors[] = 'Неверный формат email';
            } else {
                try {
                    global $pdo;
                    
                    // Ищем пользователя в базе
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user && verify_password($password, $user['password_hash'])) {
                        // Успешный вход
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        
                        // Перенаправляем
                        $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
                        unset($_SESSION['redirect_url']);
                        redirect($redirect_url);
                    } else {
                        $errors[] = 'Неверный email или пароль';
                    }
                } catch (PDOException $e) {
                    error_log("Login error: " . $e->getMessage());
                    $errors[] = 'Ошибка сервера. Пожалуйста, попробуйте позже.';
                }
            }
        }
        
       // Обработка регистрации
if ($action === 'register') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']); // Проверяем чекбокс
    
    // Валидация данных
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errors[] = 'Все поля обязательны для заполнения';
    } elseif (!is_valid_email($email)) {
        $errors[] = 'Неверный формат email';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Пароли не совпадают';
    } elseif (!$terms) {
        $errors[] = 'Необходимо принять условия использования';
    } else {
                try {
                    global $pdo;
                    
                    // Проверяем существует ли email
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        $errors[] = 'Пользователь с таким email уже существует';
                    } else {
                        // Создаем нового пользователя
                        $passwordHash = hash_password($password);
                        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
                        
                        if ($stmt->execute([$firstName, $lastName, $email, $passwordHash])) {
                            $success = 'Регистрация успешна! Теперь вы можете войти.';
                            // Очищаем форму
                            $_POST = [];
                        } else {
                            $errors[] = 'Ошибка при регистрации. Пожалуйста, попробуйте позже.';
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $errors[] = 'Ошибка сервера. Пожалуйста, попробуйте позже.';
                }
            }
        }
    }
}

// Генерация CSRF токена
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHub - Вход и регистрация</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="auth.css">
</head>

<body>
    <div class="auth-container">
        <!-- Левый блок с приветствием -->
        <div class="auth-welcome">
            <div class="welcome-content">
                <a href="index.html" class="auth-logo">TaskHub</a>
                <h1>Добро пожаловать!</h1>
                <p>Присоединяйтесь к нашей платформе для управления задачами и проектами. Повышайте продуктивность
                    вместе с нами.</p>
                <div class="welcome-features">
                    <div class="feature">
                        <i class='bx bx-task'></i>
                        <span>Управление задачами</span>
                    </div>
                    <div class="feature">
                        <i class='bx bx-grid-alt'></i>
                        <span>Канбан-доска</span>
                    </div>
                    <div class="feature">
                        <i class='bx bx-group'></i>
                        <span>Командная работа</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Правый блок с формами -->
        <div class="auth-forms">
            <div class="forms-container">
                <!-- Переключатель форм -->
                <div class="auth-tabs">
                    <button class="tab-btn active" data-tab="login">Вход</button>
                    <button class="tab-btn" data-tab="register">Регистрация</button>
                </div>
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <p>
                        <?php echo htmlspecialchars($success); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Форма входа -->
                <form class="auth-form active" id="loginForm" method="POST">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-group">
        <label for="loginEmail">Email</label>
        <div class="input-with-icon">
            <i class='bx bx-envelope'></i>
            <input type="email" id="loginEmail" name="email" placeholder="your@email.com" required>
        </div>
        <span class="error-message" id="loginEmailError"></span>
    </div>

    <div class="form-group">
        <label for="loginPassword">Пароль</label>
        <div class="input-with-icon">
            <i class='bx bx-lock-alt'></i>
            <input type="password" id="loginPassword" name="password" placeholder="Введите ваш пароль" required>
            <button type="button" class="toggle-password" aria-label="Показать пароль">
                <i class='bx bx-hide'></i>
            </button>
        </div>
        <span class="error-message" id="loginPasswordError"></span>
    </div>
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="remember">
                            <span class="checkmark"></span>
                            Запомнить меня
                        </label>
                        <a href="#forgot-password" class="forgot-link">Забыли пароль?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class='bx bx-log-in'></i> Войти
                    </button>

                    <div class="form-divider">
                        <span>или</span>
                    </div>

                    <button type="button" class="btn btn-google">
                        <i class='bx bxl-google'></i> Продолжить с Google
                    </button>
                </form>

       
                <!-- Форма регистрации -->
<form class="auth-form" id="registerForm" method="POST">
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-row">
        <div class="form-group">
            <label for="registerFirstName">Имя</label>
            <div class="input-with-icon">
                <i class='bx bx-user'></i>
                <input type="text" id="registerFirstName" name="first_name" placeholder="Иван" required>
            </div>
            <span class="error-message" id="firstNameError"></span>
        </div>

        <div class="form-group">
            <label for="registerLastName">Фамилия</label>
            <div class="input-with-icon">
                <i class='bx bx-user'></i>
                <input type="text" id="registerLastName" name="last_name" placeholder="Иванов" required>
            </div>
            <span class="error-message" id="lastNameError"></span>
        </div>
    </div>

    <div class="form-group">
        <label for="registerEmail">Email</label>
        <div class="input-with-icon">
            <i class='bx bx-envelope'></i>
            <input type="email" id="registerEmail" name="email" placeholder="your@email.com" required>
        </div>
        <span class="error-message" id="registerEmailError"></span>
    </div>

    <div class="form-group">
        <label for="registerPassword">Пароль</label>
        <div class="input-with-icon">
            <i class='bx bx-lock-alt'></i>
            <input type="password" id="registerPassword" name="password" placeholder="Создайте пароль" required>
            <button type="button" class="toggle-password" aria-label="Показать пароль">
                <i class='bx bx-hide'></i>
            </button>
        </div>
        <span class="error-message" id="registerPasswordError"></span>
    </div>

    <div class="form-group">
        <label for="registerConfirmPassword">Подтверждение пароля</label>
        <div class="input-with-icon">
            <i class='bx bx-lock-alt'></i>
            <input type="password" id="registerConfirmPassword" name="confirm_password" placeholder="Повторите пароль" required>
            <button type="button" class="toggle-password" aria-label="Показать пароль">
                <i class='bx bx-hide'></i>
            </button>
        </div>
        <span class="error-message" id="confirmPasswordError"></span>
    </div>

    <div class="form-group">
        <label class="checkbox-container">
            <input type="checkbox" id="terms" name="terms" required>
            <span class="checkmark"></span>
            Я принимаю <a href="#terms">условия использования</a> и <a href="#privacy">политику конфиденциальности</a>
        </label>
        <span class="error-message" id="termsError"></span>
    </div>

    <button type="submit" class="btn btn-primary btn-full">
        <i class='bx bx-user-plus'></i> Зарегистрироваться
    </button>

    <div class="form-divider">
        <span>или</span>
    </div>

    <button type="button" class="btn btn-google">
        <i class='bx bxl-google'></i> Продолжить с Google
    </button>
</form>
            </div>
        </div>
    </div>

    <script src="auth.js"></script>
</body>

</html>