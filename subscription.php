<?php
$pageTitle = 'Подписки - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$subscriptionModel = new Subscription();
$loyaltyModel = new Loyalty();
$referralModel = new Referral(); // Добавлено для работы с бонусами

$currentUser = $userModel->getCurrentUser();
$currentSubscription = $subscriptionModel->getUserSubscription($currentUser['id']);
$plans = $subscriptionModel->getPlans();

// Инициализация переменных для формы
$error = '';
$success = '';

// Получаем данные о лояльности пользователя
$loyaltyData = $loyaltyModel->getUserLoyaltyData($currentUser['id']);

// Выбранный план из GET-параметра
$selectedPlan = $_GET['plan'] ?? 'basic';
if (!isset($plans[$selectedPlan])) {
    $selectedPlan = 'basic';
}

// Обработка формы покупки подписки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['purchase_subscription'])) {
        $planType = $_POST['plan_type'] ?? 'basic';
        if (!isset($plans[$planType])) {
            $error = 'Выбран неверный план подписки';
        } else {
            $plan = $plans[$planType];
            $paymentMethod = $_POST['payment_method'] ?? '';
            $useBonusDays = isset($_POST['use_bonus_days']) ? true : false; // Получаем информацию о использовании бонусных дней

            if (empty($paymentMethod)) {
                $error = 'Выберите способ оплаты';
            } else {
                // Здесь должен быть код для обработки платежа через выбранную платежную систему
                // В данном примере мы просто создаем подписку и платеж без реальной оплаты

                // Создаем запись о платеже
                $paymentResult = $subscriptionModel->createPayment(
                    $currentUser['id'],
                    $plan['price'],
                    $paymentMethod,
                    'completed'
                );

                if ($paymentResult['success']) {
                    // Создаем или продлеваем подписку с учетом бонусных дней
                    $subscriptionResult = $subscriptionModel->extendSubscription(
                        $currentUser['id'],
                        $planType,
                        $paymentResult['payment_id'],
                        $useBonusDays // Передаем информацию о бонусных днях
                    );

                    if ($subscriptionResult['success']) {
                        $success = 'Подписка успешно оформлена! Теперь у вас есть доступ к скачиванию чита.';
                        // Обновляем текущую подписку
                        $currentSubscription = $subscriptionModel->getUserSubscription($currentUser['id']);
                    } else {
                        $error = 'Ошибка при создании подписки';
                    }
                } else {
                    $error = 'Ошибка при обработке платежа';
                }
            }
        }
    } elseif (isset($_POST['cancel_subscription'])) {
        if (!$currentSubscription) {
            $error = 'У вас нет активной подписки';
        } else {
            $result = $subscriptionModel->cancelSubscription($currentUser['id']);

            if ($result['success']) {
                $success = 'Подписка успешно отменена';
                // Обновляем текущую подписку
                $currentSubscription = $subscriptionModel->getUserSubscription($currentUser['id']);
            } else {
                $error = $result['message'];
            }
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

    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Заголовок страницы -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Тарифные планы</h1>
        <p class="lead">Выберите подходящий тарифный план и получите доступ к читу уже сегодня</p>
    </div>

    <!-- Карточки с тарифами -->
    <div class="row mb-5">
        <?php foreach ($plans as $planType => $plan): ?>
            <?php
            $featured = ($planType === 'premium') ? 'featured' : '';
            $isCurrentPlan = ($currentSubscription && $currentSubscription['plan_type'] === $planType);
            ?>
            <div class="col-lg-4 mb-4">
                <div class="card subscription-card <?= $featured ?>">
                    <div class="card-header">
                        <h4 class="mb-0"><?= $plan['name'] ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="subscription-price"><?= formatPrice($plan['price']) ?></div>
                            <div class="subscription-period">на <?= $plan['duration'] ?> дней</div>
                        </div>
                        <ul class="subscription-features mb-4">
                            <?php foreach ($plan['features'] as $feature): ?>
                            <li class="text-center"><i class="fas fa-check"></i> <?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center">
                            <?php if ($isCurrentPlan): ?>
                            <button class="btn btn-success" disabled>Текущий план</button>
                            <?php else: ?>
                            <a href="?plan=<?= $planType ?>" class="btn btn-primary">Выбрать</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Ссылка на индивидуальные планы -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-3">Хотите создать индивидуальный план?</h4>
                    <p>Выберите только нужные функции, настройте длительность и получите максимальную выгоду!</p>
                    <a href="/custom-plan.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sliders-h me-2"></i> Создать индивидуальный план
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Информация о текущей подписке -->
    <?php if ($currentSubscription): ?>
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Текущая подписка</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <?php
                                $planType = $currentSubscription['plan_type'];
                                echo $plans[$planType]['name'];
                                ?>
                            </h5>
                            <p class="mb-2">Статус: <span class="badge bg-success">Активна</span></p>
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
                            <form action="" method="POST" onsubmit="return confirm('Вы уверены, что хотите отменить подписку? Это действие нельзя отменить.');">
                                <button type="submit" name="cancel_subscription" class="btn btn-outline-danger">Отменить подписку</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Информация о программе лояльности -->
    <?php if ($loyaltyData['success']): ?>
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-<?= $loyaltyData['loyalty_level'] ?> text-white">
                    <h4 class="mb-0"><i class="fas fa-medal me-2"></i> Ваш уровень лояльности: <?= $loyaltyData['loyalty_level_name'] ?></h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Текущий статус</h5>
                            <div class="mb-3">
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $loyaltyData['loyalty_level'] ?>" role="progressbar" style="width: <?= $loyaltyData['progress'] ?>%;" aria-valuenow="<?= $loyaltyData['progress'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $loyaltyData['progress'] ?>%</div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small>Текущий: <?= $loyaltyData['loyalty_days'] ?> дней</small>
                                    <?php if ($loyaltyData['next_level']): ?>
                                    <small>Следующий: <?= $loyaltyData['days_for_next_level'] ?> дней до <?= $loyaltyData['levels'][$loyaltyData['next_level']]['name'] ?></small>
                                    <?php else: ?>
                                    <small>Максимальный уровень достигнут</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p>Текущая скидка: <span class="badge bg-success"><?= $loyaltyData['discount'] ?>%</span></p>
                        </div>
                        <div class="col-md-8">
                            <h5>Преимущества вашего уровня</h5>
                            <ul class="list-group">
                                <?php foreach ($loyaltyData['benefits'] as $benefit): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i> <?= $benefit ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Форма оплаты -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Оформление подписки</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h5>Выбранный план:</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <select class="form-select plan-selector" id="plan-selector" name="plan_type" data-plans='<?= json_encode($plans) ?>'>
                                        <?php foreach ($plans as $planType => $plan): ?>
                                        <option value="<?= $planType ?>" <?= ($selectedPlan === $planType) ? 'selected' : '' ?>>
                                            <?= $plan['name'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1">Стоимость: <span id="plan-price" class="fw-bold"><?= formatPrice($plans[$selectedPlan]['price']) ?></span></p>
                                    <p class="mb-1">Длительность: <span id="plan-duration" class="fw-bold"><?= $plans[$selectedPlan]['duration'] ?> дней</span></p>
                                </div>
                                <?php if ($currentSubscription): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> У вас уже есть активная подписка. При оформлении новой подписки, срок действия будет добавлен к текущему.
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h5>Способ оплаты:</h5>
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-card" value="card" checked>
                                        <label class="form-check-label" for="payment-card">
                                            <i class="fas fa-credit-card me-2"></i> Банковская карта
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-qiwi" value="qiwi">
                                        <label class="form-check-label" for="payment-qiwi">
                                            <i class="fas fa-wallet me-2"></i> QIWI Кошелек
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-yoomoney" value="yoomoney">
                                        <label class="form-check-label" for="payment-yoomoney">
                                            <i class="fas fa-money-bill-wave me-2"></i> ЮMoney
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-crypto" value="crypto">
                                        <label class="form-check-label" for="payment-crypto">
                                            <i class="fab fa-bitcoin me-2"></i> Криптовалюта
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="use_bonus_days" name="use_bonus_days" value="1">
                                <label class="form-check-label" for="use_bonus_days">
                                    <i class="fas fa-gift text-success me-2"></i> Использовать мои бонусные дни (<?= $referralModel->getUserBonusBalance($currentUser['id']) ?> дней)
                                </label>
                                <div class="form-text">Бонусные дни будут добавлены к длительности вашей подписки</div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="purchase_subscription" class="btn btn-primary btn-lg">Оплатить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
