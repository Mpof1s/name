// profile.js - Только визуальные функции, формы отправляются нормально
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile JS loaded');
    
    // Инициализация
    initPasswordToggles();
    initAvatarUpload();
    initCharacterCounter();
    
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
    
    // Функция загрузки аватарки (только превью)
    function initAvatarUpload() {
        const avatarUploadBtn = document.getElementById('avatarUploadBtn');
        const avatarInput = document.getElementById('avatarInput');
        const avatarImage = document.getElementById('avatarImage');
        
        if (avatarUploadBtn && avatarInput) {
            avatarUploadBtn.addEventListener('click', function() {
                avatarInput.click();
            });
            
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                if (!file.type.startsWith('image/')) {
                    alert('Пожалуйста, выберите изображение');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert('Размер файла не должен превышать 5MB');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarImage) {
                        avatarImage.src = e.target.result;
                    }
                    console.log('Avatar preview updated');
                };
                
                reader.readAsDataURL(file);
            });
        }
    }
    
    // Счетчик символов для поля "О себе"
    function initCharacterCounter() {
        const bioTextarea = document.getElementById('bio');
        if (!bioTextarea) return;
        
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.fontSize = '0.8rem';
        counter.style.color = '#6B7280';
        counter.style.textAlign = 'right';
        counter.style.marginTop = '0.25rem';
        
        bioTextarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            counter.textContent = `${bioTextarea.value.length}/500 символов`;
        }
        
        bioTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Визуальная валидация формы
    function initFormValidation() {
        const profileForm = document.getElementById('profileForm');
        const passwordForm = document.getElementById('passwordForm');
        
        if (profileForm) {
            profileForm.addEventListener('submit', function() {
                validateProfileForm();
            });
        }
        
        if (passwordForm) {
            passwordForm.addEventListener('submit', function() {
                validatePasswordForm();
            });
        }
    }
    
    // Валидация формы профиля
    function validateProfileForm() {
        const firstName = document.getElementById('firstName');
        const lastName = document.getElementById('lastName');
        const email = document.getElementById('email');
        let isValid = true;
        
        clearErrors();
        
        if (!firstName.value.trim()) {
            markAsError(firstName, 'Имя обязательно');
            isValid = false;
        }
        
        if (!lastName.value.trim()) {
            markAsError(lastName, 'Фамилия обязательна');
            isValid = false;
        }
        
        if (!email.value.trim()) {
            markAsError(email, 'Email обязателен');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            markAsError(email, 'Неверный формат email');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Валидация формы пароля
    function validatePasswordForm() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        let isValid = true;
        
        clearErrors();
        
        if (newPassword && newPassword.value.length < 6) {
            markAsError(newPassword, 'Минимум 6 символов');
            isValid = false;
        }
        
        if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
            markAsError(confirmPassword, 'Пароли не совпадают');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Показать ошибку
    function markAsError(input, message) {
        input.classList.add('error');
        
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('span');
            errorElement.className = 'error-message';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        
        errorElement.textContent = message;
    }
    
    // Очистить ошибки
    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => element.remove());
        
        const errorInputs = document.querySelectorAll('.error');
        errorInputs.forEach(input => input.classList.remove('error'));
    }
    
    // Проверка email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Инициализируем валидацию
    initFormValidation();
});

console.log('Profile script loaded successfully');