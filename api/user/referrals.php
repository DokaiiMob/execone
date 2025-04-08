<?php
/**
 * API endpoint: /api/user/referrals
 * Метод: GET
 *
 * Возвращает:
 * - информация о рефералах пользователя
 * - реферальный код пользователя
 * - статистика по рефералам
 */

// Получаем информацию о пользователе
$userModel = new User();
$user = $userModel->getUserById($userId);

if (!$user) {
    $response = [
        'success' => false,
        'message' => 'User not found'
    ];
    $statusCode = 404;
    return;
}

// Получаем модель рефералов
$referralModel = new Referral();

// Получаем реферальный код пользователя
$referralCode = $referralModel->getUserReferralCode($userId);

// Получаем статистику по рефералам
$referralStats = $referralModel->getUserReferralStats($userId);

// Получаем список рефералов
$referrals = $referralModel->getUserReferrals($userId);

// Получаем бонусный баланс
$bonusBalance = $referralModel->getUserBonusBalance($userId);

// Форматируем данные о рефералах
$referralsList = [];
foreach ($referrals as $ref) {
    $referredUser = $userModel->getUserById($ref['referred_id']);
    if ($referredUser) {
        $referralsList[] = [
            'id' => $ref['id'],
            'username' => $referredUser['username'],
            'status' => $ref['status'],
            'bonus_earned' => $ref['bonus_earned'],
            'created_at' => $ref['created_at']
        ];
    }
}

// Подготавливаем данные для ответа
$data = [
    'code' => $referralCode ? $referralCode['code'] : null,
    'stats' => [
        'total_referrals' => $referralStats['total_referrals'] ?? 0,
        'active_referrals' => $referralStats['active_referrals'] ?? 0,
        'total_bonus_days' => $referralStats['total_bonus_days'] ?? 0,
        'current_bonus_days' => $bonusBalance
    ],
    'referrals' => $referralsList,
    'referral_url' => $referralCode ? $this->config['site']['url'] . '/register.php?ref=' . $referralCode['code'] : null,
    'rewards' => [
        'registration' => 3, // Бонусных дней за регистрацию
        'subscription_percent' => 10 // Процент от стоимости подписки
    ]
];

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'User referrals information retrieved successfully',
    'data' => $data
];
$statusCode = 200;
