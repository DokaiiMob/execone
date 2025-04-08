<?php
/**
 * Системная проверка сайта
 * Этот файл проверяет целостность системы и исправляет найденные проблемы
 */

require_once __DIR__ . '/config/init.php';

header('Content-Type: text/plain');
set_time_limit(300); // Увеличиваем время выполнения до 5 минут

// Получаем конфигурацию
$config = require __DIR__ . '/config/config.php';
$db = Database::getInstance();

echo "=== НАЧАЛО СИСТЕМНОЙ ПРОВЕРКИ ===\n\n";

// Проверка PHP версии и модулей
echo "Проверка PHP и модулей:\n";
echo "- PHP версия: " . phpversion() . "\n";
echo "- PDO доступен: " . (extension_loaded('pdo') ? "Да" : "Нет") . "\n";
echo "- PDO SQLite доступен: " . (extension_loaded('pdo_sqlite') ? "Да" : "Нет") . "\n";
echo "- PDO MySQL доступен: " . (extension_loaded('pdo_mysql') ? "Да" : "Нет") . "\n";
echo "- GD доступен: " . (extension_loaded('gd') ? "Да" : "Нет") . "\n";
echo "- Fileinfo доступен: " . (extension_loaded('fileinfo') ? "Да" : "Нет") . "\n";
echo "- JSON доступен: " . (extension_loaded('json') ? "Да" : "Нет") . "\n\n";

// Проверка конфигурации
echo "Проверка конфигурации:\n";
$driver = $config['database']['driver'];
echo "- Драйвер базы данных: {$driver}\n";

if ($driver === 'sqlite') {
    $dbPath = $config['database']['sqlite']['path'];
    echo "- Путь к SQLite: {$dbPath}\n";
    $directory = dirname($dbPath);
    echo "- Директория для SQLite существует: " . (file_exists($directory) ? "Да" : "Нет") . "\n";
    echo "- Директория доступна для записи: " . (is_writable($directory) ? "Да" : "Нет") . "\n";
} else if ($driver === 'mysql') {
    $mysqlConfig = $config['database']['mysql'];
    echo "- MySQL хост: {$mysqlConfig['host']}\n";
    echo "- MySQL база данных: {$mysqlConfig['database']}\n";
    echo "- MySQL пользователь: {$mysqlConfig['username']}\n";
    echo "- MySQL charset: {$mysqlConfig['charset']}\n";
}

echo "\n";

// Проверка соединения с базой данных
echo "Проверка соединения с базой данных:\n";
try {
    $db->query("SELECT 1");
    echo "- Соединение с базой данных успешно установлено\n\n";
} catch (Exception $e) {
    echo "- ОШИБКА: Не удалось подключиться к базе данных: " . $e->getMessage() . "\n\n";
    die("Критическая ошибка. Дальнейшая проверка невозможна.\n");
}

// Проверка и создание директорий для загрузок
echo "Проверка директорий для загрузок:\n";
$uploadDirs = [
    $config['uploads']['cheat_files']['path'],
    $config['uploads']['user_avatars']['path']
];

foreach ($uploadDirs as $dir) {
    echo "- Директория {$dir}: ";
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Создана успешно\n";
        } else {
            echo "ОШИБКА создания\n";
        }
    } else {
        echo "Уже существует\n";
    }
}
echo "\n";

// Проверка и инициализация таблиц
echo "Проверка таблиц базы данных:\n";

// Список ожидаемых таблиц
$expectedTables = [
    'users', 'subscriptions', 'payments', 'cheat_versions',
    'download_logs', 'notifications', 'reviews', 'referral_codes',
    'referrals'
];

try {
    // Для MySQL проверяем через SHOW TABLES
    if ($driver === 'mysql') {
        $existingTables = $db->fetchAll("SHOW TABLES");
        $tablesList = [];
        foreach ($existingTables as $tableRow) {
            $tablesList[] = reset($tableRow); // Получаем первый элемент строки
        }

        foreach ($expectedTables as $table) {
            echo "- Таблица {$table}: ";
            if (in_array($table, $tablesList)) {
                echo "Существует\n";
            } else {
                echo "Отсутствует, попытка создания...\n";
                // Запускаем инициализацию базы данных
                $db->initializeDatabase();
                echo "  Таблицы созданы через initializeDatabase()\n";
                break; // Прерываем цикл, так как все таблицы созданы
            }
        }
    }
    // Для SQLite проверяем через sqlite_master
    else if ($driver === 'sqlite') {
        $tablesList = [];
        $existingTables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
        foreach ($existingTables as $tableRow) {
            $tablesList[] = $tableRow['name'];
        }

        foreach ($expectedTables as $table) {
            echo "- Таблица {$table}: ";
            if (in_array($table, $tablesList)) {
                echo "Существует\n";
            } else {
                echo "Отсутствует, попытка создания...\n";
                // Запускаем инициализацию базы данных
                $db->initializeDatabase();
                echo "  Таблицы созданы через initializeDatabase()\n";
                break; // Прерываем цикл, так как все таблицы созданы
            }
        }
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке таблиц: " . $e->getMessage() . "\n";
}
echo "\n";

// Проверка наличия администратора
echo "Проверка наличия администратора:\n";
try {
    $adminCount = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    echo "- Количество администраторов: {$adminCount}\n";
    if ($adminCount == 0) {
        echo "- Создание администратора по умолчанию...\n";
        $userModel = new User();
        $adminUsername = 'admin';
        $adminEmail = 'admin@example.com';
        $adminPassword = 'admin123';

        $result = $userModel->register($adminUsername, $adminEmail, $adminPassword);
        if ($result['success']) {
            $userModel->changeUserRole($result['user_id'], 'admin');
            echo "  Администратор успешно создан:\n";
            echo "  Логин: {$adminUsername}\n";
            echo "  Email: {$adminEmail}\n";
            echo "  Пароль: {$adminPassword}\n";
            echo "  ВНИМАНИЕ: Рекомендуется сразу сменить пароль администратора после входа в систему.\n";
        } else {
            echo "  ОШИБКА при создании администратора: {$result['message']}\n";
        }
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке администраторов: " . $e->getMessage() . "\n";
}
echo "\n";

// Проверка наличия демо-версий чита
echo "Проверка наличия демо-версий чита:\n";
try {
    $versionsCount = $db->fetchColumn("SELECT COUNT(*) FROM cheat_versions");
    echo "- Количество версий чита: {$versionsCount}\n";

    if ($versionsCount == 0) {
        echo "- Создание демо-версий чита...\n";

        $versionsData = [
            [
                'version' => '1.0.0',
                'description' => "Первая версия чита для SAMP\n* Базовый аимбот\n* Простой WallHack\n* Базовый SpeedHack",
                'required_plan' => 'basic',
                'is_active' => 1,
                'file_path' => 'demo_basic_1.0.0.zip',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'version' => '1.5.0',
                'description' => "Улучшенная версия чита\n* Улучшенный аимбот с настройками\n* Расширенный WallHack с цветовыми опциями\n* Улучшенный SpeedHack\n* Добавлен NoRecoil",
                'required_plan' => 'premium',
                'is_active' => 1,
                'file_path' => 'demo_premium_1.5.0.zip',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'version' => '2.0.0',
                'description' => "VIP версия чита\n* Максимальная настройка аимбота\n* Полный WallHack с расширенными настройками\n* Улучшенный SpeedHack\n* Улучшенный NoRecoil\n* ESP функции\n* Anti-Stun\n* Эксклюзивные VIP функции",
                'required_plan' => 'vip',
                'is_active' => 1,
                'file_path' => 'demo_vip_2.0.0.zip',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($versionsData as $versionData) {
            $db->insert('cheat_versions', $versionData);
            echo "  Создана версия чита {$versionData['version']} ({$versionData['required_plan']})\n";
        }
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке версий чита: " . $e->getMessage() . "\n";
}
echo "\n";

// Проверка совместимости кода и структуры таблиц
echo "Проверка совместимости кода и структуры таблиц:\n";

// Проверка столбца referral_code_id в таблице users
try {
    $userFieldsCheck = false;

    if ($driver === 'mysql') {
        $columns = $db->fetchAll("SHOW COLUMNS FROM users");
        foreach ($columns as $column) {
            if ($column['Field'] === 'referral_code_id') {
                $userFieldsCheck = true;
                break;
            }
        }
    } else if ($driver === 'sqlite') {
        $userTableInfo = $db->fetchAll("PRAGMA table_info(users)");
        foreach ($userTableInfo as $column) {
            if ($column['name'] === 'referral_code_id') {
                $userFieldsCheck = true;
                break;
            }
        }
    }

    echo "- Поле referral_code_id в таблице users: ";
    if ($userFieldsCheck) {
        echo "Существует\n";
    } else {
        echo "Отсутствует, добавление...\n";
        try {
            if ($driver === 'mysql') {
                $db->query("ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL");

                try {
                    $db->query("ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL");
                } catch (Exception $e) {
                    echo "  Примечание: Внешний ключ не добавлен: " . $e->getMessage() . "\n";
                }
            } else if ($driver === 'sqlite') {
                $db->query("ALTER TABLE users ADD COLUMN referral_code_id INTEGER DEFAULT NULL");
            }
            echo "  Поле добавлено успешно\n";
        } catch (Exception $e) {
            echo "  ОШИБКА при добавлении поля: " . $e->getMessage() . "\n";
            echo "  Эта ошибка может быть проигнорирована, если поле уже существует\n";
        }
    }
} catch (Exception $e) {
    echo "- ОШИБКА при проверке структуры таблицы users: " . $e->getMessage() . "\n";
}

echo "\n=== СИСТЕМНАЯ ПРОВЕРКА ЗАВЕРШЕНА ===\n";
echo "Сайт готов к использованию! Вы можете закрыть эту страницу.\n";
