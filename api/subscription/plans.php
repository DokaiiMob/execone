<?php
/**
 * API endpoint: /api/subscription/plans
 * Метод: GET
 *
 * Возвращает:
 * - список доступных планов подписки
 */

// Получаем информацию о доступных планах
$subscriptionModel = new Subscription();
$plans = $subscriptionModel->getPlans();

// Подготавливаем отформатированные данные о планах
$formattedPlans = [];
foreach ($plans as $type => $plan) {
    $formattedPlans[] = [
        'type' => $type,
        'name' => $plan['name'],
        'price' => $plan['price'],
        'duration' => $plan['duration'],
        'features' => $plan['features'],
        'daily_price' => round($plan['price'] / $plan['duration'], 2)
    ];
}

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Subscription plans retrieved successfully',
    'data' => [
        'plans' => $formattedPlans,
        'custom_plans_available' => true
    ]
];
$statusCode = 200;
