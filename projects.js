// projects.js - Логика для страницы проектов
document.addEventListener('DOMContentLoaded', function() {
    console.log('Projects JS loaded');
    
    // Элементы страницы
    const addProjectBtn = document.getElementById('addProjectBtn');
    const projectModal = document.getElementById('projectModal');
    const cancelProjectBtn = document.getElementById('cancelProject');
    const modalCloseBtn = document.querySelector('.modal-close');
    const projectForm = document.getElementById('projectForm');
    
    // Инициализация
    initEventListeners();
    
    function initEventListeners() {
        // Кнопка добавления проекта
        if (addProjectBtn) {
            addProjectBtn.addEventListener('click', showProjectModal);
        }
        
        // Закрытие модального окна
        if (cancelProjectBtn) {
            cancelProjectBtn.addEventListener('click', closeModal);
        }
        
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', closeModal);
        }
        
        if (projectModal) {
            projectModal.addEventListener('click', function(e) {
                if (e.target === projectModal) closeModal();
            });
        }
        
        
        // Валидация формы
        if (projectForm) {
            projectForm.addEventListener('submit', function(e) {
                if (!validateProjectForm()) {
                    e.preventDefault();
                }
            });
        }
        
        // Закрытие по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && projectModal.style.display === 'flex') {
                closeModal();
            }
        });
    }
    
    function showProjectModal() {
        if (projectModal) {
            projectModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Фокусируемся на поле названия
            const nameInput = document.getElementById('projectName');
            if (nameInput) {
                nameInput.focus();
            }
        }
    }
    
    function closeModal() {
        if (projectModal) {
            projectModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Очищаем форму
            if (projectForm) {
                projectForm.reset();
            }
        }
    }
    
    function validateProjectForm() {
        const nameInput = document.getElementById('projectName');
        let isValid = true;
        
        clearErrors();
        
        if (!nameInput.value.trim()) {
            markAsError(nameInput, 'Название проекта обязательно');
            isValid = false;
        } else {
            clearError(nameInput);
        }
        
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
    
    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => element.remove());
        
        const errorInputs = document.querySelectorAll('.error');
        errorInputs.forEach(input => input.classList.remove('error'));
    }
});

// Функции для работы с проектами
function viewProject(projectId) {
    alert(`Просмотр проекта ${projectId} будет доступен в следующей версии`);
}

function editProject(projectId) {
    alert(`Редактирование проекта ${projectId} будет доступно в следующей версии`);
}

// Функция удаления проекта
function deleteProject(projectId, projectName) {
    if (confirm(`Вы уверены, что хотите удалить проект "${projectName}"?`)) {
        alert(`Удаление проекта ${projectId} будет доступно в следующей версии`);
    }
}

// Функция изменения статуса проекта
function changeProjectStatus(projectId, currentStatus) {
    alert(`Изменение статуса проекта ${projectId} будет доступно в следующей версии`);
}
