<?php
/**
 * Скрипт для исправления таблицы пользователей
 * Проверяет и исправляет проблемы с таблицей users, включая поле referral_code_id
 */

require_once __DIR__ . '/config/init.php';

header('Content-Type: text/plain');

// Получаем конфигурацию
$config = require __DIR__ . '/config/config.php';
$db = Database::getInstance();

echo "=== ПРОВЕРКА И ИСПРАВЛЕНИЕ ТАБЛИЦЫ ПОЛЬЗОВАТЕЛЕЙ ===\n\n";

$driver = $config['database']['driver'];
echo "Используемый драйвер базы данных: {$driver}\n\n";

// Проверка существования таблицы users
echo "Проверка таблицы users:\n";
try {
    if ($driver === 'mysql') {
        $table = $db->fetch("SHOW TABLES LIKE 'users'");
        if (!$table) {
            echo "- Таблица users не существует, создание...\n";
            $db->query("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    avatar VARCHAR(255) DEFAULT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'user',
                    email_verified TINYINT(1) DEFAULT 0,
                    verification_token VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "  Таблица users создана\n";
        } else {
            echo "- Таблица users существует\n";
        }
    } else if ($driver === 'sqlite') {
        $table = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$table) {
            echo "- Таблица users не существует, создание...\n";
            $db->query("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    avatar VARCHAR(255) DEFAULT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'user',
                    email_verified INTEGER DEFAULT 0,
                    verification_token VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            echo "  Таблица users создана\n";
        } else {
            echo "- Таблица users существует\n";
        }
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке таблицы users: " . $e->getMessage() . "\n";
}

// Проверка столбца referral_code_id в таблице users
echo "\nПроверка столбца referral_code_id в таблице users:\n";
try {
    $columnExists = false;

    if ($driver === 'mysql') {
        $columns = $db->fetchAll("SHOW COLUMNS FROM users");
        foreach ($columns as $column) {
            if ($column['Field'] === 'referral_code_id') {
                $columnExists = true;
                break;
            }
        }
    } else if ($driver === 'sqlite') {
        $columns = $db->fetchAll("PRAGMA table_info(users)");
        foreach ($columns as $column) {
            if ($column['name'] === 'referral_code_id') {
                $columnExists = true;
                break;
            }
        }
    }

    if (!$columnExists) {
        echo "- Столбец referral_code_id не существует, добавление...\n";
        try {
            if ($driver === 'mysql') {
                $db->query("ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL");
                echo "  Столбец referral_code_id добавлен\n";

                // Проверяем существование таблицы referral_codes
                $refCodesTable = $db->fetch("SHOW TABLES LIKE 'referral_codes'");
                if ($refCodesTable) {
                    try {
                        // Пробуем добавить внешний ключ, но не паникуем, если не получится
                        $db->query("ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL");
                        echo "  Внешний ключ для столбца referral_code_id добавлен\n";
                    } catch (Exception $e) {
                        echo "  Предупреждение: Не удалось добавить внешний ключ: " . $e->getMessage() . "\n";
                        echo "  Это не критическая ошибка и может быть проигнорирована\n";
                    }
                } else {
                    echo "  Таблица referral_codes не существует, внешний ключ не добавлен\n";
                }
            } else if ($driver === 'sqlite') {
                $db->query("ALTER TABLE users ADD COLUMN referral_code_id INTEGER DEFAULT NULL");
                echo "  Столбец referral_code_id добавлен\n";
            }
        } catch (Exception $e) {
            echo "- ОШИБКА при добавлении столбца referral_code_id: " . $e->getMessage() . "\n";
        }
    } else {
        echo "- Столбец referral_code_id уже существует\n";
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке столбца referral_code_id: " . $e->getMessage() . "\n";
}

// Проверяем таблицу referral_codes
echo "\nПроверка таблицы referral_codes:\n";
try {
    $tableExists = false;

    if ($driver === 'mysql') {
        $table = $db->fetch("SHOW TABLES LIKE 'referral_codes'");
        $tableExists = !empty($table);
    } else if ($driver === 'sqlite') {
        $table = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='referral_codes'");
        $tableExists = !empty($table);
    }

    if (!$tableExists) {
        echo "- Таблица referral_codes не существует, создание...\n";
        try {
            if ($driver === 'mysql') {
                $db->query("
                    CREATE TABLE IF NOT EXISTS referral_codes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        code VARCHAR(20) NOT NULL UNIQUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } else if ($driver === 'sqlite') {
                $db->query("
                    CREATE TABLE IF NOT EXISTS referral_codes (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        code VARCHAR(20) NOT NULL UNIQUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )
                ");
            }
            echo "  Таблица referral_codes создана\n";
        } catch (Exception $e) {
            echo "- ОШИБКА при создании таблицы referral_codes: " . $e->getMessage() . "\n";
        }
    } else {
        echo "- Таблица referral_codes существует\n";
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке таблицы referral_codes: " . $e->getMessage() . "\n";
}

echo "\n=== ПРОВЕРКА И ИСПРАВЛЕНИЕ ЗАВЕРШЕНЫ ===\n";
echo "Таблица пользователей проверена и исправлена. Вы можете продолжить использование сайта.\n";
