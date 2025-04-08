<?php
/**
 * Скрипт для исправления отсутствующих таблиц в базе данных
 */

require_once __DIR__ . '/config/init.php';

header('Content-Type: text/plain');

// Получаем конфигурацию
$config = require __DIR__ . '/config/config.php';
$db = Database::getInstance();

echo "Начинаем исправление отсутствующих таблиц...\n";

$driver = $config['database']['driver'];

// Создание таблиц для SQLite
if ($driver === 'sqlite') {
    // Создание таблицы отзывов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                cheat_version_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                review_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_approved INTEGER DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
            )
        ");
        echo "Таблица reviews создана (SQLite).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы reviews: " . $e->getMessage() . "\n";
    }

    // Создание таблицы реферальных кодов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS referral_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                code VARCHAR(20) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "Таблица referral_codes создана (SQLite).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы referral_codes: " . $e->getMessage() . "\n";
    }

    // Создание таблицы рефералов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS referrals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                referrer_id INTEGER NOT NULL,
                referred_id INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                bonus_earned INTEGER DEFAULT 0,
                referral_code_id INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
            )
        ");
        echo "Таблица referrals создана (SQLite).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы referrals: " . $e->getMessage() . "\n";
    }

    // Добавление поля referral_code_id в таблицу пользователей, если его нет
    try {
        $db->query("
            ALTER TABLE users ADD COLUMN referral_code_id INTEGER DEFAULT NULL
        ");
        echo "Поле referral_code_id добавлено в таблицу users (SQLite).\n";
    } catch (Exception $e) {
        echo "Поле referral_code_id уже существует или произошла ошибка: " . $e->getMessage() . "\n";
    }

} else if ($driver === 'mysql') {
    // Создание таблицы отзывов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                cheat_version_id INT NOT NULL,
                rating INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                review_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_approved TINYINT(1) DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "Таблица reviews создана (MySQL).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы reviews: " . $e->getMessage() . "\n";
    }

    // Создание таблицы реферальных кодов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS referral_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                code VARCHAR(20) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "Таблица referral_codes создана (MySQL).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы referral_codes: " . $e->getMessage() . "\n";
    }

    // Создание таблицы рефералов
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS referrals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                referrer_id INT NOT NULL,
                referred_id INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                bonus_earned INT DEFAULT 0,
                referral_code_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "Таблица referrals создана (MySQL).\n";
    } catch (Exception $e) {
        echo "Ошибка при создании таблицы referrals: " . $e->getMessage() . "\n";
    }

    // Проверяем существует ли поле referral_code_id в таблице users
    try {
        // Проверяем существование колонки
        $columnExists = false;
        $columns = $db->fetchAll("SHOW COLUMNS FROM users");
        foreach ($columns as $column) {
            if ($column['Field'] === 'referral_code_id') {
                $columnExists = true;
                break;
            }
        }

        if (!$columnExists) {
            try {
                $db->query("
                    ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL
                ");
                echo "Поле referral_code_id добавлено в таблицу users (MySQL).\n";

                try {
                    $db->query("
                        ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
                    ");
                    echo "Внешний ключ для referral_code_id добавлен.\n";
                } catch (Exception $e) {
                    echo "Внешний ключ не добавлен: " . $e->getMessage() . "\n";
                    echo "Это не критическая ошибка и может быть проигнорирована.\n";
                }
            } catch (Exception $e) {
                echo "Ошибка при добавлении поля referral_code_id: " . $e->getMessage() . "\n";
                echo "Эта ошибка может быть проигнорирована, если поле уже существует.\n";
            }
        } else {
            echo "Поле referral_code_id уже существует в таблице users.\n";
        }
    } catch (Exception $e) {
        echo "Ошибка при проверке поля referral_code_id: " . $e->getMessage() . "\n";
    }
}

echo "\nИсправление отсутствующих таблиц завершено.\n";
echo "Можете продолжить использование сайта.\n";
