<?php
$pageTitle = 'Скачать чит - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию и наличие активной подписки
requireLogin();
requireActiveSubscription();

$userModel = new User();
$subscriptionModel = new Subscription();
$cheatVersionModel = new CheatVersion();

$currentUser = $userModel->getCurrentUser();
$currentSubscription = $subscriptionModel->getUserSubscription($currentUser['id']);
$availableVersions = $cheatVersionModel->getAvailableVersionsForUser($currentUser['id']);

// Инициализация переменных для сообщений
$error = '';

// Обработка скачивания чита
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $versionId = (int) $_GET['download'];
    $version = $cheatVersionModel->getVersionById($versionId);

    if (!$version) {
        $error = 'Указанная версия чита не найдена';
    } else {
        $plan = $currentSubscription['plan_type'];
        $requiredPlan = $version['required_plan'];

        $canDownload = false;

        if ($plan === 'vip') {
            // VIP подписка имеет доступ ко всем версиям
            $canDownload = true;
        } elseif ($plan === 'premium' && ($requiredPlan === 'basic' || $requiredPlan === 'premium')) {
            // Premium подписка имеет доступ к Basic и Premium версиям
            $canDownload = true;
        } elseif ($plan === 'basic' && $requiredPlan === 'basic') {
            // Basic подписка имеет доступ только к Basic версиям
            $canDownload = true;
        }

        if ($canDownload) {
            // Логируем скачивание
            $cheatVersionModel->logDownload($currentUser['id'], $versionId);

            // Путь к файлу
            $filePath = $config['uploads']['cheat_files']['path'] . $version['file_path'];

            if (file_exists($filePath)) {
                // Устанавливаем заголовки для скачивания файла
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));

                // Отправляем файл пользователю
                readfile($filePath);
                exit;
            } else {
                $error = 'Файл не найден';
            }
        } else {
            $error = 'У вас нет доступа к данной версии чита. Требуется подписка более высокого уровня.';
        }
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Заголовок страницы -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Скачать чит для SAMP</h1>
        <p class="lead">Доступные версии чита для скачивания</p>
    </div>

    <!-- Информация о подписке -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Ваша подписка</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <?php
                                $plans = $subscriptionModel->getPlans();
                                $planType = $currentSubscription['plan_type'];
                                echo $plans[$planType]['name'];
                                ?>
                            </h5>
                            <p class="mb-2">Дата окончания: <?= formatDate($currentSubscription['end_date']) ?></p>
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
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="/subscription.php" class="btn btn-primary">Управление подпиской</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Версии чита для скачивания -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Доступные версии</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($availableVersions)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Нет доступных версий для вашего тарифного плана. Пожалуйста, <a href="/subscription.php">обновите подписку</a>.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Версия</th>
                                    <th>Дата выпуска</th>
                                    <th>Требуемая подписка</th>
                                    <th>Описание</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableVersions as $version): ?>
                                <tr>
                                    <td><strong><?= $version['version'] ?></strong></td>
                                    <td><?= formatDate($version['created_at']) ?></td>
                                    <td>
                                        <?php
                                        $requiredPlan = $version['required_plan'];
                                        echo $plans[$requiredPlan]['name'];
                                        ?>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars($version['description'])) ?></td>
                                    <td>
                                        <a href="?download=<?= $version['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download me-1"></i> Скачать
                                        </a>
                                        <a href="/reviews.php?version_id=<?= $version['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-star me-1"></i> Отзывы
                                        </a>
                                    </td>
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

    <!-- Инструкция по установке -->
    <div class="row mt-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Инструкция по установке</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Системные требования:</h5>
                            <ul>
                                <li>Операционная система: Windows 7/8/10/11 (64-бит)</li>
                                <li>Процессор: Intel Core i3 или аналогичный AMD</li>
                                <li>Оперативная память: 4 ГБ</li>
                                <li>Видеокарта: совместимая с DirectX 9</li>
                                <li>Свободное место на диске: 50 МБ</li>
                            </ul>

                            <h5 class="mt-4">Установка:</h5>
                            <ol>
                                <li>Скачайте последнюю версию чита</li>
                                <li>Распакуйте архив в любую папку на вашем компьютере</li>
                                <li>Запустите файл <code>launcher.exe</code> от имени администратора</li>
                                <li>В открывшемся окне введите свой логин и пароль от сайта</li>
                                <li>Нажмите кнопку "Запустить"</li>
                                <li>Запустите игру SAMP</li>
                            </ol>

                            <h5 class="mt-4">Использование:</h5>
                            <ul>
                                <li>Для открытия меню чита нажмите клавишу <kbd>INSERT</kbd></li>
                                <li>Настройте функции чита по вашему предпочтению</li>
                                <li>Для закрытия меню нажмите клавишу <kbd>INSERT</kbd> еще раз</li>
                                <li>Для полного отключения чита нажмите комбинацию клавиш <kbd>CTRL</kbd> + <kbd>F4</kbd></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Важно!</h5>
                                <p>Для корректной работы чита рекомендуется:</p>
                                <ul>
                                    <li>Временно отключить антивирус перед запуском</li>
                                    <li>Запускать как launcher, так и игру от имени администратора</li>
                                    <li>Убедиться, что установлены все необходимые компоненты (DirectX, Visual C++ Redistributable)</li>
                                </ul>
                                <p class="mb-0">При возникновении любых проблем обращайтесь в <a href="#" class="alert-link">службу поддержки</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
