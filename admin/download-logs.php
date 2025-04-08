<?php
$pageTitle = 'Логи загрузок - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$userModel = new User();
$cheatVersionModel = new CheatVersion();
$subscriptionModel = new Subscription();

// Параметры пагинации
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Получаем список логов загрузок
$downloadLogs = $cheatVersionModel->getAllDownloadLogs($perPage, $offset);
$totalLogs = count($cheatVersionModel->getAllDownloadLogs(1000000, 0));

// Параметры пагинации
$totalPages = ceil($totalLogs / $perPage);

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
                        <a href="/admin/download-logs.php" class="nav-link active">
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
            <h2 class="mb-4">Логи загрузок</h2>

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-primary text-white">
                        <div class="stat-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="stat-value"><?= $totalLogs ?></div>
                        <div class="stat-label">Всего загрузок</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-success text-white">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <?php
                        $todayLogs = $cheatVersionModel->getDownloadCountByPeriod('today');
                        ?>
                        <div class="stat-value"><?= $todayLogs ?></div>
                        <div class="stat-label">Загрузки сегодня</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-warning text-dark">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <?php
                        $weekLogs = $cheatVersionModel->getDownloadCountByPeriod('week');
                        ?>
                        <div class="stat-value"><?= $weekLogs ?></div>
                        <div class="stat-label">Загрузки за неделю</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card admin-stat-card bg-info text-white">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <?php
                        $monthLogs = $cheatVersionModel->getDownloadCountByPeriod('month');
                        ?>
                        <div class="stat-value"><?= $monthLogs ?></div>
                        <div class="stat-label">Загрузки за месяц</div>
                    </div>
                </div>
            </div>

            <!-- Таблица логов загрузок -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($downloadLogs)): ?>
                    <div class="alert alert-info mb-0">
                        Логи загрузок не найдены.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Версия чита</th>
                                    <th>Требуемый план</th>
                                    <th>IP адрес</th>
                                    <th>User-Agent</th>
                                    <th>Дата загрузки</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($downloadLogs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td>
                                        <a href="users.php?action=edit&id=<?= $log['user_id'] ?>">
                                            <?= htmlspecialchars($log['username']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($log['email']) ?></small>
                                    </td>
                                    <td>
                                        <a href="cheat-versions.php?action=edit&id=<?= $log['cheat_version_id'] ?>">
                                            <?= htmlspecialchars($log['version']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $plans = $subscriptionModel->getPlans();
                                        $requiredPlan = $log['required_plan'];
                                        echo $plans[$requiredPlan]['name'];
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td>
                                        <div class="user-agent-wrapper" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                            <small><?= htmlspecialchars($log['user_agent']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= formatDate($log['download_date']) ?></td>
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
                                <a class="page-link" href="?page=<?= $page - 1 ?>">
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
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $page) {
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                }
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">
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

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
