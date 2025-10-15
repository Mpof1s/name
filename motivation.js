// motivation.js - Логика для страницы мотивации
document.addEventListener('DOMContentLoaded', function() {
    const addMotivationBtn = document.getElementById('addMotivationBtn');
    const motivationForm = document.getElementById('motivationForm');
    const motivationText = document.getElementById('motivationText');
    const charCount = document.getElementById('charCount');
    const cancelBtn = document.getElementById('cancelBtn');
    const submitBtn = document.getElementById('submitBtn');
    const motivationGrid = document.getElementById('motivationGrid');
    
    let isFormVisible = false;
    
    // Инициализация
    initEventListeners();
    
    function initEventListeners() {
        // Кнопка добавления мотивации
        addMotivationBtn.addEventListener('click', toggleMotivationForm);
        
        // Отслеживание ввода текста
        motivationText.addEventListener('input', updateCharCount);
        
        // Кнопки формы
        cancelBtn.addEventListener('click', resetForm);
        submitBtn.addEventListener('click', submitMotivation);
        
        // Клик по карточкам мотивации
        const motivationCards = document.querySelectorAll('.motivation-card');
        motivationCards.forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Можно добавить функционал "лайка" или копирования
                console.log('Карточка мотивации кликнута');
            });
        });
    }
    
    function toggleMotivationForm() {
        isFormVisible = !isFormVisible;
        
        if (isFormVisible) {
            motivationForm.style.display = 'block';
            motivationText.focus();
            addMotivationBtn.innerHTML = '<i class="bx bx-x"></i> Отмена';
            addMotivationBtn.classList.add('btn-secondary');
            addMotivationBtn.classList.remove('btn-primary');
        } else {
            resetForm();
        }
    }
    
    function updateCharCount() {
        const text = motivationText.value;
        charCount.textContent = text.length;
        
        // Меняем цвет счетчика при приближении к лимиту
        if (text.length > 450) {
            charCount.style.color = '#DC2626';
        } else if (text.length > 400) {
            charCount.style.color = '#F59E0B';
        } else {
            charCount.style.color = '#6B7280';
        }
    }
    
    function resetForm() {
        motivationForm.style.display = 'none';
        motivationText.value = '';
        charCount.textContent = '0';
        charCount.style.color = '#6B7280';
        
        addMotivationBtn.innerHTML = '<i class="bx bx-plus"></i> Добавить мотивацию';
        addMotivationBtn.classList.remove('btn-secondary');
        addMotivationBtn.classList.add('btn-primary');
        
        isFormVisible = false;
    }
    
    function submitMotivation() {
        const text = motivationText.value.trim();
        
        if (!text) {
            alert('Пожалуйста, напишите мотивационное сообщение!');
            motivationText.focus();
            return;
        }
        
        if (text.length > 500) {
            alert('Сообщение слишком длинное! Максимум 500 символов.');
            return;
        }
        
        // Заглушка для добавления мотивации
        console.log('Добавление мотивации:', text);
        alert('Мотивация добавлена! После подключения PHP она сохранится в базе данных.');
        
        // Здесь будет AJAX запрос к PHP когда подключим бэкенд
        /*
        fetch('add_motivation.php', {
            method: 'POST',
            body: JSON.stringify({ text: text }),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addMotivationToGrid(text, 'Вы', new Date().toLocaleDateString());
                resetForm();
            }
        });
        */
        
        resetForm();
    }
    
    // Функция для добавления мотивации в сетку (заглушка)
    function addMotivationToGrid(text, author, date) {
        const newCard = document.createElement('div');
        newCard.className = 'motivation-card';
        newCard.innerHTML = `
            <div class="motivation-content">
                <p class="motivation-text">${text}</p>
            </div>
            <div class="motivation-meta">
                <span class="motivation-author">${author}</span>
                <span class="motivation-date">• ${date}</span>
            </div>
        `;
        
        motivationGrid.prepend(newCard);
        
        // Добавляем анимацию для новой карточки
        newCard.style.animation = 'floatIn 0.6s ease';
        
        // Добавляем обработчик клика
        newCard.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }
    
    // Дополнительная функция для рандомного выбора градиента
    function getRandomGradient() {
        const gradients = [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
        ];
        return gradients[Math.floor(Math.random() * gradients.length)];
    }
});