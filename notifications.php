<?php
$pageTitle = 'Уведомления - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$notificationModel = new Notification();

$currentUser = $userModel->getCurrentUser();
$action = $_GET['action'] ?? '';
$notificationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Обработка действий с уведомлениями
if ($action && $notificationId > 0) {
    switch ($action) {
        case 'read':
            $result = $notificationModel->markAsRead($notificationId, $currentUser['id']);
            if ($result['success']) {
                displaySuccess('Уведомление отмечено как прочитанное');
            } else {
                displayError($result['message']);
            }
            break;

        case 'delete':
            $result = $notificationModel->deleteNotification($notificationId, $currentUser['id']);
            if ($result['success']) {
                displaySuccess('Уведомление удалено');
            } else {
                displayError($result['message']);
            }
            break;
    }

    // Перенаправляем на страницу уведомлений без параметров
    header('Location: /notifications.php');
    exit;
}

// Обработка массовых действий с уведомлениями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $result = $notificationModel->markAllAsRead($currentUser['id']);
        if ($result['success']) {
            displaySuccess('Все уведомления отмечены как прочитанные');
        }
    } elseif (isset($_POST['delete_all'])) {
        $result = $notificationModel->deleteAllNotifications($currentUser['id']);
        if ($result['success']) {
            displaySuccess('Все уведомления удалены');
        }
    }

    // Перенаправляем на страницу уведомлений без параметров
    header('Location: /notifications.php');
    exit;
}

// Получаем уведомления пользователя
$notifications = $notificationModel->getUserNotifications($currentUser['id'], 50, 0);
$unreadCount = $notificationModel->getUnreadCount($currentUser['id']);

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Уведомления <span class="badge bg-primary"><?= $unreadCount ?> новых</span></h1>
                <div>
                    <?php if (!empty($notifications)): ?>
                        <form action="" method="POST" class="d-inline">
                            <button type="submit" name="mark_all_read" class="btn btn-outline-primary me-2">
                                <i class="fas fa-check-double me-1"></i> Отметить все как прочитанные
                            </button>
                            <button type="submit" name="delete_all" class="btn btn-outline-danger" onclick="return confirm('Вы уверены, что хотите удалить все уведомления?');">
                                <i class="fas fa-trash me-1"></i> Удалить все
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> У вас пока нет уведомлений.
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item list-group-item-action <?= !$notification['is_read'] ? 'notification-unread' : '' ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $iconClass = 'info-circle';
                                            $bgClass = 'primary';

                                            switch ($notification['type']) {
                                                case 'success':
                                                    $iconClass = 'check-circle';
                                                    $bgClass = 'success';
                                                    break;
                                                case 'warning':
                                                    $iconClass = 'exclamation-triangle';
                                                    $bgClass = 'warning';
                                                    break;
                                                case 'danger':
                                                    $iconClass = 'exclamation-circle';
                                                    $bgClass = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <div class="notification-icon bg-<?= $bgClass ?> text-white me-3">
                                                <i class="fas fa-<?= $iconClass ?>"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h5>
                                                <p class="mb-2"><?= nl2br(htmlspecialchars($notification['message'])) ?></p>
                                                <div class="text-muted small">
                                                    <i class="far fa-clock me-1"></i> <?= formatDate($notification['created_at']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-3 notification-actions">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?action=read&id=<?= $notification['id'] ?>" class="btn btn-sm btn-outline-primary me-2" title="Отметить как прочитанное">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $notification['id'] ?>" class="btn btn-sm btn-outline-danger" title="Удалить" onclick="return confirm('Вы уверены, что хотите удалить это уведомление?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notification-unread {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-actions {
    opacity: 0.2;
    transition: opacity 0.3s;
}

.list-group-item:hover .notification-actions {
    opacity: 1;
}

@media (max-width: 768px) {
    .notification-actions {
        opacity: 1;
    }
}
</style>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
