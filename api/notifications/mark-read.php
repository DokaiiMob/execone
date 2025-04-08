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

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = isset($data['id']) ? (int)$data['id'] : 0;

if (!$notificationId) {
    echo json_encode([
        'success' => false,
        'message' => 'Не указан ID уведомления'
    ]);
    exit;
}

// Инициализируем модель уведомлений
$notificationModel = new Notification();

// Отмечаем уведомление как прочитанное
$result = $notificationModel->markAsRead($notificationId, $userId);

// Получаем количество непрочитанных уведомлений
$unreadCount = $notificationModel->getUnreadCount($userId);

// Отправляем ответ
echo json_encode([
    'success' => $result['success'],
    'message' => isset($result['message']) ? $result['message'] : 'Уведомление отмечено как прочитанное',
    'unread_count' => $unreadCount
]);
