/**
 * Функционал переключения между светлой и темной темой
 */
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли в localStorage сохраненная тема
    const currentTheme = localStorage.getItem('theme') || 'light';

    // Применяем сохраненную тему при загрузке страницы
    document.documentElement.setAttribute('data-theme', currentTheme);

    // Устанавливаем положение переключателя в соответствии с текущей темой
    if (currentTheme === 'dark') {
        document.getElementById('theme-switch').checked = true;
    }

    // Обработчик переключения темы
    document.getElementById('theme-switch').addEventListener('change', function(e) {
        if (e.target.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            showThemeChangeNotification('Темная тема включена');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            showThemeChangeNotification('Светлая тема включена');
        }
    });

    // Функция отображения уведомления о смене темы
    function showThemeChangeNotification(message) {
        // Проверяем, существует ли уже уведомление
        let notification = document.getElementById('theme-notification');

        if (!notification) {
            // Создаем новое уведомление, если его нет
            notification = document.createElement('div');
            notification.id = 'theme-notification';
            notification.className = 'theme-notification';
            document.body.appendChild(notification);

            // Добавляем стили для уведомления, если их еще нет
            if (!document.getElementById('theme-notification-style')) {
                const style = document.createElement('style');
                style.id = 'theme-notification-style';
                style.textContent = `
                    .theme-notification {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background-color: var(--primary-color);
                        color: white;
                        padding: 10px 20px;
                        border-radius: 5px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                        z-index: 9999;
                        opacity: 0;
                        transform: translateY(20px);
                        transition: opacity 0.3s, transform 0.3s;
                    }
                    .theme-notification.show {
                        opacity: 1;
                        transform: translateY(0);
                    }
                `;
                document.head.appendChild(style);
            }
        }

        // Устанавливаем текст уведомления
        notification.textContent = message;

        // Показываем уведомление
        notification.classList.add('show');

        // Скрываем уведомление через 2 секунды
        setTimeout(() => {
            notification.classList.remove('show');
        }, 2000);
    }
});
