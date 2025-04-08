<?php

class Loyalty {
    private $db;
    private $config;

    // Уровни лояльности
    const LEVEL_BRONZE = 'bronze';
    const LEVEL_SILVER = 'silver';
    const LEVEL_GOLD = 'gold';
    const LEVEL_PLATINUM = 'platinum';

    // Минимальное количество дней подписки для каждого уровня
    const DAYS_BRONZE = 0;    // Базовый уровень
    const DAYS_SILVER = 60;   // 2 месяца
    const DAYS_GOLD = 180;    // 6 месяцев
    const DAYS_PLATINUM = 365; // 1 год

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицу истории лояльности, если она не существует
        $this->createLoyaltyTable();
    }

    /**
     * Создание таблицы истории лояльности
     */
    private function createLoyaltyTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            // Создаем таблицу истории лояльности
            $this->db->query("
                CREATE TABLE IF NOT EXISTS loyalty_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    subscription_days INTEGER NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Добавляем колонку уровня лояльности в таблицу users
            $this->db->query("
                PRAGMA foreign_keys = off;

                BEGIN TRANSACTION;

                CREATE TABLE IF NOT EXISTS users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username VARCHAR(255) NOT NULL UNIQUE,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    avatar VARCHAR(255) DEFAULT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'user',
                    email_verified INTEGER DEFAULT 0,
                    verification_token VARCHAR(255) DEFAULT NULL,
                    bonus_balance INTEGER DEFAULT 0,
                    loyalty_days INTEGER DEFAULT 0,
                    loyalty_level VARCHAR(20) DEFAULT 'bronze',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                INSERT INTO users_new SELECT
                    id, username, email, password, avatar, role,
                    email_verified, verification_token,
                    COALESCE(bonus_balance, 0) AS bonus_balance,
                    0 AS loyalty_days,
                    'bronze' AS loyalty_level,
                    created_at, updated_at
                FROM users;

                DROP TABLE IF EXISTS users;

                ALTER TABLE users_new RENAME TO users;

                COMMIT;

                PRAGMA foreign_keys = on;
            ");
        } else if ($driver === 'mysql') {
            // Создаем таблицу истории лояльности
            $this->db->query("
                CREATE TABLE IF NOT EXISTS loyalty_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    subscription_days INT NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Проверяем наличие колонок loyalty_days и loyalty_level в таблице users
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'loyalty_days'");
            if ($result->rowCount() === 0) {
                // Добавляем колонку, если ее нет
                $this->db->query("ALTER TABLE users ADD COLUMN loyalty_days INT DEFAULT 0");
            }

            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'loyalty_level'");
            if ($result->rowCount() === 0) {
                // Добавляем колонку, если ее нет
                $this->db->query("ALTER TABLE users ADD COLUMN loyalty_level VARCHAR(20) DEFAULT 'bronze'");
            }
        }
    }

    /**
     * Добавление дней лояльности пользователю
     *
     * @param int $userId ID пользователя
     * @param int $days Количество дней
     * @param string $actionType Тип действия
     * @param string $description Описание действия
     * @return array Результат операции
     */
    public function addLoyaltyDays($userId, $days, $actionType, $description = '') {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Обновляем количество дней лояльности
        $currentDays = $user['loyalty_days'] ?? 0;
        $newDays = $currentDays + $days;

        // Определяем новый уровень лояльности
        $currentLevel = $user['loyalty_level'] ?? self::LEVEL_BRONZE;
        $newLevel = $this->calculateLoyaltyLevel($newDays);

        // Обновляем данные пользователя
        $this->db->update('users', [
            'loyalty_days' => $newDays,
            'loyalty_level' => $newLevel
        ], 'id = ?', [$userId]);

        // Добавляем запись в историю лояльности
        $this->db->insert('loyalty_history', [
            'user_id' => $userId,
            'subscription_days' => $days,
            'action_type' => $actionType,
            'description' => $description
        ]);

        // Если уровень лояльности изменился, отправляем уведомление
        if ($currentLevel !== $newLevel) {
            $this->sendLevelUpNotification($userId, $newLevel);
        }

        return [
            'success' => true,
            'previous_days' => $currentDays,
            'new_days' => $newDays,
            'previous_level' => $currentLevel,
            'new_level' => $newLevel
        ];
    }

    /**
     * Расчет уровня лояльности на основе количества дней
     *
     * @param int $days Количество дней
     * @return string Уровень лояльности
     */
    public function calculateLoyaltyLevel($days) {
        if ($days >= self::DAYS_PLATINUM) {
            return self::LEVEL_PLATINUM;
        } elseif ($days >= self::DAYS_GOLD) {
            return self::LEVEL_GOLD;
        } elseif ($days >= self::DAYS_SILVER) {
            return self::LEVEL_SILVER;
        } else {
            return self::LEVEL_BRONZE;
        }
    }

    /**
     * Отправка уведомления о повышении уровня лояльности
     *
     * @param int $userId ID пользователя
     * @param string $newLevel Новый уровень лояльности
     */
    private function sendLevelUpNotification($userId, $newLevel) {
        $notificationModel = new Notification();

        $levelNames = [
            self::LEVEL_BRONZE => 'Бронзовый',
            self::LEVEL_SILVER => 'Серебряный',
            self::LEVEL_GOLD => 'Золотой',
            self::LEVEL_PLATINUM => 'Платиновый'
        ];

        $levelBenefits = [
            self::LEVEL_BRONZE => 'базовые преимущества',
            self::LEVEL_SILVER => 'скидка 5% на продление подписки',
            self::LEVEL_GOLD => 'скидка 10% на продление подписки и приоритетная поддержка',
            self::LEVEL_PLATINUM => 'скидка 15% на продление подписки, VIP-поддержка и эксклюзивный доступ к бета-версиям'
        ];

        $title = 'Повышен уровень лояльности!';
        $message = 'Поздравляем! Ваш уровень в программе лояльности повышен до "' . $levelNames[$newLevel] . '". ' .
                   'Теперь вам доступны новые привилегии: ' . $levelBenefits[$newLevel] . '.';

        $notificationModel->createNotification($userId, $title, $message, 'success');
    }

    /**
     * Получение скидки в зависимости от уровня лояльности
     *
     * @param string $loyaltyLevel Уровень лояльности
     * @return float Скидка в процентах
     */
    public function getLoyaltyDiscount($loyaltyLevel) {
        switch ($loyaltyLevel) {
            case self::LEVEL_PLATINUM:
                return 15.0; // 15%
            case self::LEVEL_GOLD:
                return 10.0; // 10%
            case self::LEVEL_SILVER:
                return 5.0; // 5%
            default:
                return 0.0; // 0%
        }
    }

    /**
     * Получение привилегий для уровня лояльности
     *
     * @param string $loyaltyLevel Уровень лояльности
     * @return array Список привилегий
     */
    public function getLoyaltyBenefits($loyaltyLevel) {
        $benefits = [
            // Бронзовый уровень (базовый)
            self::LEVEL_BRONZE => [
                'Стандартная поддержка',
                'Доступ к базовым функциям'
            ],

            // Серебряный уровень
            self::LEVEL_SILVER => [
                'Скидка 5% на продление подписки',
                'Стандартная поддержка',
                'Доступ к базовым функциям'
            ],

            // Золотой уровень
            self::LEVEL_GOLD => [
                'Скидка 10% на продление подписки',
                'Приоритетная поддержка',
                'Доступ к базовым функциям',
                'Раннее уведомление о новых версиях'
            ],

            // Платиновый уровень
            self::LEVEL_PLATINUM => [
                'Скидка 15% на продление подписки',
                'VIP-поддержка 24/7',
                'Доступ к базовым функциям',
                'Раннее уведомление о новых версиях',
                'Доступ к бета-версиям',
                'Эксклюзивный статус в сообществе'
            ]
        ];

        return $benefits[$loyaltyLevel] ?? $benefits[self::LEVEL_BRONZE];
    }

    /**
     * Получение описания уровней лояльности
     *
     * @return array Описание уровней лояльности
     */
    public function getLoyaltyLevelsDescription() {
        return [
            self::LEVEL_BRONZE => [
                'name' => 'Бронзовый',
                'days' => self::DAYS_BRONZE,
                'icon' => 'medal',
                'color' => 'bronze',
                'description' => 'Начальный уровень для новых пользователей',
                'benefits' => $this->getLoyaltyBenefits(self::LEVEL_BRONZE)
            ],
            self::LEVEL_SILVER => [
                'name' => 'Серебряный',
                'days' => self::DAYS_SILVER,
                'icon' => 'medal',
                'color' => 'silver',
                'description' => 'Уровень для постоянных пользователей',
                'benefits' => $this->getLoyaltyBenefits(self::LEVEL_SILVER)
            ],
            self::LEVEL_GOLD => [
                'name' => 'Золотой',
                'days' => self::DAYS_GOLD,
                'icon' => 'medal',
                'color' => 'gold',
                'description' => 'Уровень для лояльных пользователей',
                'benefits' => $this->getLoyaltyBenefits(self::LEVEL_GOLD)
            ],
            self::LEVEL_PLATINUM => [
                'name' => 'Платиновый',
                'days' => self::DAYS_PLATINUM,
                'icon' => 'crown',
                'color' => 'platinum',
                'description' => 'Высший уровень для самых преданных пользователей',
                'benefits' => $this->getLoyaltyBenefits(self::LEVEL_PLATINUM)
            ]
        ];
    }

    /**
     * Получение истории лояльности пользователя
     *
     * @param int $userId ID пользователя
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array История лояльности
     */
    public function getUserLoyaltyHistory($userId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM loyalty_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Добавление дней лояльности при покупке подписки
     *
     * @param int $userId ID пользователя
     * @param string $planType Тип плана подписки
     * @param int $duration Длительность подписки в днях
     * @return array Результат операции
     */
    public function processSubscriptionPurchase($userId, $planType, $duration) {
        // Описание для истории лояльности
        $description = "Покупка подписки \"{$planType}\" на {$duration} дней";

        // Добавляем дни лояльности (равное количеству дней подписки)
        return $this->addLoyaltyDays($userId, $duration, 'subscription_purchase', $description);
    }

    /**
     * Получение данных о лояльности пользователя
     *
     * @param int $userId ID пользователя
     * @return array Данные о лояльности
     */
    public function getUserLoyaltyData($userId) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Получаем текущий уровень и количество дней
        $currentDays = $user['loyalty_days'] ?? 0;
        $currentLevel = $user['loyalty_level'] ?? self::LEVEL_BRONZE;

        // Получаем скидку для текущего уровня
        $discount = $this->getLoyaltyDiscount($currentLevel);

        // Получаем список привилегий
        $benefits = $this->getLoyaltyBenefits($currentLevel);

        // Получаем описание всех уровней
        $levels = $this->getLoyaltyLevelsDescription();

        // Определяем следующий уровень и прогресс
        $nextLevel = null;
        $daysForNextLevel = 0;
        $progress = 100; // По умолчанию 100%, если максимальный уровень

        if ($currentLevel === self::LEVEL_BRONZE) {
            $nextLevel = self::LEVEL_SILVER;
            $daysForNextLevel = self::DAYS_SILVER - $currentDays;
            $progress = min(100, ($currentDays / self::DAYS_SILVER) * 100);
        } elseif ($currentLevel === self::LEVEL_SILVER) {
            $nextLevel = self::LEVEL_GOLD;
            $daysForNextLevel = self::DAYS_GOLD - $currentDays;
            $progress = min(100, (($currentDays - self::DAYS_SILVER) / (self::DAYS_GOLD - self::DAYS_SILVER)) * 100);
        } elseif ($currentLevel === self::LEVEL_GOLD) {
            $nextLevel = self::LEVEL_PLATINUM;
            $daysForNextLevel = self::DAYS_PLATINUM - $currentDays;
            $progress = min(100, (($currentDays - self::DAYS_GOLD) / (self::DAYS_PLATINUM - self::DAYS_GOLD)) * 100);
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'loyalty_days' => $currentDays,
            'loyalty_level' => $currentLevel,
            'loyalty_level_name' => $levels[$currentLevel]['name'],
            'discount' => $discount,
            'benefits' => $benefits,
            'next_level' => $nextLevel,
            'days_for_next_level' => $daysForNextLevel,
            'progress' => round($progress, 1),
            'levels' => $levels
        ];
    }
}
