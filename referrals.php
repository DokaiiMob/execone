<?php
$pageTitle = 'Реферальная программа - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$referralModel = new Referral();

$currentUser = $userModel->getCurrentUser();
$userId = $currentUser['id'];

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Генерация основного реферального кода
    if (isset($_POST['generate_code'])) {
        $result = $referralModel->generateReferralCode($userId);

        if ($result['success']) {
            displaySuccess('Реферальный код успешно создан');
        } else {
            displayError($result['message']);
        }
    }
    // Генерация дополнительного реферального кода
    elseif (isset($_POST['create_additional_code'])) {
        $description = trim($_POST['code_description'] ?? '');
        $result = $referralModel->createAdditionalReferralCode($userId, $description);

        if ($result['success']) {
            displaySuccess('Дополнительный реферальный код успешно создан');
        } else {
            displayError($result['message']);
        }
    }

    // Перенаправляем на страницу рефералов
    header('Location: /referrals.php');
    exit;
}

// Получаем все реферальные коды пользователя
$referralCodes = $referralModel->getUserReferralCodes($userId);
// Для обратной совместимости получаем основной код
$primaryReferralCode = $referralModel->getUserReferralCode($userId);

// Получаем детальную статистику рефералов
$referralStats = $referralModel->getDetailedReferralStats($userId);
$basicStats = $referralStats['basic'];

// Получаем список рефералов
$referrals = $referralModel->getUserReferrals($userId);

// Получаем информацию о реферере пользователя (если есть)
$referrer = $referralModel->getUserReferrer($userId);

// Генерируем реферальную ссылку для основного кода
$referralLink = '';
if ($primaryReferralCode) {
    $referralLink = $config['site']['url'] . '/register.php?ref=' . $primaryReferralCode;
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Реферальная программа</h1>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <h4 class="mb-3">Как это работает?</h4>
                            <p>Приглашайте друзей и получайте бонусные дни к своей подписке! Чем больше друзей вы пригласите, тем больше бонусных дней получите.</p>
                            <ol>
                                <li><strong>Получите свой реферальный код</strong> - сгенерируйте уникальный код и поделитесь им с друзьями</li>
                                <li><strong>Пригласите друзей</strong> - когда ваш друг регистрируется с вашим кодом, он становится вашим рефералом</li>
                                <li><strong>Получайте бонусы</strong> - когда ваш реферал покупает подписку, вы получаете бонусные дни:</li>
                                <ul>
                                    <li>Базовая подписка: <strong>30%</strong> от длительности в бонусных днях</li>
                                    <li>Премиум подписка: <strong>40%</strong> от длительности в бонусных днях</li>
                                    <li>VIP подписка: <strong>50%</strong> от длительности в бонусных днях</li>
                                </ul>
                                <li><strong>Используйте бонусы</strong> - бонусные дни можно использовать при продлении своей подписки</li>
                            </ol>
                        </div>
                        <div class="col-lg-4">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-gift text-primary me-2"></i> Ваш бонусный баланс</h5>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Бонусных дней:</span>
                                        <span class="fs-3 fw-bold text-primary"><?= isset($referralStats['current_bonus_days']) ? $referralStats['current_bonus_days'] : 0 ?></span>
                                    </div>
                                    <a href="/subscription.php" class="btn btn-primary w-100"><i class="fas fa-plus-circle me-1"></i> Использовать бонусы</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Реферальный код и ссылка -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> Ваша реферальная ссылка</h5>
                </div>
                <div class="card-body">
                    <?php if ($primaryReferralCode): ?>
                        <div class="row">
                            <div class="col-lg-4 mb-3 mb-lg-0">
                                <div class="d-flex align-items-center">
                                    <span class="fs-5 me-3">Ваш код:</span>
                                    <div class="bg-light p-2 rounded">
                                        <span class="fs-4 fw-bold text-primary"><?= $primaryReferralCode ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="referral-link" value="<?= $referralLink ?>" readonly>
                                    <button class="btn btn-primary copy-to-clipboard" data-copy="<?= $referralLink ?>" type="button"><i class="fas fa-copy me-1"></i> Копировать</button>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary share-link" data-share="vk"><i class="fab fa-vk me-1"></i> ВКонтакте</button>
                                    <button class="btn btn-sm btn-outline-primary share-link" data-share="telegram"><i class="fab fa-telegram me-1"></i> Telegram</button>
                                    <button class="btn btn-sm btn-outline-primary share-link" data-share="discord"><i class="fab fa-discord me-1"></i> Discord</button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>У вас пока нет реферального кода. Создайте его, чтобы начать приглашать друзей.</p>
                        <form action="" method="post">
                            <button type="submit" name="generate_code" class="btn btn-primary"><i class="fas fa-magic me-1"></i> Создать реферальный код</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Дополнительные реферальные коды -->
            <?php if (!empty($referralCodes)): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i> Ваши реферальные коды</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Код</th>
                                    <th>Описание</th>
                                    <th>Создан</th>
                                    <th>Регистраций</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referralCodes as $code): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold"><?= $code['code'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($code['description'] ?: 'Основной код') ?></td>
                                    <td><?= formatDate($code['created_at']) ?></td>
                                    <td><?= $code['referrals_count'] ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary copy-to-clipboard" data-copy="<?= $config['site']['url'] ?>/register.php?ref=<?= $code['code'] ?>" title="Копировать ссылку">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button class="btn btn-outline-primary share-ref-code" data-code="<?= $code['code'] ?>" title="Поделиться">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Форма создания дополнительного кода -->
                    <div class="mt-3">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newCodeForm" aria-expanded="false">
                            <i class="fas fa-plus-circle me-1"></i> Создать новый реферальный код
                        </button>

                        <div class="collapse mt-3" id="newCodeForm">
                            <div class="card card-body bg-light">
                                <form action="" method="post">
                                    <div class="mb-3">
                                        <label for="code_description" class="form-label">Описание кода (необязательно)</label>
                                        <input type="text" class="form-control" id="code_description" name="code_description" placeholder="Например: Для форума, Для Discord...">
                                        <div class="form-text">Описание поможет вам отслеживать, откуда приходят рефералы</div>
                                    </div>
                                    <button type="submit" name="create_additional_code" class="btn btn-success">Создать код</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Статистика рефералов -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="display-1 text-primary mb-2"><i class="fas fa-users"></i></div>
                            <h3 class="fs-5 fw-normal">Всего рефералов</h3>
                            <div class="display-5 fw-bold"><?= $basicStats['total_referrals'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="display-1 text-success mb-2"><i class="fas fa-user-check"></i></div>
                            <h3 class="fs-5 fw-normal">Активных рефералов</h3>
                            <div class="display-5 fw-bold"><?= $basicStats['active_referrals'] ?></div>
                            <div class="small text-muted">Конверсия: <?= $referralStats['conversion_rate'] ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="display-1 text-warning mb-2"><i class="fas fa-calendar-day"></i></div>
                            <h3 class="fs-5 fw-normal">Заработано бонусных дней</h3>
                            <div class="display-5 fw-bold"><?= $basicStats['total_bonus_days'] ?></div>
                            <?php if ($basicStats['active_referrals'] > 0): ?>
                            <div class="small text-muted">В среднем: <?= $referralStats['average_bonus_per_referral'] ?> дней/реф</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="display-1 text-info mb-2"><i class="fas fa-calendar-check"></i></div>
                            <h3 class="fs-5 fw-normal">Использовано бонусных дней</h3>
                            <div class="display-5 fw-bold"><?= $basicStats['used_bonus_days'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Детальная статистика рефералов -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Подробная статистика</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="referralStatsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="true">По месяцам</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="plans-tab" data-bs-toggle="tab" data-bs-target="#plans" type="button" role="tab" aria-controls="plans" aria-selected="false">По планам</button>
                        </li>
                    </ul>

                    <div class="tab-content p-3" id="referralStatsContent">
                        <!-- Ежемесячная статистика -->
                        <div class="tab-pane fade show active" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                            <?php if (empty($referralStats['monthly'])): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> У вас пока нет данных для отображения ежемесячной статистики.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Месяц</th>
                                                <th>Всего регистраций</th>
                                                <th>Активных рефералов</th>
                                                <th>Заработано бонусов</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($referralStats['monthly'] as $month => $data): ?>
                                                <tr>
                                                    <td><?= $month ?></td>
                                                    <td><?= $data['total_signups'] ?></td>
                                                    <td><?= $data['active_signups'] ?></td>
                                                    <td><?= $data['earned_bonus'] ?> дней</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Статистика по планам -->
                        <div class="tab-pane fade" id="plans" role="tabpanel" aria-labelledby="plans-tab">
                            <?php if (empty($referralStats['subscription_plans'])): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> У вас пока нет данных для отображения статистики по планам подписки.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php
                                    $plans = ['basic' => 'Базовый', 'premium' => 'Премиум', 'vip' => 'VIP'];
                                    $colors = ['basic' => 'primary', 'premium' => 'success', 'vip' => 'warning'];

                                    foreach ($plans as $planType => $planName):
                                        $planData = $referralStats['subscription_plans'][$planType] ?? null;
                                        $count = $planData ? $planData['count'] : 0;
                                        $bonus = $planData ? $planData['total_bonus'] : 0;
                                    ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header bg-<?= $colors[$planType] ?> text-white">
                                                <h5 class="mb-0"><?= $planName ?></h5>
                                            </div>
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <span class="display-4 fw-bold"><?= $count ?></span>
                                                    <div class="text-muted">активных рефералов</div>
                                                </div>
                                                <div>
                                                    <span class="fs-4 fw-bold"><?= $bonus ?></span>
                                                    <div class="text-muted">бонусных дней</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Список рефералов -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Ваши рефералы</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($referrals)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> У вас пока нет рефералов. Поделитесь своей реферальной ссылкой с друзьями, чтобы начать получать бонусы.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Пользователь</th>
                                        <th>Дата регистрации</th>
                                        <th>Статус</th>
                                        <th>Бонус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrals as $referral): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($referral['avatar'])): ?>
                                                        <img src="<?= getUserAvatarUrl($referral['avatar']) ?>" alt="Аватар" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="avatar-placeholder me-2 d-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 32px; height: 32px;">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($referral['username']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($referral['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= formatDate($referral['created_at']) ?></td>
                                            <td>
                                                <?php if ($referral['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Активен</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Ожидает покупки</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($referral['status'] === 'active'): ?>
                                                    <span class="fw-bold text-success">+<?= $referral['bonus_earned'] ?> дней</span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Информация о реферере -->
            <?php if ($referrer): ?>
                <div class="card mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Вы присоединились по приглашению</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-3x text-secondary"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-5"><?= htmlspecialchars($referrer['username']) ?></div>
                                <p class="mb-0 text-muted">Дата регистрации: <?= formatDate($referrer['created_at']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Копирование реферальной ссылки
    const copyButtons = document.querySelectorAll('.copy-to-clipboard');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Показываем уведомление
                const originalText = this.innerHTML;
                const originalTitle = this.getAttribute('title');

                this.innerHTML = '<i class="fas fa-check"></i>';
                this.setAttribute('title', 'Скопировано!');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.setAttribute('title', originalTitle);
                }, 2000);
            }).catch(err => {
                // Запасной метод для копирования
                const textarea = document.createElement('textarea');
                textarea.value = textToCopy;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                // Показываем уведомление
                const originalText = this.innerHTML;
                const originalTitle = this.getAttribute('title');

                this.innerHTML = '<i class="fas fa-check"></i>';
                this.setAttribute('title', 'Скопировано!');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.setAttribute('title', originalTitle);
                }, 2000);
            });
        });
    });

    // Поделиться реферальной ссылкой
    const shareButtons = document.querySelectorAll('.share-link, .share-ref-code');
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            let referralCode;
            let shareUrl;

            if (this.classList.contains('share-link')) {
                // Для кнопок 'share-link' берем ссылку из поля input
                const referralLink = document.getElementById('referral-link').value;
                const shareType = this.getAttribute('data-share');
                const shareText = 'Присоединяйтесь к лучшему читу для SAMP! Зарегистрируйтесь по моей реферальной ссылке и получите бонусы:';

                switch (shareType) {
                    case 'vk':
                        shareUrl = `https://vk.com/share.php?url=${encodeURIComponent(referralLink)}&title=${encodeURIComponent(shareText)}`;
                        break;
                    case 'telegram':
                        shareUrl = `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(shareText)}`;
                        break;
                    case 'discord':
                        // Для Discord просто копируем текст со ссылкой
                        const discordText = `${shareText}\n${referralLink}`;
                        navigator.clipboard.writeText(discordText).catch(err => {
                            const textarea = document.createElement('textarea');
                            textarea.value = discordText;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                        });

                        alert('Текст с реферальной ссылкой скопирован в буфер обмена. Вставьте его в Discord.');
                        return;
                }
            } else if (this.classList.contains('share-ref-code')) {
                // Для кнопок 'share-ref-code' берем код из атрибута data-code
                referralCode = this.getAttribute('data-code');
                const referralLink = '<?= $config['site']['url'] ?>/register.php?ref=' + referralCode;
                const shareText = 'Присоединяйтесь к лучшему читу для SAMP! Зарегистрируйтесь по моей реферальной ссылке и получите бонусы:';

                // Показываем модальное окно с вариантами шаринга
                const shareModal = new bootstrap.Modal(document.getElementById('shareModal') || createShareModal());

                // Устанавливаем данные для шаринга в модальном окне
                document.querySelectorAll('.share-modal-btn').forEach(btn => {
                    const type = btn.getAttribute('data-type');

                    if (type === 'copy') {
                        btn.onclick = function() {
                            navigator.clipboard.writeText(referralLink).catch(err => {
                                const textarea = document.createElement('textarea');
                                textarea.value = referralLink;
                                document.body.appendChild(textarea);
                                textarea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textarea);
                            });

                            const originalText = this.innerHTML;
                            this.innerHTML = '<i class="fas fa-check"></i> Скопировано!';

                            setTimeout(() => {
                                this.innerHTML = originalText;
                            }, 2000);
                        };
                    } else {
                        switch (type) {
                            case 'vk':
                                btn.href = `https://vk.com/share.php?url=${encodeURIComponent(referralLink)}&title=${encodeURIComponent(shareText)}`;
                                break;
                            case 'telegram':
                                btn.href = `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(shareText)}`;
                                break;
                            case 'discord':
                                btn.onclick = function() {
                                    const discordText = `${shareText}\n${referralLink}`;
                                    navigator.clipboard.writeText(discordText).catch(err => {
                                        const textarea = document.createElement('textarea');
                                        textarea.value = discordText;
                                        document.body.appendChild(textarea);
                                        textarea.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(textarea);
                                    });

                                    alert('Текст с реферальной ссылкой скопирован в буфер обмена. Вставьте его в Discord.');
                                };
                                break;
                        }
                    }
                });

                document.getElementById('shareModalReferralLink').value = referralLink;

                shareModal.show();
                return;
            }

            // Открываем окно для шаринга
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        });
    });

    // Функция для создания модального окна шаринга, если его нет в DOM
    function createShareModal() {
        const modalHTML = `
        <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shareModalLabel">Поделиться реферальной ссылкой</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="shareModalReferralLink" class="form-label">Ваша реферальная ссылка:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="shareModalReferralLink" readonly>
                                <button class="btn btn-outline-primary share-modal-btn" data-type="copy"><i class="fas fa-copy"></i> Копировать</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="btn btn-outline-primary mx-1 share-modal-btn" data-type="vk" target="_blank">
                                <i class="fab fa-vk"></i> ВКонтакте
                            </a>
                            <a href="#" class="btn btn-outline-primary mx-1 share-modal-btn" data-type="telegram" target="_blank">
                                <i class="fab fa-telegram"></i> Telegram
                            </a>
                            <button class="btn btn-outline-primary mx-1 share-modal-btn" data-type="discord">
                                <i class="fab fa-discord"></i> Discord
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

        const modalElement = document.createRange().createContextualFragment(modalHTML).firstElementChild;
        document.body.appendChild(modalElement);

        return new bootstrap.Modal(modalElement);
    }
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
