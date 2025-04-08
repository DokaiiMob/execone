<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/init.php';

// Проверяем авторизацию
if (!$user->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Требуется авторизация'
    ]);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный метод запроса'
    ]);
    exit;
}

// Получаем ID текущего пользователя
$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

// Инициализируем модель уведомлений
$notificationModel = new Notification();

// Отмечаем все уведомления как прочитанные
$result = $notificationModel->markAllAsRead($userId);

// Отправляем ответ
echo json_encode([
    'success' => $result['success'],
    'message' => 'Все уведомления отмечены как прочитанные',
    'unread_count' => 0
]);
