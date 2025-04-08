<?php
/**
 * API endpoint: /api/auth/login
 * Метод: POST
 *
 * Параметры:
 * - username_or_email: логин или email пользователя
 * - password: пароль пользователя
 * - description: описание токена (опционально)
 * - expires: срок действия токена в формате ("+30 days", "+1 year", etc.) (опционально)
 *
 * Возвращает:
 * - токен для авторизации
 */

// Проверяем наличие необходимых параметров
if (!isset($requestData['username_or_email']) || !isset($requestData['password'])) {
    $response = [
        'success' => false,
        'message' => 'Missing required parameters: username_or_email and password'
    ];
    $statusCode = 400;
    return;
}

// Получаем параметры
$usernameOrEmail = $requestData['username_or_email'];
$password = $requestData['password'];
$description = $requestData['description'] ?? 'API Token generated via API';
$expires = $requestData['expires'] ?? '+30 days'; // По умолчанию токен действует 30 дней

// Авторизуем пользователя
$userModel = new User();
$loginResult = $userModel->login($usernameOrEmail, $password);

if (!$loginResult['success']) {
    $response = [
        'success' => false,
        'message' => $loginResult['message']
    ];
    $statusCode = 401;
    return;
}

// Генерируем API токен
$apiTokenModel = new ApiToken();
$tokenResult = $apiTokenModel->generateToken(
    $loginResult['user']['id'],
    $description,
    '*', // Разрешаем все действия
    $expires
);

if (!$tokenResult['success']) {
    $response = [
        'success' => false,
        'message' => $tokenResult['message']
    ];
    $statusCode = 500;
    return;
}

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Authentication successful',
    'data' => [
        'token' => $tokenResult['token'],
        'expires_at' => $tokenResult['expires_at'],
        'user' => [
            'id' => $loginResult['user']['id'],
            'username' => $loginResult['user']['username'],
            'email' => $loginResult['user']['email'],
            'role' => $loginResult['user']['role']
        ]
    ]
];
$statusCode = 200;
