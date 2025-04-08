<?php
/**
 * API endpoint: /api/user/info
 * Метод: GET
 *
 * Возвращает:
 * - информация о пользователе
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

// Получаем информацию о лояльности
$loyaltyModel = new Loyalty();
$loyaltyData = $loyaltyModel->getUserLoyaltyData($userId);

// Получаем информацию о рефералах
$referralModel = new Referral();
$referralStats = $referralModel->getUserReferralStats($userId);

// Получаем информацию о бонусах
$bonusBalance = $referralModel->getUserBonusBalance($userId);

// Получаем информацию о подписке
$subscriptionModel = new Subscription();
$subscription = $subscriptionModel->getUserSubscription($userId);

// Подготавливаем данные о пользователе
$userData = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role'],
    'avatar' => $user['avatar'] ? getUserAvatarUrl($user['avatar']) : null,
    'created_at' => $user['created_at'],
    'loyalty' => [
        'level' => $loyaltyData['loyalty_level'] ?? 'bronze',
        'level_name' => $loyaltyData['loyalty_level_name'] ?? 'Бронзовый',
        'days' => $loyaltyData['loyalty_days'] ?? 0,
        'discount' => $loyaltyData['discount'] ?? 0,
        'progress' => $loyaltyData['progress'] ?? 0,
        'next_level' => $loyaltyData['next_level'] ?? null,
        'days_for_next_level' => $loyaltyData['days_for_next_level'] ?? 0
    ],
    'referrals' => [
        'total' => $referralStats['total_referrals'] ?? 0,
        'active' => $referralStats['active_referrals'] ?? 0,
        'total_bonus_days' => $referralStats['total_bonus_days'] ?? 0,
        'current_bonus_days' => $bonusBalance
    ],
    'subscription' => null
];

// Добавляем информацию о подписке, если она есть
if ($subscription) {
    $plan = $subscriptionModel->getPlan($subscription['plan_type']);
    $userData['subscription'] = [
        'id' => $subscription['id'],
        'plan_type' => $subscription['plan_type'],
        'plan_name' => $plan['name'],
        'status' => $subscription['status'],
        'end_date' => $subscription['end_date'],
        'is_active' => true,
        'days_left' => max(0, floor((strtotime($subscription['end_date']) - time()) / 86400))
    ];
}

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'User information retrieved successfully',
    'data' => $userData
];
$statusCode = 200;
