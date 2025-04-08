<?php

class Notification {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицу уведомлений, если не существует
        $this->createNotificationsTable();
    }

    /**
     * Создание таблицы уведомлений в базе данных, если она не существует
     */
    private function createNotificationsTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'info',
                    is_read INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } else if ($driver === 'mysql') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type VARCHAR(50) NOT NULL DEFAULT 'info',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Создание нового уведомления для пользователя
     *
     * @param int $userId ID пользователя
     * @param string $title Заголовок уведомления
     * @param string $message Текст уведомления
     * @param string $type Тип уведомления (info, success, warning, danger)
     * @return array Результат операции
     */
    public function createNotification($userId, $title, $message, $type = 'info') {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Валидация типа уведомления
        $allowedTypes = ['info', 'success', 'warning', 'danger'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'info';
        }

        // Создаем новое уведомление
        $notificationId = $this->db->insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type
        ]);

        return [
            'success' => true,
            'notification_id' => $notificationId
        ];
    }

    /**
     * Создание нового уведомления для всех пользователей
     *
     * @param string $title Заголовок уведомления
     * @param string $message Текст уведомления
     * @param string $type Тип уведомления (info, success, warning, danger)
     * @return array Результат операции
     */
    public function createGlobalNotification($title, $message, $type = 'info') {
        // Получаем всех пользователей
        $userModel = new User();
        $users = $userModel->getAllUsers(1000000, 0); // Получаем всех пользователей

        $count = 0;
        foreach ($users as $user) {
            $result = $this->createNotification($user['id'], $title, $message, $type);
            if ($result['success']) {
                $count++;
            }
        }

        return [
            'success' => true,
            'message' => "Создано {$count} уведомлений из " . count($users) . " пользователей"
        ];
    }

    /**
     * Получение всех уведомлений пользователя
     *
     * @param int $userId ID пользователя
     * @param int $limit Лимит уведомлений (по умолчанию 50)
     * @param int $offset Смещение выборки (по умолчанию 0)
     * @return array Массив уведомлений
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Получение количества непрочитанных уведомлений пользователя
     *
     * @param int $userId ID пользователя
     * @return int Количество непрочитанных уведомлений
     */
    public function getUnreadCount($userId) {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    /**
     * Пометка уведомления как прочитанное
     *
     * @param int $notificationId ID уведомления
     * @param int $userId ID пользователя (для проверки прав)
     * @return array Результат операции
     */
    public function markAsRead($notificationId, $userId) {
        // Проверяем, существует ли уведомление и принадлежит ли оно пользователю
        $notification = $this->db->fetch(
            "SELECT * FROM notifications WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        );

        if (!$notification) {
            return ['success' => false, 'message' => 'Уведомление не найдено или у вас нет прав для его изменения'];
        }

        // Обновляем статус уведомления
        $this->db->update('notifications', [
            'is_read' => 1
        ], 'id = ?', [$notificationId]);

        return ['success' => true];
    }

    /**
     * Пометка всех уведомлений пользователя как прочитанные
     *
     * @param int $userId ID пользователя
     * @return array Результат операции
     */
    public function markAllAsRead($userId) {
        $this->db->update('notifications', [
            'is_read' => 1
        ], 'user_id = ?', [$userId]);

        return ['success' => true];
    }

    /**
     * Удаление уведомления
     *
     * @param int $notificationId ID уведомления
     * @param int $userId ID пользователя (для проверки прав)
     * @return array Результат операции
     */
    public function deleteNotification($notificationId, $userId) {
        // Проверяем, существует ли уведомление и принадлежит ли оно пользователю
        $notification = $this->db->fetch(
            "SELECT * FROM notifications WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        );

        if (!$notification) {
            return ['success' => false, 'message' => 'Уведомление не найдено или у вас нет прав для его удаления'];
        }

        // Удаляем уведомление
        $this->db->delete('notifications', 'id = ?', [$notificationId]);

        return ['success' => true];
    }

    /**
     * Удаление всех уведомлений пользователя
     *
     * @param int $userId ID пользователя
     * @return array Результат операции
     */
    public function deleteAllNotifications($userId) {
        $this->db->delete('notifications', 'user_id = ?', [$userId]);

        return ['success' => true];
    }

    /**
     * Создание системного уведомления о новой версии чита
     *
     * @param int $userId ID пользователя
     * @param string $version Версия чита
     * @param string $requiredPlan Требуемый план подписки
     * @return array Результат операции
     */
    public function createNewVersionNotification($userId, $version, $requiredPlan) {
        // Получаем название плана
        $subscriptionModel = new Subscription();
        $plans = $subscriptionModel->getPlans();
        $planName = $plans[$requiredPlan]['name'] ?? $requiredPlan;

        $title = "Новая версия чита {$version}";
        $message = "Доступна новая версия чита {$version}. Эта версия доступна для подписки \"{$planName}\" и выше.";

        return $this->createNotification($userId, $title, $message, 'info');
    }

    /**
     * Создание системного уведомления об окончании подписки
     *
     * @param int $userId ID пользователя
     * @param string $planType Тип плана подписки
     * @param string $endDate Дата окончания подписки
     * @return array Результат операции
     */
    public function createSubscriptionExpiryNotification($userId, $planType, $endDate) {
        // Получаем название плана
        $subscriptionModel = new Subscription();
        $plans = $subscriptionModel->getPlans();
        $planName = $plans[$planType]['name'] ?? $planType;

        // Форматируем дату
        $formattedDate = date('d.m.Y', strtotime($endDate));

        $title = "Истекает подписка \"{$planName}\"";
        $message = "Ваша подписка \"{$planName}\" истекает {$formattedDate}. Не забудьте продлить её, чтобы сохранить доступ к читу.";

        return $this->createNotification($userId, $title, $message, 'warning');
    }
}
