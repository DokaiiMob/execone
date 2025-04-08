<?php
$pageTitle = 'История скачиваний - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$cheatVersionModel = new CheatVersion();
$subscriptionModel = new Subscription();

$currentUser = $userModel->getCurrentUser();
$downloadHistory = $cheatVersionModel->getUserDownloadLogs($currentUser['id']);

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">История скачиваний</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($downloadHistory)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> У вас пока нет истории скачиваний.
                        <?php if ($subscriptionModel->hasActiveSubscription($currentUser['id'])): ?>
                        <a href="/downloads.php" class="alert-link">Скачать чит</a>
                        <?php else: ?>
                        Для скачивания чита необходимо <a href="/subscription.php" class="alert-link">приобрести подписку</a>.
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Версия</th>
                                    <th>Требуемая подписка</th>
                                    <th>IP адрес</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($downloadHistory as $download): ?>
                                <tr>
                                    <td><?= formatDate($download['download_date']) ?></td>
                                    <td><strong><?= $download['version'] ?></strong></td>
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
                <div class="card-footer">
                    <a href="/downloads.php" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i> Скачать чит
                    </a>
                    <a href="/profile.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-user me-2"></i> Профиль
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
