<?php
/**
 * API endpoint: /api/subscription/purchase
 * Метод: POST
 *
 * Ожидает параметры:
 * - plan_type: тип плана подписки (basic, premium, vip, custom)
 * - payment_method: метод оплаты (card, qiwi, webmoney, crypto)
 * - duration: длительность в днях (для custom плана)
 * - promocode: промокод (опционально)
 * - use_bonus_balance: использовать ли бонусный баланс (1 или 0, опционально)
 *
 * Возвращает:
 * - информация о созданном заказе
 * - ссылка на оплату
 */

// Проверяем наличие обязательных параметров
if (!isset($requestData['plan_type'])) {
    $response = [
        'success' => false,
        'message' => 'Missing required parameter: plan_type'
    ];
    $statusCode = 400;
    return;
}

if (!isset($requestData['payment_method'])) {
    $response = [
        'success' => false,
        'message' => 'Missing required parameter: payment_method'
    ];
    $statusCode = 400;
    return;
}

// Получаем тип плана и метод оплаты
$planType = $requestData['plan_type'];
$paymentMethod = $requestData['payment_method'];
$promocode = $requestData['promocode'] ?? null;
$useBonusBalance = isset($requestData['use_bonus_balance']) && $requestData['use_bonus_balance'] == 1;

// Получаем объект подписки
$subscriptionModel = new Subscription();

// Проверяем, существует ли такой план подписки
if ($planType !== 'custom' && !$subscriptionModel->planExists($planType)) {
    $response = [
        'success' => false,
        'message' => 'Invalid subscription plan'
    ];
    $statusCode = 400;
    return;
}

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

// Проверяем доступные методы оплаты
$allowedPaymentMethods = ['card', 'qiwi', 'webmoney', 'crypto'];
if (!in_array($paymentMethod, $allowedPaymentMethods)) {
    $response = [
        'success' => false,
        'message' => 'Invalid payment method'
    ];
    $statusCode = 400;
    return;
}

// Получаем стоимость плана подписки
$price = 0;
$duration = 0;

if ($planType === 'custom') {
    // Для custom плана проверяем наличие параметра длительности
    if (!isset($requestData['duration']) || intval($requestData['duration']) <= 0) {
        $response = [
            'success' => false,
            'message' => 'Missing or invalid duration for custom plan'
        ];
        $statusCode = 400;
        return;
    }

    $duration = intval($requestData['duration']);

    // Расчет стоимости custom плана (например, 20 рублей в день)
    $dailyPrice = 20;
    $price = $duration * $dailyPrice;
} else {
    // Для стандартных планов берем информацию из конфигурации
    $plan = $subscriptionModel->getPlan($planType);
    $price = $plan['price'];
    $duration = $plan['duration'];
}

// Применяем скидку программы лояльности
$loyaltyModel = new Loyalty();
$loyaltyData = $loyaltyModel->getUserLoyaltyData($userId);
if (isset($loyaltyData['discount']) && $loyaltyData['discount'] > 0) {
    $discount = $loyaltyData['discount'];
    $price = $price * (1 - $discount / 100);
}

// Применяем промокод, если указан
if ($promocode) {
    // Здесь можно добавить логику проверки промокода и расчета скидки
    // Например:
    // $promocodeDiscount = проверяем_промокод($promocode);
    // if ($promocodeDiscount) {
    //    $price = $price * (1 - $promocodeDiscount / 100);
    // }
}

// Учитываем бонусный баланс, если пользователь хочет его использовать
$bonusBalance = 0;
if ($useBonusBalance) {
    $referralModel = new Referral();
    $bonusBalance = $referralModel->getUserBonusBalance($userId);
    if ($bonusBalance > 0) {
        // Конвертируем бонусные дни в скидку (например, 1 день = 20 рублей)
        $bonusDiscount = min($bonusBalance * 20, $price);
        $price = max(0, $price - $bonusDiscount);
    }
}

// Округляем цену до двух знаков после запятой
$price = round($price, 2);

// Создаем заказ и получаем ссылку на оплату
// (здесь должна быть реализация взаимодействия с платежной системой)
$orderId = uniqid('order_');
$paymentUrl = $this->config['site']['url'] . '/payment.php?order=' . $orderId;

// В реальной системе здесь должно быть создание заказа в базе данных
// и передача информации в платежную систему

// Подготавливаем данные для ответа
$orderData = [
    'order_id' => $orderId,
    'plan_type' => $planType,
    'duration' => $duration,
    'price' => $price,
    'payment_method' => $paymentMethod,
    'payment_url' => $paymentUrl,
    'status' => 'pending'
];

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Order created successfully',
    'data' => $orderData
];
$statusCode = 200;
