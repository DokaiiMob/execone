<?php

class Referral {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицу рефералов, если не существует
        $this->createReferralsTable();
    }

    /**
     * Создание таблицы рефералов в базе данных, если она не существует
     */
    private function createReferralsTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            // Создаем таблицу рефералов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS referrals (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    referrer_id INTEGER NOT NULL,
                    referred_id INTEGER NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    bonus_earned INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Создаем таблицу реферальных кодов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS referral_codes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    code VARCHAR(20) NOT NULL UNIQUE,
                    description VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Добавляем колонку для баланса бонусов пользователя в таблицу users
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
                    referral_code_id INTEGER DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
                );

                INSERT INTO users_new SELECT id, username, email, password, avatar, role, email_verified, verification_token, 0 AS bonus_balance, loyalty_days, loyalty_level, NULL as referral_code_id, created_at, updated_at FROM users;

                DROP TABLE IF EXISTS users;

                ALTER TABLE users_new RENAME TO users;

                COMMIT;

                PRAGMA foreign_keys = on;
            ");
        } else if ($driver === 'mysql') {
            // Создаем таблицу рефералов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS referrals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    referrer_id INT NOT NULL,
                    referred_id INT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    bonus_earned INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Создаем таблицу реферальных кодов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS referral_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    code VARCHAR(20) NOT NULL UNIQUE,
                    description VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Проверяем наличие колонки bonus_balance в таблице users
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'bonus_balance'");
            if ($result->rowCount() === 0) {
                // Добавляем колонку, если ее нет
                $this->db->query("ALTER TABLE users ADD COLUMN bonus_balance INT DEFAULT 0");
            }

            // Проверяем наличие колонки referral_code_id в таблице users
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'referral_code_id'");
            if ($result->rowCount() === 0) {
                // Добавляем колонку, если ее нет
                $this->db->query("ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL");
                $this->db->query("ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL");
            }
        }
    }

    /**
     * Генерация реферального кода для пользователя
     *
     * @param int $userId ID пользователя
     * @return array Результат операции и код
     */
    public function generateReferralCode($userId) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Проверяем, есть ли уже код у пользователя
        $existingCode = $this->getUserReferralCode($userId);

        if ($existingCode) {
            return ['success' => true, 'code' => $existingCode, 'message' => 'Реферальный код уже существует'];
        }

        // Генерируем уникальный код
        $code = $this->generateUniqueCode($userId);

        // Сохраняем код в базу данных
        $this->db->insert('referral_codes', [
            'user_id' => $userId,
            'code' => $code
        ]);

        return ['success' => true, 'code' => $code];
    }

    /**
     * Генерация уникального реферального кода
     *
     * @param int $userId ID пользователя
     * @return string Уникальный код
     */
    private function generateUniqueCode($userId) {
        // Получаем имя пользователя
        $userModel = new User();
        $user = $userModel->getUserById($userId);
        $username = $user['username'];

        // Создаем базовый код из имени пользователя (первые 5 символов, если возможно)
        $baseCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $username), 0, 5));

        // Если код слишком короткий, добавляем случайные символы
        if (strlen($baseCode) < 5) {
            $baseCode .= substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5 - strlen($baseCode));
        }

        // Добавляем случайные символы для уникальности
        $baseCode .= substr(md5(time() . $userId), 0, 5);

        // Проверяем, что код уникальный
        $existingCode = $this->db->fetch(
            "SELECT * FROM referral_codes WHERE code = ?",
            [$baseCode]
        );

        // Если код уже существует, генерируем новый
        if ($existingCode) {
            return $this->generateUniqueCode($userId);
        }

        return $baseCode;
    }

    /**
     * Получение реферального кода пользователя
     *
     * @param int $userId ID пользователя
     * @return string|false Реферальный код или false, если код не найден
     */
    public function getUserReferralCode($userId) {
        $code = $this->db->fetch(
            "SELECT code FROM referral_codes WHERE user_id = ?",
            [$userId]
        );

        return $code ? $code['code'] : false;
    }

    /**
     * Получение ID пользователя по реферальному коду
     *
     * @param string $code Реферальный код
     * @return int|false ID пользователя или false, если код не найден
     */
    public function getUserIdByCode($code) {
        $result = $this->db->fetch(
            "SELECT user_id FROM referral_codes WHERE code = ?",
            [$code]
        );

        return $result ? $result['user_id'] : false;
    }

    /**
     * Добавление реферала
     *
     * @param int $referrerId ID реферера (пригласившего)
     * @param int $referredId ID реферала (приглашенного)
     * @return array Результат операции
     */
    public function addReferral($referrerId, $referredId) {
        // Проверяем, что пользователи существуют
        $userModel = new User();
        $referrer = $userModel->getUserById($referrerId);
        $referred = $userModel->getUserById($referredId);

        if (!$referrer || !$referred) {
            return ['success' => false, 'message' => 'Один из пользователей не найден'];
        }

        // Проверяем, что реферер и реферал - разные пользователи
        if ($referrerId === $referredId) {
            return ['success' => false, 'message' => 'Вы не можете пригласить самого себя'];
        }

        // Проверяем, что такая связка реферер-реферал еще не существует
        $existingReferral = $this->db->fetch(
            "SELECT * FROM referrals WHERE referrer_id = ? AND referred_id = ?",
            [$referrerId, $referredId]
        );

        if ($existingReferral) {
            return ['success' => false, 'message' => 'Этот пользователь уже был приглашен вами'];
        }

        // Проверяем, что реферал еще не был приглашен кем-то другим
        $alreadyReferred = $this->db->fetch(
            "SELECT * FROM referrals WHERE referred_id = ?",
            [$referredId]
        );

        if ($alreadyReferred) {
            return ['success' => false, 'message' => 'Этот пользователь уже был приглашен другим пользователем'];
        }

        // Добавляем реферала
        $this->db->insert('referrals', [
            'referrer_id' => $referrerId,
            'referred_id' => $referredId,
            'status' => 'pending'
        ]);

        // Отправляем уведомление рефереру
        $notificationModel = new Notification();
        $notificationModel->createNotification(
            $referrerId,
            'Новый реферал',
            "Пользователь {$referred['username']} зарегистрировался по вашей реферальной ссылке. Когда он приобретет подписку, вы получите бонусные дни.",
            'info'
        );

        return ['success' => true];
    }

    /**
     * Обновление статуса реферала при покупке подписки
     *
     * @param int $referredId ID реферала (приглашенного)
     * @param string $planType Тип плана подписки
     * @return array Результат операции
     */
    public function processReferralPurchase($referredId, $planType) {
        // Проверяем, существует ли реферальная связь
        $referral = $this->db->fetch(
            "SELECT * FROM referrals WHERE referred_id = ? AND status = 'pending'",
            [$referredId]
        );

        if (!$referral) {
            return ['success' => false, 'message' => 'Реферальная связь не найдена или уже обработана'];
        }

        // Получаем информацию о плане подписки
        $subscriptionModel = new Subscription();
        $plan = $subscriptionModel->getPlan($planType);

        if (!$plan) {
            return ['success' => false, 'message' => 'План подписки не найден'];
        }

        // Рассчитываем бонус в зависимости от плана
        $bonusDays = $this->calculateBonusDays($plan);

        // Обновляем статус реферала
        $this->db->update('referrals', [
            'status' => 'active',
            'bonus_earned' => $bonusDays
        ], 'id = ?', [$referral['id']]);

        // Начисляем бонус рефереру
        $this->addBonusToUser($referral['referrer_id'], $bonusDays);

        // Отправляем уведомление рефереру
        $userModel = new User();
        $referrer = $userModel->getUserById($referral['referrer_id']);
        $referred = $userModel->getUserById($referredId);

        $notificationModel = new Notification();
        $notificationModel->createNotification(
            $referral['referrer_id'],
            'Начислен реферальный бонус',
            "Пользователь {$referred['username']} приобрел подписку \"{$plan['name']}\". Вам начислено {$bonusDays} бонусных дней! Вы можете использовать их при продлении своей подписки.",
            'success'
        );

        return [
            'success' => true,
            'bonus_days' => $bonusDays,
            'referrer_id' => $referral['referrer_id']
        ];
    }

    /**
     * Расчет количества бонусных дней в зависимости от плана
     *
     * @param array $plan Информация о плане подписки
     * @return int Количество бонусных дней
     */
    private function calculateBonusDays($plan) {
        // Базовый процент бонуса - 30% от длительности подписки
        $bonusPercent = 0.3;

        // Увеличиваем процент для более дорогих планов
        switch ($plan['name']) {
            case 'VIP':
                $bonusPercent = 0.5; // 50%
                break;
            case 'Премиум':
                $bonusPercent = 0.4; // 40%
                break;
            default:
                $bonusPercent = 0.3; // 30%
        }

        // Рассчитываем количество дней
        $bonusDays = round($plan['duration'] * $bonusPercent);

        // Минимальный бонус - 3 дня
        return max(3, $bonusDays);
    }

    /**
     * Начисление бонусных дней пользователю
     *
     * @param int $userId ID пользователя
     * @param int $bonusDays Количество бонусных дней
     * @return array Результат операции
     */
    public function addBonusToUser($userId, $bonusDays) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Обновляем баланс бонусов пользователя
        $currentBonus = $user['bonus_balance'] ?? 0;
        $newBonus = $currentBonus + $bonusDays;

        $this->db->update('users', [
            'bonus_balance' => $newBonus
        ], 'id = ?', [$userId]);

        return [
            'success' => true,
            'previous_balance' => $currentBonus,
            'new_balance' => $newBonus
        ];
    }

    /**
     * Использование бонусных дней при продлении подписки
     *
     * @param int $userId ID пользователя
     * @param int $bonusDays Количество используемых бонусных дней
     * @return array Результат операции
     */
    public function useBonusDays($userId, $bonusDays) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Проверяем, достаточно ли бонусов
        $currentBonus = $user['bonus_balance'] ?? 0;

        if ($currentBonus < $bonusDays) {
            return ['success' => false, 'message' => 'Недостаточно бонусных дней'];
        }

        // Списываем бонусы
        $newBonus = $currentBonus - $bonusDays;

        $this->db->update('users', [
            'bonus_balance' => $newBonus
        ], 'id = ?', [$userId]);

        return [
            'success' => true,
            'days_used' => $bonusDays,
            'previous_balance' => $currentBonus,
            'new_balance' => $newBonus
        ];
    }

    /**
     * Получение баланса бонусных дней пользователя
     *
     * @param int $userId ID пользователя
     * @return int Количество бонусных дней
     */
    public function getUserBonusBalance($userId) {
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        return $user ? ($user['bonus_balance'] ?? 0) : 0;
    }

    /**
     * Получение рефералов пользователя
     *
     * @param int $userId ID пользователя
     * @return array Список рефералов
     */
    public function getUserReferrals($userId) {
        return $this->db->fetchAll(
            "SELECT r.*, u.username, u.email, u.avatar
            FROM referrals r
            JOIN users u ON r.referred_id = u.id
            WHERE r.referrer_id = ?
            ORDER BY r.created_at DESC",
            [$userId]
        );
    }

    /**
     * Получение реферера (пригласившего) пользователя
     *
     * @param int $userId ID пользователя
     * @return array|false Информация о реферере или false, если реферер не найден
     */
    public function getUserReferrer($userId) {
        $referral = $this->db->fetch(
            "SELECT r.*, u.username, u.email
            FROM referrals r
            JOIN users u ON r.referrer_id = u.id
            WHERE r.referred_id = ?",
            [$userId]
        );

        return $referral ?: false;
    }

    /**
     * Получение статистики реферальной программы для пользователя
     *
     * @param int $userId ID пользователя
     * @return array Статистика
     */
    public function getUserReferralStats($userId) {
        // Общее количество рефералов
        $totalReferrals = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM referrals WHERE referrer_id = ?",
            [$userId]
        );

        // Количество активных рефералов (купивших подписку)
        $activeReferrals = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM referrals WHERE referrer_id = ? AND status = 'active'",
            [$userId]
        );

        // Общее количество заработанных бонусных дней
        $totalBonusDays = (int) $this->db->fetchColumn(
            "SELECT SUM(bonus_earned) FROM referrals WHERE referrer_id = ? AND status = 'active'",
            [$userId]
        );

        // Текущий баланс бонусных дней
        $currentBonusDays = $this->getUserBonusBalance($userId);

        // Использованные бонусные дни
        $usedBonusDays = $totalBonusDays - $currentBonusDays;

        return [
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'pending_referrals' => $totalReferrals - $activeReferrals,
            'total_bonus_days' => $totalBonusDays,
            'current_bonus_days' => $currentBonusDays,
            'used_bonus_days' => $usedBonusDays
        ];
    }

    /**
     * Получение подробной статистики по рефералам для пользователя
     *
     * @param int $userId ID пользователя
     * @return array Подробная статистика
     */
    public function getDetailedReferralStats($userId) {
        // Базовая статистика
        $basicStats = $this->getUserReferralStats($userId);

        // Ежемесячная статистика (последние 6 месяцев)
        $monthlyStats = [];
        $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));

        $monthlyData = $this->db->fetchAll(
            "SELECT
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as total_signups,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_signups,
                SUM(CASE WHEN status = 'active' THEN bonus_earned ELSE 0 END) as earned_bonus
            FROM referrals
            WHERE referrer_id = ? AND created_at > ?
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC",
            [$userId, $sixMonthsAgo]
        );

        foreach ($monthlyData as $data) {
            $monthName = date('F Y', strtotime("{$data['year']}-{$data['month']}-01"));
            $monthlyStats[$monthName] = [
                'total_signups' => $data['total_signups'],
                'active_signups' => $data['active_signups'],
                'earned_bonus' => $data['earned_bonus']
            ];
        }

        // Получаем статистику по планам подписок рефералов
        $planStats = $this->db->fetchAll(
            "SELECT
                s.plan_type,
                COUNT(*) as count,
                SUM(r.bonus_earned) as total_bonus
            FROM referrals r
            JOIN subscriptions s ON r.referred_id = s.user_id
            WHERE r.referrer_id = ? AND r.status = 'active'
            GROUP BY s.plan_type",
            [$userId]
        );

        $subscriptionPlans = [];
        foreach ($planStats as $plan) {
            $subscriptionPlans[$plan['plan_type']] = [
                'count' => $plan['count'],
                'total_bonus' => $plan['total_bonus']
            ];
        }

        // Конверсия: отношение активных рефералов к общему числу
        $conversionRate = ($basicStats['total_referrals'] > 0)
            ? round(($basicStats['active_referrals'] / $basicStats['total_referrals']) * 100, 2)
            : 0;

        return [
            'basic' => $basicStats,
            'monthly' => $monthlyStats,
            'subscription_plans' => $subscriptionPlans,
            'conversion_rate' => $conversionRate,
            'average_bonus_per_referral' => $basicStats['active_referrals'] > 0
                ? round($basicStats['total_bonus_days'] / $basicStats['active_referrals'], 1)
                : 0
        ];
    }

    /**
     * Создание дополнительного реферального кода для пользователя
     *
     * @param int $userId ID пользователя
     * @param string $description Описание кода (для какой цели создан)
     * @return array Результат операции и код
     */
    public function createAdditionalReferralCode($userId, $description = '') {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Генерируем уникальный код
        $code = $this->generateUniqueCode($userId);

        // Сохраняем код в базу данных с описанием
        $this->db->insert('referral_codes', [
            'user_id' => $userId,
            'code' => $code,
            'description' => $description
        ]);

        return ['success' => true, 'code' => $code];
    }

    /**
     * Получение всех реферальных кодов пользователя
     *
     * @param int $userId ID пользователя
     * @return array Список реферальных кодов
     */
    public function getUserReferralCodes($userId) {
        return $this->db->fetchAll(
            "SELECT id, code, description, created_at,
            (SELECT COUNT(*) FROM referrals r
             JOIN users u ON r.referred_id = u.id
             WHERE u.referral_code_id = rc.id) as referrals_count
            FROM referral_codes rc
            WHERE user_id = ?
            ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Обновление таблицы users - добавление привязки к конкретному реферальному коду
     */
    public function updateUserReferralCodeLink() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
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
                    referral_code_id INTEGER DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL
                );

                INSERT INTO users_new
                SELECT
                    id, username, email, password, avatar,
                    role, email_verified, verification_token,
                    bonus_balance, loyalty_days, loyalty_level,
                    NULL as referral_code_id,
                    created_at, updated_at
                FROM users;

                DROP TABLE IF EXISTS users;

                ALTER TABLE users_new RENAME TO users;

                COMMIT;

                PRAGMA foreign_keys = on;
            ");
        } else if ($driver === 'mysql') {
            // Проверяем наличие колонки referral_code_id в таблице users
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'referral_code_id'");
            if ($result->rowCount() === 0) {
                // Добавляем колонку, если ее нет
                $this->db->query("ALTER TABLE users ADD COLUMN referral_code_id INT DEFAULT NULL");
                $this->db->query("ALTER TABLE users ADD FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE SET NULL");
            }
        }
    }
}
