<?php

class Database {
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->connect();
    }

    /**
     * Получение единственного экземпляра класса (паттерн Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Подключение к базе данных в зависимости от выбранного драйвера
     */
    private function connect() {
        $driver = $this->config['database']['driver'];

        try {
            if ($driver === 'sqlite') {
                $dbPath = $this->config['database']['sqlite']['path'];
                $directory = dirname($dbPath);

                // Создаем директорию для SQLite файла, если она не существует
                if (!file_exists($directory)) {
                    if (!mkdir($directory, 0755, true)) {
                        throw new Exception("Не удалось создать директорию для SQLite базы данных: {$directory}");
                    }
                }

                // Проверяем доступность директории для записи
                if (!is_writable($directory)) {
                    throw new Exception("Директория {$directory} недоступна для записи");
                }

                // Инициализируем SQLite соединение
                $this->pdo = new PDO('sqlite:' . $dbPath);
            } else if ($driver === 'mysql') {
                $config = $this->config['database']['mysql'];

                // Проверяем наличие всех необходимых параметров для MySQL
                if (!isset($config['host']) || !isset($config['database']) ||
                    !isset($config['charset']) || !isset($config['username'])) {
                    throw new Exception("Отсутствуют необходимые параметры для подключения к MySQL");
                }

                // Инициализируем MySQL соединение
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                $this->pdo = new PDO($dsn, $config['username'], $config['password'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                throw new Exception("Неподдерживаемый драйвер базы данных: {$driver}");
            }

            // Общие настройки для всех типов подключений
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errorMsg = "Ошибка подключения к базе данных: " . $e->getMessage();
            error_log($errorMsg);
            die($errorMsg);
        } catch (Exception $e) {
            $errorMsg = "Ошибка конфигурации базы данных: " . $e->getMessage();
            error_log($errorMsg);
            die($errorMsg);
        }
    }

    /**
     * Выполнение запроса к базе данных
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $errorMsg = "Ошибка выполнения запроса: " . $e->getMessage();
            $errorMsg .= "\nЗапрос: " . $sql;
            if (!empty($params)) {
                $errorMsg .= "\nПараметры: " . print_r($params, true);
            }
            error_log($errorMsg);
            die($errorMsg);
        }
    }

    /**
     * Получение одной строки из результата запроса
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение всех строк из результата запроса
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение значения из одной ячейки результата запроса
     */
    public function fetchColumn($sql, $params = [], $column = 0) {
        return $this->query($sql, $params)->fetchColumn($column);
    }

    /**
     * Выполнение INSERT запроса и возвращение ID вставленной записи
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    /**
     * Выполнение UPDATE запроса
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $params[] = $value;
        }

        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $this->query($sql, array_merge($params, $whereParams));
    }

    /**
     * Выполнение DELETE запроса
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
    }

    /**
     * Получение PDO объекта для прямого использования
     */
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Создание таблиц для SQLite базы данных
     */
    public function initializeSQLiteDatabase() {
        // Создание таблицы пользователей
        $this->query("
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

        // Создание таблицы подписок
        $this->query("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                plan_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_date TIMESTAMP NOT NULL,
                payment_id VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Создание таблицы платежей
        $this->query("
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL,
                payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                transaction_id VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Создание таблицы версий чита
        $this->query("
            CREATE TABLE IF NOT EXISTS cheat_versions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                version VARCHAR(50) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                description TEXT,
                required_plan VARCHAR(20) NOT NULL DEFAULT 'basic',
                is_active INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Создание таблицы логов загрузок
        $this->query("
            CREATE TABLE IF NOT EXISTS download_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                cheat_version_id INTEGER NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
            )
        ");

        // Создание таблицы уведомлений
        $this->query("
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

        // Создание таблицы отзывов
        $this->query("
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

        // Создание таблицы реферальных кодов
        $this->query("
            CREATE TABLE IF NOT EXISTS referral_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                code VARCHAR(20) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Создание таблицы рефералов
        $this->query("
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

        // Проверяем существует ли поле referral_code_id в таблице users
        try {
            // Проверяем существование колонки в SQLite
            $columnExists = false;
            $userTableInfo = $this->fetchAll("PRAGMA table_info(users)");
            foreach ($userTableInfo as $column) {
                if ($column['name'] === 'referral_code_id') {
                    $columnExists = true;
                    break;
                }
            }

            if (!$columnExists) {
                $this->query("
                    ALTER TABLE users ADD COLUMN referral_code_id INTEGER DEFAULT NULL
                ");
                // SQLite не поддерживает добавление ограничения FOREIGN KEY через ALTER TABLE,
                // поэтому внешний ключ будет просто в виде поля
            }
        } catch (Exception $e) {
            // Поле уже может существовать, игнорируем ошибку
        }
    }

    /**
     * Создание таблиц для MySQL базы данных
     */
    public function initializeMySQLDatabase() {
        // Создание таблицы пользователей
        $this->query("
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

        // Создание таблицы подписок
        $this->query("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                plan_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_date TIMESTAMP NOT NULL,
                payment_id VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Создание таблицы платежей
        $this->query("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL,
                payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                transaction_id VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Создание таблицы версий чита
        $this->query("
            CREATE TABLE IF NOT EXISTS cheat_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(50) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                description TEXT,
                required_plan VARCHAR(20) NOT NULL DEFAULT 'basic',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Создание таблицы логов загрузок
        $this->query("
            CREATE TABLE IF NOT EXISTS download_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                cheat_version_id INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Создание таблицы уведомлений
        $this->query("
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

        // Создание таблицы отзывов
        $this->query("
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

        // Создание таблицы реферальных кодов
        $this->query("
            CREATE TABLE IF NOT EXISTS referral_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                code VARCHAR(20) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Создание таблицы рефералов
        $this->query("
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

        // Проверяем существует ли поле referral_code_id в таблице users
        try {
            // Проверяем существование колонки в MySQL
            $columnExists = false;
            $columns = $this->fetchAll("SHOW COLUMNS FROM users");
            foreach ($columns as $column) {
                if ($column['Field'] === 'referral_code_id') {
                    $columnExists = true;
                    break;
                }
            }

            if (!$columnExists) {
                $this->query("
                    ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL
                ");
                try {
                    $this->query("
                        ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
                    ");
                } catch (Exception $e) {
                    // Внешний ключ может уже существовать, игнорируем ошибку
                }
            }
        } catch (Exception $e) {
            // Поле уже может существовать, игнорируем ошибку
        }
    }

    /**
     * Инициализация базы данных
     */
    public function initializeDatabase() {
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            $this->initializeSQLiteDatabase();
        } else if ($driver === 'mysql') {
            $this->initializeMySQLDatabase();
        }
    }
}
