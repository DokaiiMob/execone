/**
 * Основные JavaScript функции для сайта
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всплывающих подсказок (Bootstrap)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Инициализация всплывающих сообщений (Bootstrap)
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Автоматическое скрытие сообщений об успехе/ошибке через 5 секунд
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-danger');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Подтверждение удаления
    document.querySelectorAll('.delete-confirm').forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.')) {
                e.preventDefault();
            }
        });
    });

    // Предпросмотр изображения перед загрузкой
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatar-preview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    avatarPreview.src = reader.result;
                    avatarPreview.style.display = 'block';
                });
                reader.readAsDataURL(file);
            }
        });
    }

    // Копирование текста в буфер обмена
    document.querySelectorAll('.copy-to-clipboard').forEach(function(element) {
        element.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            // Показываем уведомление
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Скопировано!';

            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });

    // Обработка выбора плана подписки
    const planSelectors = document.querySelectorAll('.plan-selector');
    if (planSelectors.length > 0) {
        planSelectors.forEach(function(selector) {
            selector.addEventListener('change', function() {
                const planType = this.value;
                const planPriceElement = document.getElementById('plan-price');
                const planDurationElement = document.getElementById('plan-duration');
                const planTypeInput = document.getElementById('plan-type');

                // Получаем JSON с ценами и продолжительностью из атрибута data-plans
                const plansData = JSON.parse(this.getAttribute('data-plans'));

                if (planPriceElement && planDurationElement && planTypeInput && plansData[planType]) {
                    planPriceElement.textContent = new Intl.NumberFormat('ru-RU').format(plansData[planType].price) + ' ₽';
                    planDurationElement.textContent = plansData[planType].duration + ' дней';
                    planTypeInput.value = planType;
                }
            });
        });
    }

    // Обработка табов в личном кабинете
    const profileTabs = document.querySelectorAll('[data-bs-toggle="pill"]');
    if (profileTabs.length > 0) {
        profileTabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(e) {
                // Сохраняем активный таб в localStorage
                localStorage.setItem('activeProfileTab', e.target.getAttribute('href'));
            });
        });

        // Восстанавливаем активный таб при загрузке страницы
        const activeTab = localStorage.getItem('activeProfileTab');
        if (activeTab) {
            const tab = new bootstrap.Tab(document.querySelector(`[href="${activeTab}"]`));
            tab.show();
        }
    }

    // Автоматическое обновление счетчика времени до окончания подписки
    const subscriptionTimers = document.querySelectorAll('.subscription-timer');
    if (subscriptionTimers.length > 0) {
        function updateTimers() {
            subscriptionTimers.forEach(function(timer) {
                const endDate = new Date(timer.getAttribute('data-end-date')).getTime();
                const now = new Date().getTime();
                const distance = endDate - now;

                if (distance <= 0) {
                    timer.innerHTML = '<span class="text-danger">Истекла</span>';
                } else {
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                    let timerText = '';
                    if (days > 0) {
                        timerText += days + ' дн. ';
                    }
                    timerText += hours + ' ч. ' + minutes + ' мин.';

                    timer.textContent = timerText;

                    // Добавляем предупреждающий класс, если осталось мало времени
                    if (days <= 3) {
                        timer.classList.add('text-warning');
                    }
                }
            });
        }

        // Запускаем обновление таймеров каждую минуту
        updateTimers();
        setInterval(updateTimers, 60000);
    }
});

/**
 * Функция для отправки AJAX запросов
 *
 * @param {string} url - URL адрес для запроса
 * @param {string} method - Метод запроса (GET, POST, PUT, DELETE)
 * @param {object} data - Данные для отправки (для POST, PUT)
 * @param {function} callback - Функция обратного вызова для обработки результата
 */
function ajax(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(e, null);
                }
            } else {
                callback(new Error(`Ошибка запроса: ${xhr.status}`), null);
            }
        }
    };

    if (data) {
        xhr.send(JSON.stringify(data));
    } else {
        xhr.send();
    }
}
