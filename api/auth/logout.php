<?php
/**
 * API endpoint: /api/auth/logout
 * Метод: POST
 *
 * Параметры:
 * - token: токен для деактивации (опционально, если не указан, используется текущий токен)
 *
 * Возвращает:
 * - статус операции
 */

// Получаем токен для деактивации
$tokenToDeactivate = $requestData['token'] ?? $apiToken;

if (empty($tokenToDeactivate)) {
    $response = [
        'success' => false,
        'message' => 'No token provided'
    ];
    $statusCode = 400;
    return;
}

// Деактивируем токен
$apiTokenModel = new ApiToken();
$result = $apiTokenModel->deactivateToken($tokenToDeactivate);

if ($result) {
    $response = [
        'success' => true,
        'message' => 'Token successfully deactivated'
    ];
    $statusCode = 200;
} else {
    $response = [
        'success' => false,
        'message' => 'Failed to deactivate token or token not found'
    ];
    $statusCode = 404;
}
