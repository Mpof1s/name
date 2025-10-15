// auth.js - Только визуальные функции, формы отправляются нормально
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth JS loaded');
    
    // Элементы страницы
    const tabButtons = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');
    
    // Инициализация
    initTabs();
    initPasswordToggles();
    initGoogleAuth();
    initForgotPassword();
    
    // Функция переключения табов
    function initTabs() {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Активируем таб
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Показываем соответствующую форму
                authForms.forEach(form => form.classList.remove('active'));
                document.getElementById(`${tabName}Form`).classList.add('active');
            });
        });
    }
    
    // Функция переключения видимости пароля
    function initPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bx-hide');
                    icon.classList.add('bx-show');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bx-show');
                    icon.classList.add('bx-hide');
                }
            });
        });
    }
    
    // Обработчик для Google авторизации
    function initGoogleAuth() {
        const googleButtons = document.querySelectorAll('.btn-google');
        googleButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Google auth clicked');
                // Заглушка для будущей реализации
            });
        });
    }
    
    // Обработчик для "Забыли пароль"
    function initForgotPassword() {
        const forgotLink = document.querySelector('.forgot-link');
        if (forgotLink) {
            forgotLink.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Forgot password clicked');
                // Заглушка для будущей реализации
            });
        }
    }
    
    // Простая клиентская валидация (только подсветка ошибок)
    function initClientValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // НЕ МЕШАЕМ ОТПРАВКЕ ФОРМЫ!
                // Только визуальная валидация
                validateForm(this);
            });
        });
    }
    
    // Визуальная валидация формы
    function validateForm(form) {
        const inputs = form.querySelectorAll('input[required]');
        let hasErrors = false;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                markAsError(input, 'Это поле обязательно');
                hasErrors = true;
            } else {
                clearError(input);
                
                // Дополнительная валидация для email
                if (input.type === 'email' && !isValidEmail(input.value)) {
                    markAsError(input, 'Неверный формат email');
                    hasErrors = true;
                }
                
                // Дополнительная валидация для пароля
                if (input.type === 'password' && input.value.length < 6) {
                    markAsError(input, 'Минимум 6 символов');
                    hasErrors = true;
                }
            }
        });
        
        // Валидация подтверждения пароля
        const password = form.querySelector('#registerPassword');
        const confirmPassword = form.querySelector('#registerConfirmPassword');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            markAsError(confirmPassword, 'Пароли не совпадают');
            hasErrors = true;
        }
        
        return !hasErrors;
    }
    
    // Показать ошибку
    function markAsError(input, message) {
        input.classList.add('error');
        
        // Создаем или находим элемент для ошибки
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('span');
            errorElement.className = 'error-message';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        
        errorElement.textContent = message;
    }
    
    // Очистить ошибку
    function clearError(input) {
        input.classList.remove('error');
        const errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
        }
    }
    
    // Проверка email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Инициализируем клиентскую валидацию
    initClientValidation();
});

// Консоль сообщение для отладки
console.log('Auth script loaded successfully');