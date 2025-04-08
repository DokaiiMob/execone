/**
 * Скрипт для работы с уведомлениями в режиме реального времени
 */
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация модуля уведомлений
    const NotificationsModule = {
        init: function() {
            this.setupToasts();
            this.setupNotificationActions();

            // Проверка новых уведомлений каждые 30 секунд
            if (document.body.classList.contains('user-logged-in')) {
                this.checkNewNotifications();
                setInterval(() => this.checkNewNotifications(), 30000);
            }
        },

        // Инициализация всплывающих уведомлений
        setupToasts: function() {
            // Добавляем контейнер для тостов, если его нет
            if (!document.getElementById('toast-container')) {
                const toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
        },

        // Проверка новых уведомлений через AJAX
        checkNewNotifications: function() {
            ajax('/api/notifications/check.php', 'GET', null, (error, response) => {
                if (error) {
                    console.error('Ошибка при проверке уведомлений:', error);
                    return;
                }

                if (response.success && response.new_notifications.length > 0) {
                    // Обновление счетчика уведомлений
                    this.updateNotificationBadge(response.unread_count);

                    // Показываем уведомления
                    response.new_notifications.forEach(notification => {
                        this.showToast(notification);
                    });
                }
            });
        },

        // Обновление значка с количеством непрочитанных уведомлений
        updateNotificationBadge: function(count) {
            const notificationLink = document.querySelector('a[href="/notifications.php"]');
            if (!notificationLink) return;

            let badge = notificationLink.querySelector('.badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    notificationLink.appendChild(badge);
                }

                badge.textContent = count > 99 ? '99+' : count;

                // Добавляем скрытый текст для скринридеров
                if (!badge.querySelector('.visually-hidden')) {
                    const hiddenText = document.createElement('span');
                    hiddenText.className = 'visually-hidden';
                    hiddenText.textContent = 'Новые уведомления';
                    badge.appendChild(hiddenText);
                }
            } else if (badge) {
                badge.remove();
            }
        },

        // Отображение всплывающего уведомления
        showToast: function(notification) {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;

            // Определяем класс для разных типов уведомлений
            let bgClass = 'bg-primary';
            switch (notification.type) {
                case 'success': bgClass = 'bg-success'; break;
                case 'warning': bgClass = 'bg-warning text-dark'; break;
                case 'danger': bgClass = 'bg-danger'; break;
            }

            // Создаем элемент toast
            const toastEl = document.createElement('div');
            toastEl.className = `toast ${bgClass} text-white`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.innerHTML = `
                <div class="toast-header ${bgClass} text-white">
                    <strong class="me-auto">${notification.title}</strong>
                    <small>${this.formatTimestamp(notification.created_at)}</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Закрыть"></button>
                </div>
                <div class="toast-body">
                    ${notification.message}
                    <div class="mt-2 pt-2 border-top">
                        <a href="/notifications.php" class="btn btn-sm btn-light">Просмотреть</a>
                        <button type="button" class="btn btn-sm btn-outline-light mark-read-btn" data-id="${notification.id}">
                            Прочитано
                        </button>
                    </div>
                </div>
            `;

            // Добавляем toast в контейнер
            toastContainer.appendChild(toastEl);

            // Инициализируем и показываем toast
            const toast = new bootstrap.Toast(toastEl, {
                delay: 10000 // Автоматически скрывать через 10 секунд
            });

            // Добавляем обработчик для кнопки "Прочитано"
            toastEl.querySelector('.mark-read-btn').addEventListener('click', () => {
                this.markAsRead(notification.id, toastEl);
            });

            toast.show();

            // Добавляем анимацию при появлении
            toastEl.style.animation = 'fadeInUp 0.3s ease-out forwards';
        },

        // Форматирование времени для отображения
        formatTimestamp: function(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();

            // Если уведомление сегодня, показываем только время
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            // Иначе показываем дату и время
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        // Отметка уведомления как прочитанного
        markAsRead: function(notificationId, toastEl) {
            ajax('/api/notifications/mark-read.php', 'POST', { id: notificationId }, (error, response) => {
                if (error) {
                    console.error('Ошибка при отметке уведомления:', error);
                    return;
                }

                if (response.success) {
                    // Закрываем toast
                    const toast = bootstrap.Toast.getInstance(toastEl);
                    if (toast) toast.hide();

                    // Обновляем счетчик
                    this.updateNotificationBadge(response.unread_count);
                }
            });
        },

        // Настройка обработчиков событий для действий с уведомлениями
        setupNotificationActions: function() {
            // Делегирование событий для кнопок в списке уведомлений
            document.addEventListener('click', (e) => {
                // Обработка клика по кнопке "Отметить как прочитанное"
                if (e.target.closest('a[href^="?action=read&id="]')) {
                    e.preventDefault();
                    const link = e.target.closest('a[href^="?action=read&id="]');
                    const id = this.getParameterFromUrl(link.href, 'id');

                    if (id) {
                        this.markAsReadInList(id, link.closest('.list-group-item'));
                    }
                }

                // Обработка клика по кнопке "Удалить"
                if (e.target.closest('a[href^="?action=delete&id="]')) {
                    e.preventDefault();
                    const link = e.target.closest('a[href^="?action=delete&id="]');

                    if (confirm('Вы уверены, что хотите удалить это уведомление?')) {
                        const id = this.getParameterFromUrl(link.href, 'id');
                        if (id) {
                            this.deleteNotification(id, link.closest('.list-group-item'));
                        }
                    }
                }
            });

            // Обработка кнопок массовых действий
            const markAllReadBtn = document.querySelector('button[name="mark_all_read"]');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
            }

            const deleteAllBtn = document.querySelector('button[name="delete_all"]');
            if (deleteAllBtn) {
                deleteAllBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (confirm('Вы уверены, что хотите удалить все уведомления?')) {
                        this.deleteAllNotifications();
                    }
                });
            }
        },

        // Получение параметра из URL
        getParameterFromUrl: function(url, parameter) {
            const urlParams = new URLSearchParams(url.split('?')[1]);
            return urlParams.get(parameter);
        },

        // Отметка уведомления как прочитанного в списке
        markAsReadInList: function(notificationId, listItem) {
            ajax('/api/notifications/mark-read.php', 'POST', { id: notificationId }, (error, response) => {
                if (error) {
                    console.error('Ошибка при отметке уведомления:', error);
                    return;
                }

                if (response.success) {
                    // Обновляем класс элемента и скрываем кнопку
                    listItem.classList.remove('notification-unread');
                    const readBtn = listItem.querySelector('a[href^="?action=read&id="]');
                    if (readBtn) readBtn.style.display = 'none';

                    // Обновляем счетчик на странице
                    const badge = document.querySelector('h1 .badge');
                    if (badge) {
                        badge.textContent = response.unread_count + ' новых';
                    }

                    // Обновляем счетчик в шапке
                    this.updateNotificationBadge(response.unread_count);

                    // Отображаем сообщение об успехе
                    this.showAlert('success', 'Уведомление отмечено как прочитанное');
                }
            });
        },

        // Удаление уведомления из списка
        deleteNotification: function(notificationId, listItem) {
            ajax('/api/notifications/delete.php', 'POST', { id: notificationId }, (error, response) => {
                if (error) {
                    console.error('Ошибка при удалении уведомления:', error);
                    return;
                }

                if (response.success) {
                    // Анимация удаления
                    listItem.style.transition = 'all 0.3s ease';
                    listItem.style.opacity = '0';
                    listItem.style.height = '0';

                    setTimeout(() => {
                        listItem.remove();

                        // Проверяем, остались ли уведомления
                        const notificationItems = document.querySelectorAll('.list-group-item');
                        if (notificationItems.length === 0) {
                            const listGroup = document.querySelector('.list-group');
                            if (listGroup) {
                                const card = listGroup.closest('.card');
                                if (card) {
                                    card.innerHTML = `
                                        <div class="alert alert-info m-3">
                                            <i class="fas fa-info-circle me-2"></i> У вас пока нет уведомлений.
                                        </div>
                                    `;
                                }
                            }

                            // Скрываем кнопки массовых действий
                            const actionButtons = document.querySelector('form.d-inline');
                            if (actionButtons) actionButtons.style.display = 'none';
                        }

                        // Обновляем счетчик на странице
                        const badge = document.querySelector('h1 .badge');
                        if (badge) {
                            badge.textContent = response.unread_count + ' новых';
                        }

                        // Обновляем счетчик в шапке
                        this.updateNotificationBadge(response.unread_count);

                        // Отображаем сообщение об успехе
                        this.showAlert('success', 'Уведомление удалено');
                    }, 300);
                }
            });
        },

        // Отметка всех уведомлений как прочитанных
        markAllAsRead: function() {
            ajax('/api/notifications/mark-all-read.php', 'POST', null, (error, response) => {
                if (error) {
                    console.error('Ошибка при отметке всех уведомлений:', error);
                    return;
                }

                if (response.success) {
                    // Обновляем классы всех элементов и скрываем кнопки
                    const unreadItems = document.querySelectorAll('.notification-unread');
                    unreadItems.forEach(item => {
                        item.classList.remove('notification-unread');
                        const readBtn = item.querySelector('a[href^="?action=read&id="]');
                        if (readBtn) readBtn.style.display = 'none';
                    });

                    // Обновляем счетчик на странице
                    const badge = document.querySelector('h1 .badge');
                    if (badge) {
                        badge.textContent = '0 новых';
                    }

                    // Обновляем счетчик в шапке
                    this.updateNotificationBadge(0);

                    // Отображаем сообщение об успехе
                    this.showAlert('success', 'Все уведомления отмечены как прочитанные');
                }
            });
        },

        // Удаление всех уведомлений
        deleteAllNotifications: function() {
            ajax('/api/notifications/delete-all.php', 'POST', null, (error, response) => {
                if (error) {
                    console.error('Ошибка при удалении всех уведомлений:', error);
                    return;
                }

                if (response.success) {
                    // Заменяем содержимое карточки
                    const card = document.querySelector('.card');
                    if (card) {
                        card.innerHTML = `
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle me-2"></i> У вас пока нет уведомлений.
                            </div>
                        `;
                    }

                    // Скрываем кнопки массовых действий
                    const actionButtons = document.querySelector('form.d-inline');
                    if (actionButtons) actionButtons.style.display = 'none';

                    // Обновляем счетчик на странице
                    const badge = document.querySelector('h1 .badge');
                    if (badge) {
                        badge.textContent = '0 новых';
                    }

                    // Обновляем счетчик в шапке
                    this.updateNotificationBadge(0);

                    // Отображаем сообщение об успехе
                    this.showAlert('success', 'Все уведомления удалены');
                }
            });
        },

        // Отображение системного уведомления
        showAlert: function(type, message) {
            // Проверяем, существует ли уже контейнер для алертов
            let alertContainer = document.querySelector('.alert-container');

            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.className = 'alert-container position-fixed top-0 start-50 translate-middle-x p-3';
                alertContainer.style.zIndex = '9999';
                document.body.appendChild(alertContainer);
            }

            // Создаем алерт
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.role = 'alert';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            `;

            // Добавляем алерт в контейнер
            alertContainer.appendChild(alert);

            // Автоматически скрываем через 3 секунды
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 3000);
        }
    };

    // Инициализация модуля уведомлений
    NotificationsModule.init();
});
