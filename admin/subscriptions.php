<?php
$pageTitle = 'Управление подписками - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$userModel = new User();
$subscriptionModel = new Subscription();

// Параметры пагинации
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Параметры поиска
$searchQuery = $_GET['q'] ?? '';

// Обработка действий
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление новой подписки
    if (isset($_POST['add_subscription'])) {
        $userId = (int)$_POST['user_id'];
        $planType = $_POST['plan_type'];

        // Проверяем существование пользователя
        $user = $userModel->getUserById($userId);
        if (!$user) {
            $message = 'Пользователь не найден';
            $messageType = 'danger';
        }
        // Проверяем существование плана
        elseif (!$subscriptionModel->planExists($planType)) {
            $message = 'Указанный план подписки не существует';
            $messageType = 'danger';
        }
        else {
            // Создаем или продлеваем подписку
            $result = $subscriptionModel->extendSubscription(
                $userId,
                $planType
            );

            if ($result['success']) {
                $message = 'Подписка успешно добавлена';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        }
    }
    // Отмена подписки
    elseif (isset($_POST['cancel_subscription'])) {
        $subscriptionId = (int)$_POST['subscription_id'];

        // Получаем информацию о подписке
        $subscription = $subscriptionModel->getSubscriptionById($subscriptionId);

        if (!$subscription) {
            $message = 'Подписка не найдена';
            $messageType = 'danger';
        } else {
            // Отменяем подписку
            $result = $subscriptionModel->cancelSubscriptionById($subscriptionId);

            if ($result['success']) {
                $message = 'Подписка успешно отменена';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        }
    }
}

// Получаем список подписок
if (!empty($searchQuery)) {
    $subscriptions = $subscriptionModel->searchSubscriptions($searchQuery, $perPage, $offset);
    $totalSubscriptions = count($subscriptionModel->searchSubscriptions($searchQuery, 1000000, 0));
} else {
    $subscriptions = $subscriptionModel->getAllActiveSubscriptions($perPage, $offset);
    $totalSubscriptions = count($subscriptionModel->getAllActiveSubscriptions(1000000, 0));
}

// Параметры пагинации
$totalPages = ceil($totalSubscriptions / $perPage);

require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-2">
            <div class="admin-sidebar rounded p-3">
                <h5 class="text-white mb-3">Админ-панель</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="/admin/" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i> Панель управления
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/users.php" class="nav-link">
                            <i class="fas fa-users"></i> Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/subscriptions.php" class="nav-link active">
                            <i class="fas fa-gem"></i> Подписки
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/payments.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i> Платежи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/cheat-versions.php" class="nav-link">
                            <i class="fas fa-code-branch"></i> Версии чита
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/download-logs.php" class="nav-link">
                            <i class="fas fa-download"></i> Логи загрузок
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/settings.php" class="nav-link">
                            <i class="fas fa-cogs"></i> Настройки сайта
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a href="/" class="nav-link text-warning">
                            <i class="fas fa-arrow-left"></i> Вернуться на сайт
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-10">
            <h2 class="mb-4">Управление подписками</h2>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Поиск и фильтры -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form action="" method="GET" class="d-flex">
                                <input type="text" name="q" class="form-control me-2" placeholder="Поиск по логину или email..." value="<?= htmlspecialchars($searchQuery) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
                                <i class="fas fa-plus"></i> Добавить подписку
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Таблица подписок -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($subscriptions)): ?>
                    <div class="alert alert-info mb-0">
                        <?php if (!empty($searchQuery)): ?>
                        По вашему запросу ничего не найдено. <a href="subscriptions.php">Сбросить поиск</a>
                        <?php else: ?>
                        Активных подписок не найдено.
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Тип плана</th>
                                    <th>Статус</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptions as $subscription): ?>
                                <tr>
                                    <td><?= $subscription['id'] ?></td>
                                    <td>
                                        <a href="users.php?action=edit&id=<?= $subscription['user_id'] ?>">
                                            <?= htmlspecialchars($subscription['username']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($subscription['email']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $plans = $subscriptionModel->getPlans();
                                        $planType = $subscription['plan_type'];
                                        echo $plans[$planType]['name'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($subscription['status'] === 'active'): ?>
                                        <span class="badge bg-success">Активна</span>
                                        <?php elseif ($subscription['status'] === 'expired'): ?>
                                        <span class="badge bg-secondary">Истекла</span>
                                        <?php elseif ($subscription['status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger">Отменена</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($subscription['start_date']) ?></td>
                                    <td>
                                        <?= formatDate($subscription['end_date']) ?>
                                        <?php
                                        $now = new DateTime();
                                        $endDate = new DateTime($subscription['end_date']);
                                        $interval = $now->diff($endDate);

                                        if ($endDate < $now) {
                                            echo '<br><small class="text-danger">Просрочена</small>';
                                        } elseif ($interval->days <= 3) {
                                            echo '<br><small class="text-warning">Скоро истекает</small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($subscription['status'] === 'active'): ?>
                                        <form method="POST" onsubmit="return confirm('Вы уверены, что хотите отменить эту подписку?');">
                                            <input type="hidden" name="subscription_id" value="<?= $subscription['id'] ?>">
                                            <button type="submit" name="cancel_subscription" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i> Отменить
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-times"></i> Отменена
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                            </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '') . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $page) {
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $i . (!empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '') . '">' . $i . '</a></li>';
                                }
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '') . '">' . $totalPages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления подписки -->
<div class="modal fade" id="addSubscriptionModal" tabindex="-1" aria-labelledby="addSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubscriptionModalLabel">Добавить подписку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">ID пользователя</label>
                        <input type="number" class="form-control" id="user_id" name="user_id" required>
                        <small class="text-muted">Введите ID пользователя из таблицы пользователей</small>
                    </div>
                    <div class="mb-3">
                        <label for="plan_type" class="form-label">Тип плана</label>
                        <select class="form-select" id="plan_type" name="plan_type" required>
                            <?php foreach ($subscriptionModel->getPlans() as $planType => $plan): ?>
                            <option value="<?= $planType ?>"><?= $plan['name'] ?> (<?= formatPrice($plan['price']) ?>, <?= $plan['duration'] ?> дней)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_subscription" class="btn btn-primary">Добавить подписку</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
