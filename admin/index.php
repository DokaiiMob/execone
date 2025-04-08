<?php
$pageTitle = 'Админ-панель - Чит для SAMP';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$userModel = new User();
$subscriptionModel = new Subscription();
$cheatVersionModel = new CheatVersion();

// Получаем статистические данные
$usersCount = count($userModel->getAllUsers(1000000, 0));
$activeSubscriptionsCount = count($subscriptionModel->getAllActiveSubscriptions(1000000, 0));
$cheatVersionsCount = count($cheatVersionModel->getAllVersions(true, 1000000, 0));
$downloadsCount = count($cheatVersionModel->getAllDownloadLogs(1000000, 0));

// Получаем последние 5 пользователей
$latestUsers = $userModel->getAllUsers(5, 0);

// Получаем последние 5 подписок
$latestSubscriptions = $subscriptionModel->getAllActiveSubscriptions(5, 0);

// Получаем последние 5 скачиваний
$latestDownloads = $cheatVersionModel->getAllDownloadLogs(5, 0);

require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-2">
            <div class="admin-sidebar rounded p-3">
                <h5 class="text-white mb-3">Админ-панель</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="/admin/" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i> Панель управления
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/users.php" class="nav-link">
                            <i class="fas fa-users"></i> Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/subscriptions.php" class="nav-link">
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
            <h2 class="mb-4">Панель управления</h2>

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-primary text-white">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?= $usersCount ?></div>
                        <div class="stat-label">Пользователей</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-success text-white">
                        <div class="stat-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <div class="stat-value"><?= $activeSubscriptionsCount ?></div>
                        <div class="stat-label">Активных подписок</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-info text-white">
                        <div class="stat-icon">
                            <i class="fas fa-code-branch"></i>
                        </div>
                        <div class="stat-value"><?= $cheatVersionsCount ?></div>
                        <div class="stat-label">Версий чита</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-warning text-white">
                        <div class="stat-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="stat-value"><?= $downloadsCount ?></div>
                        <div class="stat-label">Скачиваний</div>
                    </div>
                </div>
            </div>

            <!-- Последние активности -->
            <div class="row">
                <!-- Последние пользователи -->
                <div class="col-lg-4 mb-4">
                    <div class="card admin-card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Последние пользователи</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($latestUsers)): ?>
                            <p class="p-3 mb-0">Нет данных для отображения</p>
                            <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($latestUsers as $user): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <div class="text-muted small"><?= $user['email'] ?></div>
                                        </div>
                                        <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                            <?= $user['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/admin/users.php" class="btn btn-sm btn-primary">Все пользователи</a>
                        </div>
                    </div>
                </div>

                <!-- Последние подписки -->
                <div class="col-lg-4 mb-4">
                    <div class="card admin-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Последние подписки</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($latestSubscriptions)): ?>
                            <p class="p-3 mb-0">Нет данных для отображения</p>
                            <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($latestSubscriptions as $subscription): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($subscription['username']) ?></strong>
                                            <div class="text-muted small">
                                                <?php
                                                $plans = $subscriptionModel->getPlans();
                                                $planType = $subscription['plan_type'];
                                                echo $plans[$planType]['name'];
                                                ?>
                                            </div>
                                        </div>
                                        <span class="text-muted small">
                                            до <?= formatDate($subscription['end_date'], 'd.m.Y') ?>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/admin/subscriptions.php" class="btn btn-sm btn-success">Все подписки</a>
                        </div>
                    </div>
                </div>

                <!-- Последние скачивания -->
                <div class="col-lg-4 mb-4">
                    <div class="card admin-card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Последние скачивания</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($latestDownloads)): ?>
                            <p class="p-3 mb-0">Нет данных для отображения</p>
                            <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($latestDownloads as $download): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($download['username']) ?></strong>
                                            <div class="text-muted small">
                                                Версия: <?= $download['version'] ?>
                                            </div>
                                        </div>
                                        <span class="text-muted small">
                                            <?= formatDate($download['download_date']) ?>
                                        </span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/admin/download-logs.php" class="btn btn-sm btn-warning">Все скачивания</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card admin-card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Быстрые действия</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <a href="/admin/users.php?action=add" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i> Добавить пользователя
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="/admin/cheat-versions.php?action=add" class="btn btn-success w-100">
                                        <i class="fas fa-upload me-2"></i> Загрузить новую версию чита
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="/admin/settings.php" class="btn btn-info w-100">
                                        <i class="fas fa-cogs me-2"></i> Настройки сайта
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
