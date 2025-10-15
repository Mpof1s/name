// script.js - Главный скрипт для страницы задач
document.addEventListener('DOMContentLoaded', function() {
    console.log('Main script loaded');
    
    // Элементы страницы
    const addTaskBtn = document.getElementById('addTaskBtn');
    const taskModal = document.getElementById('taskModal');
    const cancelTaskBtn = document.getElementById('cancelTask');
    const modalCloseBtn = document.querySelector('.modal-close');
    const taskForm = document.getElementById('taskForm');
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const applyFiltersBtn = document.getElementById('applyFilters');
    
    // Инициализация
    initEventListeners();
    
    function initEventListeners() {
        // Кнопка добавления задачи
        if (addTaskBtn) {
            addTaskBtn.addEventListener('click', showAddTaskModal);
        }
        
        // Закрытие модального окна
        if (cancelTaskBtn) {
            cancelTaskBtn.addEventListener('click', closeModal);
        }
        
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', closeModal);
        }
        
        if (taskModal) {
            taskModal.addEventListener('click', function(e) {
                if (e.target === taskModal) closeModal();
            });
        }
        
        // Фильтрация задач
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', applyFilters);
        }
        
        // Закрытие по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && taskModal.style.display === 'flex') {
                closeModal();
            }
        });
    }
    
    function showAddTaskModal() {
        if (taskModal) {
            taskModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const deadlineInput = document.getElementById('taskDeadline');
            if (deadlineInput) {
                deadlineInput.min = tomorrow.toISOString().split('T')[0];
            }
            
            const titleInput = document.getElementById('taskTitle');
            if (titleInput) {
                titleInput.focus();
            }
        }
    }
    
    function closeModal() {
        if (taskModal) {
            taskModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            if (taskForm) {
                taskForm.reset();
            }
        }
    }
    
    function applyFilters() {
        const status = statusFilter ? statusFilter.value : 'all';
        const priority = priorityFilter ? priorityFilter.value : 'all';
        
        // Формируем URL с параметрами фильтрации
        const params = new URLSearchParams();
        if (status !== 'all') params.append('status', status);
        if (priority !== 'all') params.append('priority', priority);
        
        const queryString = params.toString();
        const url = queryString ? `index.php?${queryString}` : 'index.php';
        
        window.location.href = url;
    }
});

// Функция обновления статуса задачи
function updateTaskStatus(taskId, isCompleted) {
    const newStatus = isCompleted ? 'done' : 'planned';
    
    // Отправляем AJAX запрос
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            update_status: '1',
            task_id: taskId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Ошибка при обновлении статуса');
            // Возвращаем чекбокс в исходное состояние
            const checkbox = document.getElementById(`task-${taskId}`);
            if (checkbox) {
                checkbox.checked = !isCompleted;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при обновлении статуса');
    });
}

// Функция редактирования задачи
function editTask(taskId) {
    alert(`Редактирование задачи ${taskId} будет доступно в следующей версии`);
}

// Функция удаления задачи
function deleteTask(taskId) {
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        // Здесь будет AJAX запрос для удаления
        alert(`Удаление задачи ${taskId} будет доступно в следующей версии`);
    }
}

// Валидация форм
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            markAsError(input, 'Это поле обязательно');
            isValid = false;
        } else {
            clearError(input);
        }
    });
    
    return isValid;
}

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

function clearError(input) {
    input.classList.remove('error');
    const errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains('error-message')) {
        errorElement.remove();
    }
}
// Функции для бургер-меню
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');
    const mobileMenuLinks = document.querySelectorAll('.mobile-nav-link');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    
    // Закрытие по клику вне меню
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                closeMobileMenu();
            }
        });
    }
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
    
    // Закрытие при клике на ссылки
    if (mobileMenuLinks) {
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }
}

function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const body = document.body;
    
    if (mobileMenu.classList.contains('active')) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
}

function openMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const body = document.body;
    
    mobileMenu.classList.add('active');
    body.style.overflow = 'hidden';
}

function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const body = document.body;
    
    mobileMenu.classList.remove('active');
    body.style.overflow = 'auto';
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    // ... остальной ваш код
});

// Управление модальным окном
function initModal() {
    const addTaskBtn = document.getElementById('addTaskBtn');
    const taskModal = document.getElementById('taskModal');
    const cancelTaskBtn = document.getElementById('cancelTask');
    const modalCloseBtn = document.getElementById('modalClose');
    const taskForm = document.getElementById('taskForm');
    
    // Открытие модального окна
    if (addTaskBtn && taskModal) {
        addTaskBtn.addEventListener('click', function() {
            taskModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Устанавливаем минимальную дату как завтра
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const deadlineInput = document.getElementById('taskDeadline');
            if (deadlineInput) {
                deadlineInput.min = tomorrow.toISOString().split('T')[0];
            }
            
            // Фокусируемся на поле названия
            const titleInput = document.getElementById('taskTitle');
            if (titleInput) {
                titleInput.focus();
            }
        });
    }
    
    // Закрытие модального окна
    function closeModal() {
        const taskModal = document.getElementById('taskModal');
        if (taskModal) {
            taskModal.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Очищаем форму и ошибки
            const taskForm = document.getElementById('taskForm');
            if (taskForm) {
                taskForm.reset();
            }
            
            // Очищаем ошибки
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => el.textContent = '');
            
            const inputElements = document.querySelectorAll('.modal-input');
            inputElements.forEach(el => el.classList.remove('error'));
        }
    }
    
    // Обработчики закрытия
    if (cancelTaskBtn) {
        cancelTaskBtn.addEventListener('click', closeModal);
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    
    if (taskModal) {
        taskModal.addEventListener('click', function(e) {
            if (e.target === taskModal) {
                closeModal();
            }
        });
    }
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const taskModal = document.getElementById('taskModal');
            if (taskModal && taskModal.classList.contains('show')) {
                closeModal();
            }
        }
    });
    
    // Валидация формы
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            if (!validateTaskForm()) {
                e.preventDefault();
            }
        });
    }
}

// Валидация формы задачи
function validateTaskForm() {
    const titleInput = document.getElementById('taskTitle');
    const titleError = document.getElementById('titleError');
    let isValid = true;
    
    // Очищаем предыдущие ошибки
    if (titleError) titleError.textContent = '';
    if (titleInput) titleInput.classList.remove('error');
    
    // Проверяем название задачи
    if (titleInput && !titleInput.value.trim()) {
        titleError.textContent = 'Название задачи обязательно';
        titleInput.classList.add('error');
        isValid = false;
        titleInput.focus();
    }
    
    return isValid;
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initModal();
    console.log('Modal initialized');
});