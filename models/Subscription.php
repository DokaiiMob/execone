<?php

class Subscription {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Получение информации о всех доступных планах подписки
     */
    public function getPlans() {
        return $this->config['subscription']['plans'];
    }

    /**
     * Получение информации о конкретном плане подписки
     */
    public function getPlan($planType) {
        $plans = $this->getPlans();

        if (!isset($plans[$planType])) {
            return null;
        }

        return $plans[$planType];
    }

    /**
     * Проверка, существует ли такой план подписки
     */
    public function planExists($planType) {
        $plans = $this->getPlans();
        return isset($plans[$planType]);
    }

    /**
     * Получение текущей подписки пользователя
     */
    public function getUserSubscription($userId) {
        return $this->db->fetch(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW() ORDER BY end_date DESC LIMIT 1",
            [$userId]
        );
    }

    /**
     * Получение истории подписок пользователя
     */
    public function getUserSubscriptionHistory($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY start_date DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Проверка, имеет ли пользователь активную подписку
     */
    public function hasActiveSubscription($userId) {
        $subscription = $this->getUserSubscription($userId);
        return $subscription !== null;
    }

    /**
     * Проверка, имеет ли пользователь доступ к конкретному плану подписки
     */
    public function hasAccessToPlan($userId, $requiredPlan) {
        $subscription = $this->getUserSubscription($userId);

        if (!$subscription) {
            return false;
        }

        $plans = $this->getPlans();
        $currentPlan = $subscription['plan_type'];

        // Если у пользователя VIP, он имеет доступ ко всем планам
        if ($currentPlan === 'vip') {
            return true;
        }

        // Если у пользователя премиум, он имеет доступ к базовому и премиум
        if ($currentPlan === 'premium' && ($requiredPlan === 'basic' || $requiredPlan === 'premium')) {
            return true;
        }

        // Если у пользователя базовый, он имеет доступ только к базовому
        if ($currentPlan === 'basic' && $requiredPlan === 'basic') {
            return true;
        }

        return false;
    }

    /**
     * Создание новой подписки для пользователя
     */
    public function createSubscription($userId, $planType, $paymentId = null) {
        // Проверяем существование плана
        if (!$this->planExists($planType)) {
            return ['success' => false, 'message' => 'Указанный план подписки не существует'];
        }

        $plan = $this->getPlan($planType);

        // Рассчитываем дату окончания подписки
        $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));

        // Создаем новую подписку
        $subscriptionId = $this->db->insert('subscriptions', [
            'user_id' => $userId,
            'plan_type' => $planType,
            'status' => 'active',
            'end_date' => $endDate,
            'payment_id' => $paymentId
        ]);

        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'plan_type' => $planType,
            'end_date' => $endDate
        ];
    }

    /**
     * Продление подписки пользователя
     *
     * @param int $userId ID пользователя
     * @param string $planType Тип плана подписки
     * @param int $paymentId ID платежа
     * @param bool $useBonusDays Использовать бонусные дни
     * @return array Результат операции
     */
    public function extendSubscription($userId, $planType, $paymentId = null, $useBonusDays = false) {
        // Проверяем существование плана
        if (!$this->planExists($planType)) {
            return ['success' => false, 'message' => 'Указанный план подписки не существует'];
        }

        $plan = $this->getPlan($planType);

        // Проверяем, есть ли у пользователя активная подписка
        $currentSubscription = $this->getUserSubscription($userId);

        // Проверяем бонусные дни, если опция выбрана
        $bonusDays = 0;
        if ($useBonusDays) {
            $referralModel = new Referral();
            $bonusBalance = $referralModel->getUserBonusBalance($userId);

            if ($bonusBalance > 0) {
                // Не используем больше дней, чем есть на балансе
                $bonusDays = min($bonusBalance, $plan['duration']);

                // Используем бонусные дни
                if ($bonusDays > 0) {
                    $referralModel->useBonusDays($userId, $bonusDays);
                }
            }
        }

        if ($currentSubscription) {
            // Если текущая подписка отличается от новой, завершаем старую
            if ($currentSubscription['plan_type'] !== $planType) {
                $this->db->update('subscriptions', [
                    'status' => 'expired'
                ], 'id = ?', [$currentSubscription['id']]);

                // Создаем новую подписку с учетом бонусных дней
                $totalDuration = $plan['duration'] + $bonusDays;
                $endDate = date('Y-m-d H:i:s', strtotime("+{$totalDuration} days"));

                $subscriptionId = $this->db->insert('subscriptions', [
                    'user_id' => $userId,
                    'plan_type' => $planType,
                    'status' => 'active',
                    'end_date' => $endDate,
                    'payment_id' => $paymentId,
                    'bonus_days_used' => $bonusDays
                ]);

                return [
                    'success' => true,
                    'subscription_id' => $subscriptionId,
                    'plan_type' => $planType,
                    'end_date' => $endDate,
                    'bonus_days_used' => $bonusDays
                ];
            }

            // Иначе продлеваем текущую подписку
            $totalDuration = $plan['duration'] + $bonusDays;

            // Рассчитываем новую дату окончания подписки
            $endDate = max(
                date('Y-m-d H:i:s', strtotime("+{$totalDuration} days")),
                date('Y-m-d H:i:s', strtotime($currentSubscription['end_date'] . " +{$totalDuration} days"))
            );

            $this->db->update('subscriptions', [
                'end_date' => $endDate,
                'bonus_days_used' => ($currentSubscription['bonus_days_used'] ?? 0) + $bonusDays
            ], 'id = ?', [$currentSubscription['id']]);

            return [
                'success' => true,
                'subscription_id' => $currentSubscription['id'],
                'plan_type' => $planType,
                'end_date' => $endDate,
                'bonus_days_used' => $bonusDays
            ];
        } else {
            // Если у пользователя нет активной подписки, создаем новую с учетом бонусных дней
            $totalDuration = $plan['duration'] + $bonusDays;
            $endDate = date('Y-m-d H:i:s', strtotime("+{$totalDuration} days"));

            $subscriptionId = $this->db->insert('subscriptions', [
                'user_id' => $userId,
                'plan_type' => $planType,
                'status' => 'active',
                'end_date' => $endDate,
                'payment_id' => $paymentId,
                'bonus_days_used' => $bonusDays
            ]);

            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'plan_type' => $planType,
                'end_date' => $endDate,
                'bonus_days_used' => $bonusDays
            ];
        }
    }

    /**
     * Отмена подписки пользователя
     */
    public function cancelSubscription($userId) {
        // Проверяем, есть ли у пользователя активная подписка
        $subscription = $this->getUserSubscription($userId);

        if (!$subscription) {
            return ['success' => false, 'message' => 'У пользователя нет активной подписки'];
        }

        // Обновляем статус подписки
        $this->db->update('subscriptions', [
            'status' => 'cancelled'
        ], 'id = ?', [$subscription['id']]);

        return ['success' => true];
    }

    /**
     * Получение списка всех активных подписок (для админа)
     */
    public function getAllActiveSubscriptions($limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT s.*, u.username, u.email FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'active' AND s.end_date > NOW()
            ORDER BY s.end_date ASC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Поиск подписок по имени пользователя или email (для админа)
     */
    public function searchSubscriptions($query, $limit = 50, $offset = 0) {
        $searchQuery = "%{$query}%";

        return $this->db->fetchAll(
            "SELECT s.*, u.username, u.email FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE (u.username LIKE ? OR u.email LIKE ?)
            ORDER BY s.end_date DESC LIMIT ? OFFSET ?",
            [$searchQuery, $searchQuery, $limit, $offset]
        );
    }

    /**
     * Создание записи о платеже
     */
    public function createPayment($userId, $amount, $paymentMethod, $status, $transactionId = null) {
        $paymentId = $this->db->insert('payments', [
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'transaction_id' => $transactionId
        ]);

        return [
            'success' => true,
            'payment_id' => $paymentId
        ];
    }

    /**
     * Получение истории платежей пользователя
     */
    public function getUserPayments($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Получение всех платежей (для админа)
     */
    public function getAllPayments($limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT p.*, u.username, u.email FROM payments p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.payment_date DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Получение подписки по ID
     */
    public function getSubscriptionById($subscriptionId) {
        return $this->db->fetch(
            "SELECT * FROM subscriptions WHERE id = ?",
            [$subscriptionId]
        );
    }

    /**
     * Отмена подписки по ID (для админ-панели)
     */
    public function cancelSubscriptionById($subscriptionId) {
        // Проверяем существование подписки
        $subscription = $this->getSubscriptionById($subscriptionId);

        if (!$subscription) {
            return ['success' => false, 'message' => 'Подписка не найдена'];
        }

        // Обновляем статус подписки
        $this->db->update('subscriptions', [
            'status' => 'cancelled'
        ], 'id = ?', [$subscriptionId]);

        return ['success' => true];
    }

    /**
     * Расчет скидки для пользователя с учетом программы лояльности и прочих скидок
     *
     * @param int $userId ID пользователя
     * @param float $originalPrice Исходная цена подписки
     * @param int $duration Длительность подписки в днях
     * @return array Информация о скидке и финальной цене
     */
    public function calculateDiscount($userId, $originalPrice, $duration = 30) {
        $discounts = [];
        $totalDiscountPercent = 0;

        // Получаем данные о лояльности пользователя
        $loyaltyModel = new Loyalty();
        $loyaltyData = $loyaltyModel->getUserLoyaltyData($userId);

        if ($loyaltyData['success']) {
            $loyaltyDiscount = $loyaltyData['discount'];
            if ($loyaltyDiscount > 0) {
                $discounts['loyalty'] = [
                    'name' => 'Программа лояльности (' . $loyaltyData['loyalty_level_name'] . ')',
                    'percent' => $loyaltyDiscount
                ];
                $totalDiscountPercent += $loyaltyDiscount;
            }
        }

        // Скидка за длительность подписки
        $durationDiscount = 0;
        if ($duration >= 365) { // Годовая подписка
            $durationDiscount = 20; // 20% скидка
        } elseif ($duration >= 180) { // Полгода
            $durationDiscount = 15; // 15% скидка
        } elseif ($duration >= 90) { // 3 месяца
            $durationDiscount = 10; // 10% скидка
        }

        if ($durationDiscount > 0) {
            $discounts['duration'] = [
                'name' => 'Длительная подписка (' . round($duration / 30) . ' мес.)',
                'percent' => $durationDiscount
            ];
            $totalDiscountPercent += $durationDiscount;
        }

        // Ограничиваем максимальную скидку 40%
        $totalDiscountPercent = min($totalDiscountPercent, 40);

        // Рассчитываем итоговую цену
        $discountAmount = ($originalPrice * $totalDiscountPercent) / 100;
        $finalPrice = $originalPrice - $discountAmount;

        return [
            'original_price' => $originalPrice,
            'discount_percent' => $totalDiscountPercent,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'discounts' => $discounts
        ];
    }

    /**
     * Создание индивидуального плана подписки
     *
     * @param string $basePlanType Базовый тип плана (basic, premium, vip)
     * @param int $duration Длительность подписки в днях
     * @param array $features Выбранные функции (если null, используются функции базового плана)
     * @return array План подписки
     */
    public function createCustomPlan($basePlanType, $duration, $features = null) {
        // Проверяем существование базового плана
        if (!$this->planExists($basePlanType)) {
            return ['success' => false, 'message' => 'Указанный базовый план не существует'];
        }

        $basePlan = $this->getPlan($basePlanType);
        $allPlans = $this->getPlans();

        // Формируем базовую стоимость за один день
        $dailyPrice = $basePlan['price'] / $basePlan['duration'];

        // Если есть кастомные функции, рассчитываем стоимость
        if ($features !== null) {
            // Создаем список всех доступных функций с ценами
            $allFeatures = [];
            foreach ($allPlans as $planType => $plan) {
                foreach ($plan['features'] as $feature) {
                    if (!isset($allFeatures[$feature])) {
                        // Назначаем каждой функции стоимость в зависимости от уровня плана
                        switch ($planType) {
                            case 'vip':
                                $featurePrice = 0.3; // 30% от стоимости базового плана
                                break;
                            case 'premium':
                                $featurePrice = 0.15; // 15% от стоимости базового плана
                                break;
                            default:
                                $featurePrice = 0.1; // 10% от стоимости базового плана
                        }
                        $allFeatures[$feature] = $featurePrice;
                    }
                }
            }

            // Рассчитываем стоимость выбранных функций
            $featuresPriceMultiplier = 0;
            $selectedFeatures = [];
            foreach ($features as $feature) {
                if (isset($allFeatures[$feature])) {
                    $featuresPriceMultiplier += $allFeatures[$feature];
                    $selectedFeatures[] = $feature;
                }
            }

            // Ограничиваем множитель, чтобы не превышать стоимость VIP
            $featuresPriceMultiplier = min($featuresPriceMultiplier, 1.5);

            // Рассчитываем итоговую цену
            $totalPrice = round($dailyPrice * $duration * $featuresPriceMultiplier);

            return [
                'success' => true,
                'name' => 'Индивидуальный',
                'base_plan' => $basePlanType,
                'price' => $totalPrice,
                'duration' => $duration,
                'features' => $selectedFeatures,
                'custom' => true,
                'daily_price' => round($dailyPrice * $featuresPriceMultiplier, 2)
            ];
        } else {
            // Если нет кастомных функций, просто меняем длительность
            $totalPrice = round($dailyPrice * $duration);

            return [
                'success' => true,
                'name' => $basePlan['name'] . ' (' . $duration . ' дней)',
                'base_plan' => $basePlanType,
                'price' => $totalPrice,
                'duration' => $duration,
                'features' => $basePlan['features'],
                'custom' => true,
                'daily_price' => round($dailyPrice, 2)
            ];
        }
    }

    /**
     * Сохранение информации о кастомном плане
     *
     * @param int $userId ID пользователя
     * @param array $customPlan Данные кастомного плана
     * @return array Результат операции
     */
    public function saveCustomPlan($userId, $customPlan) {
        if (!isset($customPlan['success']) || !$customPlan['success']) {
            return ['success' => false, 'message' => 'Некорректные данные плана'];
        }

        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Генерируем уникальный ID для кастомного плана
        $planId = uniqid('custom_');

        // Сохраняем план в таблицу кастомных планов
        $this->db->insert('custom_plans', [
            'id' => $planId,
            'user_id' => $userId,
            'base_plan' => $customPlan['base_plan'],
            'name' => $customPlan['name'],
            'price' => $customPlan['price'],
            'duration' => $customPlan['duration'],
            'features' => json_encode($customPlan['features']),
            'daily_price' => $customPlan['daily_price']
        ]);

        return [
            'success' => true,
            'plan_id' => $planId,
            'plan' => $customPlan
        ];
    }

    /**
     * Получение кастомного плана по ID
     *
     * @param string $planId ID плана
     * @return array|false Данные плана или false, если план не найден
     */
    public function getCustomPlan($planId) {
        $plan = $this->db->fetch(
            "SELECT * FROM custom_plans WHERE id = ?",
            [$planId]
        );

        if (!$plan) {
            return false;
        }

        // Преобразуем JSON функций обратно в массив
        $plan['features'] = json_decode($plan['features'], true);

        return $plan;
    }

    /**
     * Получение всех кастомных планов пользователя
     *
     * @param int $userId ID пользователя
     * @return array Список кастомных планов
     */
    public function getUserCustomPlans($userId) {
        $plans = $this->db->fetchAll(
            "SELECT * FROM custom_plans WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );

        // Преобразуем JSON функций обратно в массив для каждого плана
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true);
        }

        return $plans;
    }

    /**
     * Создание таблицы для кастомных планов, если не существует
     */
    public function createCustomPlansTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS custom_plans (
                    id VARCHAR(20) PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    base_plan VARCHAR(20) NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    price REAL NOT NULL,
                    duration INTEGER NOT NULL,
                    features TEXT NOT NULL,
                    daily_price REAL NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } else if ($driver === 'mysql') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS custom_plans (
                    id VARCHAR(20) PRIMARY KEY,
                    user_id INT NOT NULL,
                    base_plan VARCHAR(20) NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    duration INT NOT NULL,
                    features TEXT NOT NULL,
                    daily_price DECIMAL(10,2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}
