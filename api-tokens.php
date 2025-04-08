<?php
$pageTitle = 'API Токены - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Проверяем авторизацию
requireLogin();

$userModel = new User();
$apiTokenModel = new ApiToken();

$currentUser = $userModel->getCurrentUser();
$userId = $currentUser['id'];

// Инициализация переменных для формы
$error = '';
$success = '';
$newToken = null;

// Обработка формы создания токена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_token'])) {
        $description = $_POST['description'] ?? 'Токен для API';
        $expires = $_POST['expires'] ?? '+30 days';

        // Создаем токен
        $tokenResult = $apiTokenModel->generateToken($userId, $description, '*', $expires);

        if ($tokenResult['success']) {
            $success = 'Токен успешно создан.';
            $newToken = $tokenResult['token'];
        } else {
            $error = 'Ошибка при создании токена: ' . $tokenResult['message'];
        }
    } elseif (isset($_POST['deactivate_token']) && isset($_POST['token_id'])) {
        $tokenId = $_POST['token_id'];

        // Деактивируем токен
        $result = $apiTokenModel->updateToken($tokenId, ['active' => 0], $userId);

        if ($result) {
            $success = 'Токен успешно деактивирован.';
        } else {
            $error = 'Ошибка при деактивации токена.';
        }
    } elseif (isset($_POST['delete_token']) && isset($_POST['token_id'])) {
        $tokenId = $_POST['token_id'];

        // Удаляем токен
        $result = $apiTokenModel->deleteToken($tokenId, $userId);

        if ($result) {
            $success = 'Токен успешно удален.';
        } else {
            $error = 'Ошибка при удалении токена.';
        }
    }
}

// Получаем список токенов пользователя
$tokens = $apiTokenModel->getUserTokens($userId);

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

    <?php if ($newToken): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h5 class="alert-heading"><i class="fas fa-key me-1"></i> Новый токен создан!</h5>
        <p class="mb-0">Сохраните этот токен, так как он больше не будет показан:</p>
        <div class="input-group mt-2">
            <input type="text" class="form-control" value="<?= htmlspecialchars($newToken) ?>" id="new-token" readonly>
            <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#new-token">
                <i class="fas fa-copy"></i> Копировать
            </button>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>API Токены</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTokenModal">
                    <i class="fas fa-plus-circle me-1"></i> Создать новый токен
                </button>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Ваши API токены</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tokens)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> У вас пока нет API токенов. Создайте новый токен для интеграции с другими сервисами.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Описание</th>
                                    <th>Статус</th>
                                    <th>Последнее использование</th>
                                    <th>Срок действия</th>
                                    <th>Создан</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tokens as $token): ?>
                                <tr>
                                    <td><?= htmlspecialchars($token['description']) ?></td>
                                    <td>
                                        <?php if ($token['active']): ?>
                                        <span class="badge bg-success">Активен</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Неактивен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($token['last_used_at']): ?>
                                        <?= formatDate($token['last_used_at']) ?>
                                        <?php else: ?>
                                        <span class="text-muted">Не использовался</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($token['expires_at']): ?>
                                        <?= formatDate($token['expires_at']) ?>
                                        <?php else: ?>
                                        <span class="text-success">Бессрочный</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($token['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($token['active']): ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                                <button type="submit" name="deactivate_token" class="btn btn-warning" title="Деактивировать" onclick="return confirm('Вы уверены, что хотите деактивировать этот токен?');">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="token_id" value="<?= $token['id'] ?>">
                                                <button type="submit" name="delete_token" class="btn btn-danger" title="Удалить" onclick="return confirm('Вы уверены, что хотите удалить этот токен? Это действие нельзя отменить.');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Документация по API</h5>
                </div>
                <div class="card-body">
                    <p>Наш API позволяет вам интегрировать функциональность нашего сервиса с другими приложениями.</p>

                    <h5>Базовый URL</h5>
                    <div class="bg-light p-2 mb-3 rounded">
                        <code><?= $config['site']['url'] ?>/api</code>
                    </div>

                    <h5>Авторизация</h5>
                    <p>Для авторизации используйте Bearer токен в заголовке запроса:</p>
                    <div class="bg-light p-2 mb-3 rounded">
                        <code>Authorization: Bearer YOUR_API_TOKEN</code>
                    </div>

                    <h5>Доступные эндпоинты</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Эндпоинт</th>
                                    <th>Метод</th>
                                    <th>Описание</th>
                                    <th>Авторизация</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>/api/auth/login</code></td>
                                    <td>POST</td>
                                    <td>Получение токена авторизации</td>
                                    <td>Не требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/auth/logout</code></td>
                                    <td>POST</td>
                                    <td>Деактивация токена</td>
                                    <td>Требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/user/info</code></td>
                                    <td>GET</td>
                                    <td>Информация о пользователе</td>
                                    <td>Требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/user/subscription</code></td>
                                    <td>GET</td>
                                    <td>Информация о подписке</td>
                                    <td>Требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/user/referrals</code></td>
                                    <td>GET</td>
                                    <td>Информация о рефералах</td>
                                    <td>Требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/subscription/plans</code></td>
                                    <td>GET</td>
                                    <td>Доступные планы подписки</td>
                                    <td>Не требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/cheat/versions</code></td>
                                    <td>GET</td>
                                    <td>Список версий чита</td>
                                    <td>Не требуется</td>
                                </tr>
                                <tr>
                                    <td><code>/api/cheat/download</code></td>
                                    <td>GET</td>
                                    <td>Ссылка на скачивание чита</td>
                                    <td>Требуется</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p>Более подробную документацию можно найти в нашем <a href="#" class="text-primary">Руководстве по API</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания токена -->
<div class="modal fade" id="createTokenModal" tabindex="-1" aria-labelledby="createTokenModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTokenModalLabel">Создание нового API токена</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание токена</label>
                        <input type="text" class="form-control" id="description" name="description" placeholder="Например: Интеграция с Discord" required>
                        <div class="form-text">Укажите назначение токена для удобства управления</div>
                    </div>

                    <div class="mb-3">
                        <label for="expires" class="form-label">Срок действия</label>
                        <select class="form-select" id="expires" name="expires">
                            <option value="+30 days">30 дней</option>
                            <option value="+90 days">90 дней</option>
                            <option value="+180 days">180 дней</option>
                            <option value="+1 year">1 год</option>
                            <option value="">Бессрочно</option>
                        </select>
                        <div class="form-text">По истечении срока действия токен будет автоматически деактивирован</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="create_token" class="btn btn-primary">Создать токен</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка копирования токена
    document.querySelectorAll('.copy-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = document.querySelector(this.dataset.clipboardTarget);
            input.select();
            document.execCommand('copy');

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Скопировано!';

            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
