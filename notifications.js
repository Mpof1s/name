// notifications.js - Система уведомлений
class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        // Создаем контейнер для уведомлений
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }
    
    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: 'bx bx-check-circle',
            error: 'bx bx-error',
            warning: 'bx bx-error-circle',
            info: 'bx bx-info-circle'
        };
        
        toast.innerHTML = `
            <i class="toast-icon ${icons[type]}"></i>
            <div class="toast-message">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class='bx bx-x'></i>
            </button>
        `;
        
        this.container.appendChild(toast);
        
        // Автоматическое закрытие
        if (duration > 0) {
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, duration);
        }
        
        return toast;
    }
    
    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    }
    
    error(message, duration = 3000) {
        return this.show(message, 'error', duration);
    }
    
    warning(message, duration = 3000) {
        return this.show(message, 'warning', duration);
    }
    
    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
}

// Создаем глобальный экземпляр
const notifications = new NotificationSystem();

// Делаем доступным глобально
window.notifications = notifications;