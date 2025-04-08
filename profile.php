<?php
$pageTitle = 'Профиль - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$subscriptionModel = new Subscription();
$cheatVersionModel = new CheatVersion();

$currentUser = $userModel->getCurrentUser();
$currentSubscription = $subscriptionModel->getUserSubscription($currentUser['id']);
$subscriptionHistory = $subscriptionModel->getUserSubscriptionHistory($currentUser['id']);
$paymentHistory = $subscriptionModel->getUserPayments($currentUser['id']);
$downloadHistory = $cheatVersionModel->getUserDownloadLogs($currentUser['id']);

// Инициализация переменных для формы
$error = '';
$success = '';

// Обработка формы обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? '')
        ];

        // Проверяем, что данные изменились
        if ($data['username'] !== $currentUser['username'] || $data['email'] !== $currentUser['email']) {
            $result = $userModel->updateProfile($currentUser['id'], $data);

            if ($result['success']) {
                $success = 'Профиль успешно обновлен';
                // Перезагружаем текущего пользователя
                $currentUser = $userModel->getCurrentUser();
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Все поля обязательны для заполнения';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Новые пароли не совпадают';
        } else {
            $result = $userModel->updatePassword($currentUser['id'], $currentPassword, $newPassword);

            if ($result['success']) {
                $success = 'Пароль успешно изменен';
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['upload_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $result = $userModel->uploadAvatar($currentUser['id'], $_FILES['avatar']);

        if ($result['success']) {
            $success = 'Аватар успешно загружен';
            // Перезагружаем текущего пользователя
            $currentUser = $userModel->getCurrentUser();
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['remove_avatar'])) {
        $result = $userModel->removeAvatar($currentUser['id']);

        if ($result['success']) {
            $success = 'Аватар успешно удален';
            // Перезагружаем текущего пользователя
            $currentUser = $userModel->getCurrentUser();
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-4">
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <?php if ($currentUser['avatar']): ?>
                    <img src="<?= getUserAvatarUrl($currentUser['avatar']) ?>" alt="Аватар" class="rounded-circle img-fluid mb-3 avatar-lg">
                    <?php else: ?>
                    <div class="avatar-placeholder mb-3">
                        <i class="fas fa-user fa-5x text-secondary"></i>
                    </div>
                    <?php endif; ?>
                    <h5 class="mb-1"><?= htmlspecialchars($currentUser['username']) ?></h5>
                    <p class="text-muted mb-3"><?= $currentUser['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?></p>
                    <p class="text-muted small mb-0">Дата регистрации: <?= formatDate($currentUser['created_at']) ?></p>
                </div>
            </div>

            <?php if ($currentSubscription): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Текущая подписка</h5>
                </div>
                <div class="card-body">
                    <h5 class="text-primary">
                        <?php
                        $plans = $subscriptionModel->getPlans();
                        $planType = $currentSubscription['plan_type'];
                        echo $plans[$planType]['name'];
                        ?>
                    </h5>
                    <p class="mb-2">Статус: <span class="badge bg-success">Активна</span></p>
                    <p class="mb-2">До окончания: <span class="subscription-timer" data-end-date="<?= $currentSubscription['end_date'] ?>">
                        <?php
                        $endDate = strtotime($currentSubscription['end_date']);
                        $now = time();
                        $diff = $endDate - $now;

                        $days = floor($diff / (60 * 60 * 24));
                        $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
                        $minutes = floor(($diff % (60 * 60)) / 60);

                        echo $days . ' дн. ' . $hours . ' ч. ' . $minutes . ' мин.';
                        ?>
                    </span></p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="/subscription.php" class="btn btn-outline-primary btn-sm">Продлить подписку</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Подписка</h5>
                </div>
                <div class="card-body">
                    <p class="text-center mb-2">У вас нет активной подписки</p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="/subscription.php" class="btn btn-primary btn-sm">Купить подписку</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header">
                    <ul class="nav nav-pills card-header-pills" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile-content" type="button" role="tab" aria-controls="profile-content" aria-selected="true">
                                <i class="fas fa-user-cog me-1"></i> Профиль
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security-content" type="button" role="tab" aria-controls="security-content" aria-selected="false">
                                <i class="fas fa-shield-alt me-1"></i> Безопасность
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="avatar-tab" data-bs-toggle="pill" data-bs-target="#avatar-content" type="button" role="tab" aria-controls="avatar-content" aria-selected="false">
                                <i class="fas fa-image me-1"></i> Аватар
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="pill" data-bs-target="#history-content" type="button" role="tab" aria-controls="history-content" aria-selected="false">
                                <i class="fas fa-history me-1"></i> История
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Вкладка "Профиль" -->
                        <div class="tab-pane fade show active" id="profile-content" role="tabpanel" aria-labelledby="profile-tab">
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <label for="username" class="col-sm-3 col-form-label">Логин</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="email" class="col-sm-3 col-form-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Сохранить изменения</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Вкладка "Безопасность" -->
                        <div class="tab-pane fade" id="security-content" role="tabpanel" aria-labelledby="security-tab">
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <label for="current_password" class="col-sm-3 col-form-label">Текущий пароль</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="new_password" class="col-sm-3 col-form-label">Новый пароль</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">Минимум 8 символов</small>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="confirm_password" class="col-sm-3 col-form-label">Подтверждение пароля</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" name="update_password" class="btn btn-primary">Изменить пароль</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Вкладка "Аватар" -->
                        <div class="tab-pane fade" id="avatar-content" role="tabpanel" aria-labelledby="avatar-tab">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <?php if ($currentUser['avatar']): ?>
                                    <img src="<?= getUserAvatarUrl($currentUser['avatar']) ?>" alt="Аватар" class="img-fluid rounded mb-3" style="max-width: 200px;" id="avatar-preview">
                                    <?php else: ?>
                                    <div class="avatar-placeholder mb-3">
                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                    </div>
                                    <img src="" alt="Предпросмотр" class="img-fluid rounded mb-3" style="max-width: 200px; display: none;" id="avatar-preview">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="avatar" class="form-label">Загрузить новый аватар</label>
                                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif">
                                            <small class="text-muted">Допустимые форматы: JPG, PNG, GIF. Максимальный размер: 2MB.</small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="upload_avatar" class="btn btn-primary">Загрузить</button>
                                            <?php if ($currentUser['avatar']): ?>
                                            <button type="submit" name="remove_avatar" class="btn btn-outline-danger">Удалить аватар</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Вкладка "История" -->
                        <div class="tab-pane fade" id="history-content" role="tabpanel" aria-labelledby="history-tab">
                            <ul class="nav nav-tabs mb-3" id="historySubTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="subscriptions-tab" data-bs-toggle="tab" data-bs-target="#subscriptions-content" type="button" role="tab" aria-controls="subscriptions-content" aria-selected="true">
                                        Подписки
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-content" type="button" role="tab" aria-controls="payments-content" aria-selected="false">
                                        Платежи
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="downloads-tab" data-bs-toggle="tab" data-bs-target="#downloads-content" type="button" role="tab" aria-controls="downloads-content" aria-selected="false">
                                        Загрузки
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="historySubTabsContent">
                                <!-- История подписок -->
                                <div class="tab-pane fade show active" id="subscriptions-content" role="tabpanel" aria-labelledby="subscriptions-tab">
                                    <?php if (empty($subscriptionHistory)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> У вас пока нет истории подписок.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>План</th>
                                                    <th>Статус</th>
                                                    <th>Дата начала</th>
                                                    <th>Дата окончания</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subscriptionHistory as $subscription): ?>
                                                <tr>
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
                                                    <td><?= formatDate($subscription['end_date']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- История платежей -->
                                <div class="tab-pane fade" id="payments-content" role="tabpanel" aria-labelledby="payments-tab">
                                    <?php if (empty($paymentHistory)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> У вас пока нет истории платежей.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Дата</th>
                                                    <th>Сумма</th>
                                                    <th>Способ оплаты</th>
                                                    <th>Статус</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paymentHistory as $payment): ?>
                                                <tr>
                                                    <td><?= formatDate($payment['payment_date']) ?></td>
                                                    <td><?= formatPrice($payment['amount']) ?></td>
                                                    <td><?= $payment['payment_method'] ?></td>
                                                    <td>
                                                        <?php if ($payment['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Выполнен</span>
                                                        <?php elseif ($payment['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">В обработке</span>
                                                        <?php elseif ($payment['status'] === 'failed'): ?>
                                                        <span class="badge bg-danger">Ошибка</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- История загрузок -->
                                <div class="tab-pane fade" id="downloads-content" role="tabpanel" aria-labelledby="downloads-tab">
                                    <?php if (empty($downloadHistory)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> У вас пока нет истории загрузок.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Дата</th>
                                                    <th>Версия</th>
                                                    <th>Уровень подписки</th>
                                                    <th>IP адрес</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($downloadHistory as $download): ?>
                                                <tr>
                                                    <td><?= formatDate($download['download_date']) ?></td>
                                                    <td><?= $download['version'] ?></td>
                                                    <td>
                                                        <?php
                                                        $plans = $subscriptionModel->getPlans();
                                                        $requiredPlan = $download['required_plan'];
                                                        echo $plans[$requiredPlan]['name'];
                                                        ?>
                                                    </td>
                                                    <td><?= $download['ip_address'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
