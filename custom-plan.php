<?php
$pageTitle = 'Индивидуальный план - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$subscriptionModel = new Subscription();
$loyaltyModel = new Loyalty();

// Убедимся, что таблица кастомных планов создана
$subscriptionModel->createCustomPlansTable();

$currentUser = $userModel->getCurrentUser();
$userId = $currentUser['id'];
$currentSubscription = $subscriptionModel->getUserSubscription($userId);

// Получаем информацию о лояльности пользователя
$loyaltyData = $loyaltyModel->getUserLoyaltyData($userId);

// Получаем все кастомные планы пользователя
$userCustomPlans = $subscriptionModel->getUserCustomPlans($userId);

// Получаем список всех стандартных планов
$standardPlans = $subscriptionModel->getPlans();

// Собираем все доступные функции из разных планов
$allFeatures = [];
foreach ($standardPlans as $planType => $plan) {
    foreach ($plan['features'] as $feature) {
        if (!in_array($feature, $allFeatures)) {
            $allFeatures[] = $feature;
        }
    }
}

// Инициализация переменных для формы
$error = '';
$success = '';
$customPlan = null;

// Обработка формы создания индивидуального плана
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_custom_plan'])) {
        $basePlanType = $_POST['base_plan'] ?? 'basic';
        $duration = intval($_POST['duration'] ?? 30);

        // Проверка валидности длительности
        if ($duration < 7 || $duration > 365) {
            $error = 'Длительность подписки должна быть от 7 до 365 дней';
        } else {
            // Получаем выбранные функции, если есть
            $selectedFeatures = [];
            if (isset($_POST['features']) && is_array($_POST['features'])) {
                $selectedFeatures = $_POST['features'];
            }

            // Создаем индивидуальный план
            $customPlan = $subscriptionModel->createCustomPlan(
                $basePlanType,
                $duration,
                !empty($selectedFeatures) ? $selectedFeatures : null
            );

            if ($customPlan['success']) {
                // Рассчитываем скидку
                $discountInfo = $subscriptionModel->calculateDiscount(
                    $userId,
                    $customPlan['price'],
                    $customPlan['duration']
                );

                // Сохраняем план в БД
                $saveResult = $subscriptionModel->saveCustomPlan($userId, $customPlan);

                if ($saveResult['success']) {
                    $success = 'Индивидуальный план успешно создан! Теперь вы можете его оплатить.';
                    // Добавляем информацию о скидке к плану
                    $customPlan['discount_info'] = $discountInfo;
                } else {
                    $error = 'Ошибка при сохранении плана: ' . $saveResult['message'];
                }
            } else {
                $error = 'Ошибка при создании плана: ' . $customPlan['message'];
            }
        }
    } elseif (isset($_POST['buy_custom_plan']) && isset($_POST['plan_id'])) {
        $planId = $_POST['plan_id'];
        $plan = $subscriptionModel->getCustomPlan($planId);

        if ($plan) {
            $paymentMethod = $_POST['payment_method'] ?? '';

            if (empty($paymentMethod)) {
                $error = 'Выберите способ оплаты';
            } else {
                // Рассчитываем скидку
                $discountInfo = $subscriptionModel->calculateDiscount(
                    $userId,
                    $plan['price'],
                    $plan['duration']
                );

                $finalPrice = $discountInfo['final_price'];

                // Создаем запись о платеже
                $paymentResult = $subscriptionModel->createPayment(
                    $userId,
                    $finalPrice,
                    $paymentMethod,
                    'completed'
                );

                if ($paymentResult['success']) {
                    // Создаем или продлеваем подписку
                    $subscriptionResult = $subscriptionModel->extendSubscription(
                        $userId,
                        $plan['base_plan'],
                        $paymentResult['payment_id']
                    );

                    if ($subscriptionResult['success']) {
                        // Обновляем текущую подписку
                        $currentSubscription = $subscriptionModel->getUserSubscription($userId);

                        // Начисляем дни лояльности
                        $loyaltyModel->processSubscriptionPurchase(
                            $userId,
                            $plan['base_plan'],
                            $plan['duration']
                        );

                        $success = 'Подписка успешно оформлена! Теперь у вас есть доступ к скачиванию чита на ' . $plan['duration'] . ' дней.';
                    } else {
                        $error = 'Ошибка при создании подписки';
                    }
                } else {
                    $error = 'Ошибка при обработке платежа';
                }
            }
        } else {
            $error = 'План не найден';
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
        <h1 class="display-5 fw-bold">Создайте индивидуальный план</h1>
        <p class="lead">Настройте план подписки под свои нужды и получите максимальную выгоду</p>
    </div>

    <!-- Информация о текущей подписке -->
    <?php if ($currentSubscription): ?>
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle fa-2x me-3"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading">У вас уже есть активная подписка</h5>
                <p class="mb-0">План: <strong><?= $standardPlans[$currentSubscription['plan_type']]['name'] ?></strong>, действует до: <strong><?= formatDate($currentSubscription['end_date']) ?></strong></p>
                <p class="mb-0">При покупке нового плана, его длительность будет добавлена к текущей подписке.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Форма создания индивидуального плана -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Настройте свой план</h4>
                </div>
                <div class="card-body">
                    <form id="custom-plan-form" action="" method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="base_plan" class="form-label">Базовый план</label>
                                <select class="form-select" id="base_plan" name="base_plan">
                                    <?php foreach ($standardPlans as $planType => $plan): ?>
                                    <option value="<?= $planType ?>"><?= $plan['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Выберите базовый план, от которого будет рассчитана стоимость</div>
                            </div>
                            <div class="col-md-6">
                                <label for="duration" class="form-label">Длительность (дней)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duration" name="duration" min="7" max="365" value="30">
                                    <span class="input-group-text">дней</span>
                                </div>
                                <div class="form-text">От 7 до 365 дней</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Выберите нужные функции</h5>
                            <div class="row">
                                <?php foreach ($allFeatures as $index => $feature): ?>
                                <div class="col-lg-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input feature-checkbox" type="checkbox" id="feature_<?= $index ?>" name="features[]" value="<?= htmlspecialchars($feature) ?>">
                                        <label class="form-check-label" for="feature_<?= $index ?>">
                                            <?= htmlspecialchars($feature) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="create_custom_plan" class="btn btn-primary btn-lg">Рассчитать стоимость</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Результат расчета кастомного плана -->
            <?php if ($customPlan && $customPlan['success']): ?>
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Ваш индивидуальный план</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Параметры плана:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Базовый план:
                                    <span class="fw-bold"><?= $standardPlans[$customPlan['base_plan']]['name'] ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Длительность:
                                    <span class="fw-bold"><?= $customPlan['duration'] ?> дней</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Стоимость в день:
                                    <span class="fw-bold"><?= formatPrice($customPlan['daily_price']) ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Стоимость:</h5>
                            <?php if (isset($customPlan['discount_info']) && $customPlan['discount_info']['discount_percent'] > 0): ?>
                                <div class="mb-3">
                                    <p class="mb-1">Базовая стоимость: <span class="text-decoration-line-through"><?= formatPrice($customPlan['price']) ?></span></p>

                                    <?php foreach ($customPlan['discount_info']['discounts'] as $discount): ?>
                                    <p class="mb-1 text-success">
                                        <i class="fas fa-tag me-1"></i> <?= $discount['name'] ?>: -<?= $discount['percent'] ?>%
                                    </p>
                                    <?php endforeach; ?>

                                    <p class="fs-4 fw-bold">Итого к оплате: <?= formatPrice($customPlan['discount_info']['final_price']) ?></p>
                                    <p class="text-success mb-0">Вы экономите: <?= formatPrice($customPlan['discount_info']['discount_amount']) ?></p>
                                </div>
                            <?php else: ?>
                                <p class="fs-4 fw-bold">Итого к оплате: <?= formatPrice($customPlan['price']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h5 class="mb-3">Выбранные функции:</h5>
                    <div class="row mb-4">
                        <?php foreach ($customPlan['features'] as $feature): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span><?= htmlspecialchars($feature) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="plan_id" value="<?= htmlspecialchars($saveResult['plan_id'] ?? '') ?>">

                        <div class="mb-4">
                            <h5>Способ оплаты:</h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-card" value="card" checked>
                                        <label class="form-check-label" for="payment-card">
                                            <i class="fas fa-credit-card me-2"></i> Банковская карта
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-qiwi" value="qiwi">
                                        <label class="form-check-label" for="payment-qiwi">
                                            <i class="fas fa-wallet me-2"></i> QIWI Кошелек
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-yoomoney" value="yoomoney">
                                        <label class="form-check-label" for="payment-yoomoney">
                                            <i class="fas fa-money-bill-wave me-2"></i> ЮMoney
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment-crypto" value="crypto">
                                        <label class="form-check-label" for="payment-crypto">
                                            <i class="fab fa-bitcoin me-2"></i> Криптовалюта
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="buy_custom_plan" class="btn btn-success btn-lg">Оплатить</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Сайдбар с информацией и сохраненными планами -->
        <div class="col-lg-4">
            <!-- Информация о скидках -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-tag me-2"></i> Доступные скидки</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if ($loyaltyData['success'] && $loyaltyData['discount'] > 0): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-medal me-2 text-<?= $loyaltyData['loyalty_level'] ?>"></i>
                                <?= $loyaltyData['loyalty_level_name'] ?> уровень
                            </div>
                            <span class="badge bg-success"><?= $loyaltyData['discount'] ?>%</span>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i>
                                Подписка на 3 месяца
                            </div>
                            <span class="badge bg-success">10%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i>
                                Подписка на 6 месяцев
                            </div>
                            <span class="badge bg-success">15%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i>
                                Подписка на 12 месяцев
                            </div>
                            <span class="badge bg-success">20%</span>
                        </li>
                    </ul>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i> Скидки суммируются, но не могут превышать 40%
                    </div>
                </div>
            </div>

            <!-- Сохраненные кастомные планы -->
            <?php if (!empty($userCustomPlans)): ?>
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-save me-2"></i> Ваши сохраненные планы</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($userCustomPlans as $index => $plan): ?>
                            <?php if ($index < 5): // Показываем только последние 5 планов ?>
                            <a href="#" class="list-group-item list-group-item-action load-custom-plan" data-plan='<?= htmlspecialchars(json_encode($plan)) ?>'>
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($plan['name']) ?></h5>
                                    <small><?= formatPrice($plan['price']) ?></small>
                                </div>
                                <p class="mb-1"><?= $plan['duration'] ?> дней, <?= count($plan['features']) ?> функций</p>
                                <small class="text-muted">Создан: <?= formatDate($plan['created_at']) ?></small>
                            </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($userCustomPlans) > 5): ?>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#allCustomPlansModal">
                            Показать все планы
                        </button>
                    </div>

                    <!-- Модальное окно со всеми планами -->
                    <div class="modal fade" id="allCustomPlansModal" tabindex="-1" aria-labelledby="allCustomPlansModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="allCustomPlansModalLabel">Все сохраненные планы</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="list-group">
                                        <?php foreach ($userCustomPlans as $plan): ?>
                                        <a href="#" class="list-group-item list-group-item-action load-custom-plan" data-bs-dismiss="modal" data-plan='<?= htmlspecialchars(json_encode($plan)) ?>'>
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= htmlspecialchars($plan['name']) ?></h5>
                                                <small><?= formatPrice($plan['price']) ?></small>
                                            </div>
                                            <p class="mb-1"><?= $plan['duration'] ?> дней, <?= count($plan['features']) ?> функций</p>
                                            <small class="text-muted">Создан: <?= formatDate($plan['created_at']) ?></small>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция для предварительного выбора функций в соответствии с базовым планом
    function updateFeaturesBasedOnPlan() {
        const basePlan = document.getElementById('base_plan').value;
        const featureCheckboxes = document.querySelectorAll('.feature-checkbox');

        // Получаем функции выбранного плана
        const planFeatures = <?= json_encode(array_map(function($plan) {
            return $plan['features'];
        }, $standardPlans)) ?>;

        // Сначала снимаем все выделения
        featureCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        // Затем отмечаем функции, включенные в выбранный план
        featureCheckboxes.forEach(checkbox => {
            if (planFeatures[basePlan].includes(checkbox.value)) {
                checkbox.checked = true;
            }
        });
    }

    // Вызываем при загрузке страницы
    updateFeaturesBasedOnPlan();

    // Добавляем обработчик события изменения выбранного плана
    document.getElementById('base_plan').addEventListener('change', updateFeaturesBasedOnPlan);

    // Загрузка сохраненного плана
    const loadCustomPlanButtons = document.querySelectorAll('.load-custom-plan');
    loadCustomPlanButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const planData = JSON.parse(this.getAttribute('data-plan'));

            // Заполняем форму данными из сохраненного плана
            document.getElementById('base_plan').value = planData.base_plan;
            document.getElementById('duration').value = planData.duration;

            // Снимаем все отметки с чекбоксов
            document.querySelectorAll('.feature-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Отмечаем функции из сохраненного плана
            document.querySelectorAll('.feature-checkbox').forEach(checkbox => {
                if (planData.features.includes(checkbox.value)) {
                    checkbox.checked = true;
                }
            });

            // Прокручиваем страницу к форме
            document.getElementById('custom-plan-form').scrollIntoView({ behavior: 'smooth' });
        });
    });
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
