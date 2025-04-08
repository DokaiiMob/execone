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

// Получаем ID текущего пользователя
$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

// Инициализируем модель уведомлений
$notificationModel = new Notification();

// Получаем последние непрочитанные уведомления
$notifications = $notificationModel->getUserNotifications($userId, 10, 0);
$unreadCount = $notificationModel->getUnreadCount($userId);

// Фильтруем только непрочитанные уведомления
$newNotifications = array_filter($notifications, function($notification) {
    return $notification['is_read'] == 0;
});

// Отправляем ответ
echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'new_notifications' => array_values($newNotifications) // array_values для сброса ключей
]);
