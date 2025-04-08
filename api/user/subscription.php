<?php
/**
 * API endpoint: /api/user/subscription
 * Метод: GET
 *
 * Возвращает:
 * - информация о текущей подписке пользователя
 */

// Получаем информацию о подписке
$subscriptionModel = new Subscription();
$subscription = $subscriptionModel->getUserSubscription($userId);

if (!$subscription) {
    $response = [
        'success' => true,
        'message' => 'User has no active subscription',
        'data' => [
            'has_subscription' => false
        ]
    ];
    $statusCode = 200;
    return;
}

// Получаем информацию о плане
$plan = $subscriptionModel->getPlan($subscription['plan_type']);

// Получаем историю платежей
$paymentHistory = $subscriptionModel->getUserPayments($userId, 5); // Последние 5 платежей

// Рассчитываем оставшиеся дни
$endDate = strtotime($subscription['end_date']);
$now = time();
$daysLeft = max(0, ceil(($endDate - $now) / 86400));

// Подготавливаем данные о подписке
$subscriptionData = [
    'has_subscription' => true,
    'id' => $subscription['id'],
    'plan_type' => $subscription['plan_type'],
    'plan_name' => $plan['name'],
    'status' => $subscription['status'],
    'start_date' => $subscription['created_at'],
    'end_date' => $subscription['end_date'],
    'days_left' => $daysLeft,
    'is_active' => ($subscription['status'] === 'active' && $endDate > $now),
    'features' => $plan['features'],
    'payment_history' => []
];

// Добавляем историю платежей
foreach ($paymentHistory as $payment) {
    $subscriptionData['payment_history'][] = [
        'id' => $payment['id'],
        'amount' => $payment['amount'],
        'payment_method' => $payment['payment_method'],
        'status' => $payment['status'],
        'payment_date' => $payment['payment_date']
    ];
}

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Subscription information retrieved successfully',
    'data' => $subscriptionData
];
$statusCode = 200;
