<?php
/**
 * API endpoint: /api/cheat/versions
 * Метод: GET
 *
 * Параметры:
 * - all: если равен 1, возвращает все версии (для админа)
 *
 * Возвращает:
 * - список доступных версий чита
 * - для каждой версии: id, version, description, required_plan, created_at
 */

// Создаем модель для работы с версиями чита
$cheatVersionModel = new CheatVersion();

// Определяем, нужно ли возвращать все версии или только активные
$showAll = isset($requestData['all']) && $requestData['all'] == 1;

// Проверяем, имеет ли пользователь доступ к полному списку версий
$userModel = new User();
$user = null;

if ($isAuthenticated) {
    $user = $userModel->getUserById($userId);
}

// Только администраторы могут видеть все версии
$isAdmin = $user && $user['role'] === 'admin';
if ($showAll && !$isAdmin) {
    $response = [
        'success' => false,
        'message' => 'Unauthorized to view all versions'
    ];
    $statusCode = 403;
    return;
}

// Получаем список версий
$versions = [];
if ($showAll && $isAdmin) {
    // Для админа возвращаем все версии
    $versions = $cheatVersionModel->getAllVersions(false);
} elseif ($isAuthenticated) {
    // Для авторизованного пользователя возвращаем доступные версии
    $versions = $cheatVersionModel->getAvailableVersionsForUser($userId);
} else {
    // Для неавторизованного пользователя возвращаем только базовую информацию
    $versions = $cheatVersionModel->getAllVersions(true);
}

// Готовим данные для ответа
$versionsList = [];
foreach ($versions as $version) {
    $versionData = [
        'id' => $version['id'],
        'version' => $version['version'],
        'description' => $version['description'],
        'required_plan' => $version['required_plan'],
        'created_at' => $version['created_at'],
        'is_active' => $version['is_active'] == 1
    ];

    // Добавляем название требуемого плана
    switch ($version['required_plan']) {
        case 'basic':
            $versionData['required_plan_name'] = 'Базовая';
            break;
        case 'premium':
            $versionData['required_plan_name'] = 'Премиум';
            break;
        case 'vip':
            $versionData['required_plan_name'] = 'VIP';
            break;
        default:
            $versionData['required_plan_name'] = 'Неизвестно';
    }

    // Проверяем, доступен ли чит пользователю
    if ($isAuthenticated) {
        $subscriptionModel = new Subscription();
        $versionData['is_available'] = $subscriptionModel->hasAccessToPlan($userId, $version['required_plan']);
    } else {
        $versionData['is_available'] = false;
    }

    $versionsList[] = $versionData;
}

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Cheat versions retrieved successfully',
    'data' => [
        'versions' => $versionsList,
        'total' => count($versionsList)
    ]
];
$statusCode = 200;
