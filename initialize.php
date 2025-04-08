<?php
/**
 * Файл для первичной инициализации проекта
 */

require_once __DIR__ . '/config/init.php';

header('Content-Type: text/plain');

// Проверка наличия администраторов в системе
$userModel = new User();
$db = Database::getInstance();

$adminExists = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");

if ($adminExists && $adminExists['count'] > 0) {
    echo "В системе уже есть администратор.\n";
    echo "Если вы хотите создать нового администратора, воспользуйтесь админ-панелью.\n";
    exit;
}

// Создание директорий для загрузки файлов, если они не существуют
$config = require __DIR__ . '/config/config.php';
$uploadDirs = [
    $config['uploads']['cheat_files']['path'],
    $config['uploads']['user_avatars']['path']
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Создана директория: {$dir}\n";
        } else {
            echo "Ошибка создания директории: {$dir}\n";
        }
    } else {
        echo "Директория уже существует: {$dir}\n";
    }
}

// Создание администратора
$adminUsername = 'admin';
$adminEmail = 'admin@example.com';
$adminPassword = 'admin123';

// Создание таблиц в базе данных, если они не существуют
$db->initializeDatabase();
echo "База данных инициализирована.\n";

// Убедимся, что таблица notifications создана
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
echo "Таблица уведомлений создана.\n";

// Регистрация администратора
$result = $userModel->register($adminUsername, $adminEmail, $adminPassword);

if ($result['success']) {
    // Устанавливаем роль администратора
    $userModel->changeUserRole($result['user_id'], 'admin');

    echo "Администратор успешно создан:\n";
    echo "Логин: {$adminUsername}\n";
    echo "Email: {$adminEmail}\n";
    echo "Пароль: {$adminPassword}\n";
    echo "\nДля безопасности рекомендуется сразу сменить пароль администратора после входа в систему.\n";
} else {
    echo "Ошибка при создании администратора: {$result['message']}\n";
}

// Создание тестовых версий чита
$cheatVersionModel = new CheatVersion();

// Проверяем, есть ли уже версии чита в базе
$versionsCount = $db->fetchColumn("SELECT COUNT(*) FROM cheat_versions");

if ($versionsCount == 0) {
    echo "\nСоздание тестовых версий чита...\n";

    // Пример создания тестовых данных (только для демонстрации)
    $versionsData = [
        [
            'version' => '1.0.0',
            'description' => "Первая версия чита для SAMP\n* Базовый аимбот\n* Простой WallHack\n* Базовый SpeedHack",
            'required_plan' => 'basic',
            'is_active' => 1,
            'file_path' => 'demo_basic_1.0.0.zip'
        ],
        [
            'version' => '1.5.0',
            'description' => "Улучшенная версия чита\n* Улучшенный аимбот с настройками\n* Расширенный WallHack с цветовыми опциями\n* Улучшенный SpeedHack\n* Добавлен NoRecoil",
            'required_plan' => 'premium',
            'is_active' => 1,
            'file_path' => 'demo_premium_1.5.0.zip'
        ],
        [
            'version' => '2.0.0',
            'description' => "VIP версия чита\n* Максимальная настройка аимбота\n* Полный WallHack с расширенными настройками\n* Улучшенный SpeedHack\n* Улучшенный NoRecoil\n* ESP функции\n* Anti-Stun\n* Эксклюзивные VIP функции",
            'required_plan' => 'vip',
            'is_active' => 1,
            'file_path' => 'demo_vip_2.0.0.zip'
        ]
    ];

    foreach ($versionsData as $versionData) {
        $db->insert('cheat_versions', $versionData);
        echo "Создана тестовая версия чита: {$versionData['version']} ({$versionData['required_plan']})\n";
    }
} else {
    echo "\nВ базе уже есть версии чита, пропускаем создание тестовых данных.\n";
}

echo "\nИнициализация завершена. Теперь вы можете войти в админ-панель, используя созданные учетные данные.\n";
echo "URL для входа: http://localhost/login.php\n";
