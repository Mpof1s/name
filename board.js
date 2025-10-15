// board.js - Логика для канбан-доски
document.addEventListener('DOMContentLoaded', function() {
    console.log('Board JS loaded');
    
    const kanbanBoard = document.getElementById('kanbanBoard');
    
    // Инициализация перетаскивания
    initDragAndDrop();
    
    function initDragAndDrop() {
        const taskCards = document.querySelectorAll('.task-card');
        const columns = document.querySelectorAll('.column-content');
        
        // Обработчики для карточек
        taskCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
        });
        
        // Обработчики для колонок
        columns.forEach(column => {
            column.addEventListener('dragover', handleDragOver);
            column.addEventListener('dragenter', handleDragEnter);
            column.addEventListener('dragleave', handleDragLeave);
            column.addEventListener('drop', handleDrop);
        });
    }
    
    function handleDragStart(e) {
        e.dataTransfer.setData('task-id', e.target.dataset.taskId);
        e.target.classList.add('dragging');
    }
    
    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
    }
    
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
    
    function handleDragEnter(e) {
        e.preventDefault();
        if (e.target.classList.contains('column-content')) {
            e.target.classList.add('drag-over');
        }
    }
    
    function handleDragLeave(e) {
        if (e.target.classList.contains('column-content')) {
            e.target.classList.remove('drag-over');
        }
    }
    
    function handleDrop(e) {
        e.preventDefault();
        const columns = document.querySelectorAll('.column-content');
        columns.forEach(col => col.classList.remove('drag-over'));
        
        const taskId = e.dataTransfer.getData('task-id');
        const draggedCard = document.querySelector(`[data-task-id="${taskId}"]`);
        
        if (draggedCard && e.target.classList.contains('column-content')) {
            const newStatus = e.target.dataset.status;
            
            // Обновляем DOM
            e.target.appendChild(draggedCard);
            
            // Отправляем AJAX запрос
            updateTaskStatusInDatabase(taskId, newStatus);
            
            // Обновляем счетчики
            updateColumnCounters();
        }
    }
    
    function updateTaskStatusInDatabase(taskId, newStatus) {
        fetch('board.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                update_task_status: '1',
                task_id: taskId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Ошибка при обновлении статуса задачи');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при обновлении статуса задачи');
        });
    }
    
    function updateColumnCounters() {
        const columns = document.querySelectorAll('.kanban-column');
        
        columns.forEach(column => {
            const content = column.querySelector('.column-content');
            const counter = column.querySelector('.column-counter');
            const taskCount = content.querySelectorAll('.task-card').length;
            
            counter.textContent = taskCount;
        });
    }
});