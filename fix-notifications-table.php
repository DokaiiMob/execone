<?php
/**
 * Скрипт для исправления проблемы с таблицей уведомлений
 */

require_once __DIR__ . '/config/init.php';

header('Content-Type: text/plain');

// Получаем конфигурацию
$config = require __DIR__ . '/config/config.php';
$db = Database::getInstance();

echo "Начинаем исправление таблицы уведомлений...\n";

// Удаляем таблицу если она существует
try {
    $db->query("DROP TABLE IF EXISTS notifications");
    echo "Старая таблица уведомлений удалена.\n";
} catch (Exception $e) {
    echo "Ошибка при удалении таблицы: " . $e->getMessage() . "\n";
}

// Создаем новую таблицу
try {
    $driver = $config['database']['driver'];
    if ($driver === 'sqlite') {
        $db->query("
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
        $db->query("
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
    echo "Новая таблица уведомлений успешно создана.\n";
} catch (Exception $e) {
    echo "Ошибка при создании таблицы: " . $e->getMessage() . "\n";
}

// Создаем тестовое уведомление для проверки
try {
    // Получаем первого пользователя в системе
    $user = $db->fetch("SELECT id FROM users ORDER BY id LIMIT 1");

    if ($user && isset($user['id'])) {
        $notification = [
            'user_id' => $user['id'],
            'title' => 'Тестовое уведомление',
            'message' => 'Это тестовое уведомление для проверки работы таблицы.',
            'type' => 'info',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->insert('notifications', $notification);
        echo "Создано тестовое уведомление для пользователя ID: {$user['id']}.\n";
    } else {
        echo "Не найдено пользователей для создания тестового уведомления.\n";
    }
} catch (Exception $e) {
    echo "Ошибка при создании тестового уведомления: " . $e->getMessage() . "\n";
}

echo "\nИсправление таблицы уведомлений завершено.\n";
echo "Можете продолжить использование сайта.\n";
