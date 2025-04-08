<?php
$pageTitle = 'Настройки сайта - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

// Загружаем конфигурацию
$config = require __DIR__ . '/../config/config.php';

// Обработка сохранения настроек
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        try {
            // Обновляем настройки сайта
            $newConfig = $config;

            // Настройки сайта
            $newConfig['site']['name'] = sanitizeInput($_POST['site_name']);
            $newConfig['site']['description'] = sanitizeInput($_POST['site_description']);
            $newConfig['site']['url'] = sanitizeInput($_POST['site_url']);
            $newConfig['site']['debug'] = isset($_POST['site_debug']);

            // Настройки авторизации
            $newConfig['auth']['session_lifetime'] = (int)$_POST['session_lifetime'];
            $newConfig['auth']['password_min_length'] = (int)$_POST['password_min_length'];
            $newConfig['auth']['require_email_verification'] = isset($_POST['require_email_verification']);

            // Настройки подписок
            foreach ($newConfig['subscription']['plans'] as $planType => $plan) {
                if (isset($_POST["plan_{$planType}_name"])) {
                    $newConfig['subscription']['plans'][$planType]['name'] = sanitizeInput($_POST["plan_{$planType}_name"]);
                }
                if (isset($_POST["plan_{$planType}_price"])) {
                    $newConfig['subscription']['plans'][$planType]['price'] = (int)$_POST["plan_{$planType}_price"];
                }
                if (isset($_POST["plan_{$planType}_duration"])) {
                    $newConfig['subscription']['plans'][$planType]['duration'] = (int)$_POST["plan_{$planType}_duration"];
                }
            }

            // Записываем конфигурацию в файл
            $configContent = "<?php\n/**\n * Конфигурационный файл сайта для чита в SAMP\n */\n\nreturn " . var_export($newConfig, true) . ";\n";
            $configFile = __DIR__ . '/../config/config.php';

            if (file_put_contents($configFile, $configContent)) {
                $message = 'Настройки успешно сохранены';
                $messageType = 'success';

                // Обновляем конфигурацию для текущего запроса
                $config = $newConfig;
            } else {
                $message = 'Ошибка при сохранении настроек. Проверьте права на запись файла.';
                $messageType = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Произошла ошибка: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

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
                        <a href="/admin/download-logs.php" class="nav-link">
                            <i class="fas fa-download"></i> Логи загрузок
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/settings.php" class="nav-link active">
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
            <h2 class="mb-4">Настройки сайта</h2>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row">
                            <!-- Настройки сайта -->
                            <div class="col-md-6">
                                <h4 class="mb-3">Основные настройки</h4>
                                <div class="mb-3">
                                    <label for="site_name" class="form-label">Название сайта</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($config['site']['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Описание сайта</label>
                                    <textarea class="form-control" id="site_description" name="site_description" rows="2"><?= htmlspecialchars($config['site']['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="site_url" class="form-label">URL сайта</label>
                                    <input type="url" class="form-control" id="site_url" name="site_url" value="<?= htmlspecialchars($config['site']['url']) ?>" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="site_debug" name="site_debug" <?= $config['site']['debug'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="site_debug">Режим отладки</label>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Не включайте на рабочем сайте!
                                    </div>
                                </div>

                                <h4 class="mt-4 mb-3">Настройки авторизации</h4>
                                <div class="mb-3">
                                    <label for="session_lifetime" class="form-label">Время жизни сессии (в минутах)</label>
                                    <input type="number" class="form-control" id="session_lifetime" name="session_lifetime" value="<?= $config['auth']['session_lifetime'] ?>" min="5" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_min_length" class="form-label">Минимальная длина пароля</label>
                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" value="<?= $config['auth']['password_min_length'] ?>" min="4" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="require_email_verification" name="require_email_verification" <?= $config['auth']['require_email_verification'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="require_email_verification">Требовать подтверждение email</label>
                                </div>
                            </div>

                            <!-- Настройки подписок -->
                            <div class="col-md-6">
                                <h4 class="mb-3">Тарифные планы</h4>

                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        Базовый план
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="plan_basic_name" class="form-label">Название</label>
                                            <input type="text" class="form-control" id="plan_basic_name" name="plan_basic_name" value="<?= htmlspecialchars($config['subscription']['plans']['basic']['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_basic_price" class="form-label">Стоимость (руб.)</label>
                                            <input type="number" class="form-control" id="plan_basic_price" name="plan_basic_price" value="<?= $config['subscription']['plans']['basic']['price'] ?>" min="0" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_basic_duration" class="form-label">Длительность (дней)</label>
                                            <input type="number" class="form-control" id="plan_basic_duration" name="plan_basic_duration" value="<?= $config['subscription']['plans']['basic']['duration'] ?>" min="1" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        Премиум план
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="plan_premium_name" class="form-label">Название</label>
                                            <input type="text" class="form-control" id="plan_premium_name" name="plan_premium_name" value="<?= htmlspecialchars($config['subscription']['plans']['premium']['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_premium_price" class="form-label">Стоимость (руб.)</label>
                                            <input type="number" class="form-control" id="plan_premium_price" name="plan_premium_price" value="<?= $config['subscription']['plans']['premium']['price'] ?>" min="0" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_premium_duration" class="form-label">Длительность (дней)</label>
                                            <input type="number" class="form-control" id="plan_premium_duration" name="plan_premium_duration" value="<?= $config['subscription']['plans']['premium']['duration'] ?>" min="1" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-dark">
                                        VIP план
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="plan_vip_name" class="form-label">Название</label>
                                            <input type="text" class="form-control" id="plan_vip_name" name="plan_vip_name" value="<?= htmlspecialchars($config['subscription']['plans']['vip']['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_vip_price" class="form-label">Стоимость (руб.)</label>
                                            <input type="number" class="form-control" id="plan_vip_price" name="plan_vip_price" value="<?= $config['subscription']['plans']['vip']['price'] ?>" min="0" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plan_vip_duration" class="form-label">Длительность (дней)</label>
                                            <input type="number" class="form-control" id="plan_vip_duration" name="plan_vip_duration" value="<?= $config['subscription']['plans']['vip']['duration'] ?>" min="1" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" name="save_settings" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i> Сохранить настройки
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
