<?php
/**
 * API endpoint: /api/cheat/download
 * Метод: GET
 *
 * Параметры:
 * - version_id: ID версии чита для скачивания
 *
 * Возвращает:
 * - ссылку на скачивание файла чита
 * - время действия ссылки
 */

// Проверяем наличие параметра version_id
if (!isset($requestData['version_id'])) {
    $response = [
        'success' => false,
        'message' => 'Missing required parameter: version_id'
    ];
    $statusCode = 400;
    return;
}

$versionId = intval($requestData['version_id']);

// Создаем модель для работы с версиями чита
$cheatVersionModel = new CheatVersion();

// Получаем информацию о версии
$version = $cheatVersionModel->getVersionById($versionId);

if (!$version) {
    $response = [
        'success' => false,
        'message' => 'Version not found'
    ];
    $statusCode = 404;
    return;
}

// Проверяем, активна ли версия
if ($version['is_active'] != 1) {
    $response = [
        'success' => false,
        'message' => 'This version is not available for download'
    ];
    $statusCode = 403;
    return;
}

// Получаем модель подписки
$subscriptionModel = new Subscription();

// Проверяем, имеет ли пользователь доступ к этому плану
if (!$subscriptionModel->hasAccessToPlan($userId, $version['required_plan'])) {
    $response = [
        'success' => false,
        'message' => 'Your subscription plan does not allow access to this version'
    ];
    $statusCode = 403;
    return;
}

// Проверяем, может ли пользователь скачивать файлы (например, есть ли у него активная подписка)
if (!$subscriptionModel->hasActiveSubscription($userId)) {
    $response = [
        'success' => false,
        'message' => 'You need an active subscription to download cheat files'
    ];
    $statusCode = 403;
    return;
}

// Логируем скачивание
$cheatVersionModel->logDownload($userId, $versionId);

// Генерируем временную ссылку для скачивания
$config = require __DIR__ . '/../../config/config.php';
$uploadDir = $config['uploads']['cheat_files']['path'];
$filePath = $uploadDir . $version['file_path'];

// Проверяем, существует ли файл
if (!file_exists($filePath)) {
    $response = [
        'success' => false,
        'message' => 'File not found on server'
    ];
    $statusCode = 404;
    return;
}

// Генерируем токен для скачивания
$downloadToken = bin2hex(random_bytes(16));

// Сохраняем токен в сессию или базу данных с ограниченным временем действия
// В этом примере используем сессию, но в реальном приложении лучше использовать базу данных
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['download_tokens'][$downloadToken] = [
    'file_path' => $version['file_path'],
    'expires' => time() + 3600 // Срок действия ссылки - 1 час
];

// Формируем ссылку на скачивание
$downloadUrl = $config['site']['url'] . '/download.php?token=' . $downloadToken;

// Подготавливаем данные для ответа
$downloadData = [
    'version_id' => $version['id'],
    'version' => $version['version'],
    'file_name' => basename($version['file_path']),
    'download_url' => $downloadUrl,
    'expires_in' => 3600, // Время в секундах
    'expires_at' => date('Y-m-d H:i:s', time() + 3600)
];

// Формируем ответ
$response = [
    'success' => true,
    'message' => 'Download link generated successfully',
    'data' => $downloadData
];
$statusCode = 200;
