<?php
$pageTitle = 'Управление версиями чита - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$cheatVersionModel = new CheatVersion();
$subscriptionModel = new Subscription();

$action = $_GET['action'] ?? 'list';
$versionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление новой версии чита
    if (isset($_POST['add_version'])) {
        $version = sanitizeInput($_POST['version'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $requiredPlan = sanitizeInput($_POST['required_plan'] ?? 'basic');

        if (empty($version)) {
            displayError('Поле "Версия" обязательно для заполнения');
        } elseif (!isset($_FILES['cheat_file']) || $_FILES['cheat_file']['error'] !== 0) {
            displayError('Выберите файл чита для загрузки');
        } else {
            $result = $cheatVersionModel->addVersion($version, $description, $_FILES['cheat_file'], $requiredPlan);

            if ($result['success']) {
                displaySuccess('Версия чита успешно добавлена');
                header("Location: cheat-versions.php");
                exit;
            } else {
                displayError($result['message']);
            }
        }
    }
    // Обновление версии чита
    elseif (isset($_POST['edit_version']) && $versionId > 0) {
        $version = sanitizeInput($_POST['version'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $requiredPlan = sanitizeInput($_POST['required_plan'] ?? 'basic');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($version)) {
            displayError('Поле "Версия" обязательно для заполнения');
        } else {
            $data = [
                'version' => $version,
                'description' => $description,
                'required_plan' => $requiredPlan,
                'is_active' => $isActive
            ];

            $file = isset($_FILES['cheat_file']) && $_FILES['cheat_file']['error'] === 0 ? $_FILES['cheat_file'] : null;
            $result = $cheatVersionModel->updateVersion($versionId, $data, $file);

            if ($result['success']) {
                displaySuccess('Версия чита успешно обновлена');
                header("Location: cheat-versions.php");
                exit;
            } else {
                displayError($result['message']);
            }
        }
    }
    // Удаление версии чита
    elseif (isset($_POST['delete_version']) && $versionId > 0) {
        $result = $cheatVersionModel->deleteVersion($versionId);

        if ($result['success']) {
            displaySuccess('Версия чита успешно удалена');
            header("Location: cheat-versions.php");
            exit;
        } else {
            displayError($result['message']);
        }
    }
}

// Получаем данные в зависимости от действия
$version = null;
if ($action === 'edit' || $action === 'delete') {
    $version = $cheatVersionModel->getVersionById($versionId);
    if (!$version) {
        displayError('Версия чита не найдена');
        header("Location: cheat-versions.php");
        exit;
    }
}

// Получаем список версий для отображения
$versions = [];
if ($action === 'list') {
    $versions = $cheatVersionModel->getAllVersions(false);
}

// Получаем планы подписок
$plans = $subscriptionModel->getPlans();

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
                        <a href="/admin/cheat-versions.php" class="nav-link active">
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
            <?php if ($action === 'list'): ?>
                <!-- Список версий чита -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Управление версиями чита</h2>
                    <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Добавить версию</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Версия</th>
                                        <th>Требуемая подписка</th>
                                        <th>Статус</th>
                                        <th>Дата добавления</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($versions)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Нет данных для отображения</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($versions as $v): ?>
                                        <tr>
                                            <td><?= $v['id'] ?></td>
                                            <td><?= htmlspecialchars($v['version']) ?></td>
                                            <td>
                                                <?php
                                                $planType = $v['required_plan'];
                                                echo $plans[$planType]['name'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($v['is_active']): ?>
                                                <span class="badge bg-success">Активна</span>
                                                <?php else: ?>
                                                <span class="badge bg-secondary">Неактивна</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDate($v['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?= $v['id'] ?>" class="btn btn-primary" title="Редактировать">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $v['id'] ?>" class="btn btn-danger" title="Удалить">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($action === 'add'): ?>
                <!-- Форма добавления версии чита -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Добавление версии чита</h2>
                    <a href="/admin/cheat-versions.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <label for="version" class="col-sm-2 col-form-label">Версия</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="version" name="version" placeholder="Например: 1.0.0" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="description" class="col-sm-2 col-form-label">Описание</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Описание новой версии и список изменений"></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="required_plan" class="col-sm-2 col-form-label">Требуемая подписка</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="required_plan" name="required_plan">
                                        <?php foreach ($plans as $planType => $plan): ?>
                                        <option value="<?= $planType ?>"><?= $plan['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="cheat_file" class="col-sm-2 col-form-label">Файл чита</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" id="cheat_file" name="cheat_file" required>
                                    <small class="text-muted">Допустимые форматы: <?= implode(', ', $config['uploads']['cheat_files']['allowed_extensions']) ?>. Максимальный размер: <?= $config['uploads']['cheat_files']['max_size'] / 1024 / 1024 ?> MB.</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" name="add_version" class="btn btn-primary">Добавить версию</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'edit' && $version): ?>
                <!-- Форма редактирования версии чита -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Редактирование версии чита</h2>
                    <a href="/admin/cheat-versions.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <label for="version" class="col-sm-2 col-form-label">Версия</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="version" name="version" value="<?= htmlspecialchars($version['version']) ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="description" class="col-sm-2 col-form-label">Описание</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($version['description']) ?></textarea>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="required_plan" class="col-sm-2 col-form-label">Требуемая подписка</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="required_plan" name="required_plan">
                                        <?php foreach ($plans as $planType => $plan): ?>
                                        <option value="<?= $planType ?>" <?= $version['required_plan'] === $planType ? 'selected' : '' ?>><?= $plan['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="is_active" class="col-sm-2 col-form-label">Статус</label>
                                <div class="col-sm-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= $version['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Активна (доступна для скачивания)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="cheat_file" class="col-sm-2 col-form-label">Файл чита</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" id="cheat_file" name="cheat_file">
                                    <small class="text-muted">Допустимые форматы: <?= implode(', ', $config['uploads']['cheat_files']['allowed_extensions']) ?>. Максимальный размер: <?= $config['uploads']['cheat_files']['max_size'] / 1024 / 1024 ?> MB.</small>
                                    <?php if (!empty($version['file_path'])): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-info">Текущий файл: <?= $version['file_path'] ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" name="edit_version" class="btn btn-primary">Сохранить изменения</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Статистика скачиваний версии -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Статистика скачиваний</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $downloads = $cheatVersionModel->getAllDownloadLogs(1000, 0);
                        $versionDownloads = array_filter($downloads, function($download) use ($versionId) {
                            return $download['cheat_version_id'] == $versionId;
                        });

                        if (empty($versionDownloads)):
                        ?>
                        <p class="mb-0">Нет данных о скачиваниях этой версии</p>
                        <?php else: ?>
                        <p>Всего скачиваний: <strong><?= count($versionDownloads) ?></strong></p>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Пользователь</th>
                                        <th>Дата скачивания</th>
                                        <th>IP-адрес</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($versionDownloads, 0, 10) as $download): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($download['username']) ?></td>
                                        <td><?= formatDate($download['download_date']) ?></td>
                                        <td><?= $download['ip_address'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($versionDownloads) > 10): ?>
                        <p class="text-muted mt-2">Показаны последние 10 скачиваний. Всего скачиваний: <?= count($versionDownloads) ?></p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($action === 'delete' && $version): ?>
                <!-- Форма удаления версии чита -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Удаление версии чита</h2>
                    <a href="/admin/cheat-versions.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Подтвердите удаление</h5>
                            <p>Вы собираетесь удалить версию чита <strong><?= htmlspecialchars($version['version']) ?></strong>.</p>
                            <p>Это действие нельзя отменить. Вместе с версией будет удален файл чита и связанные логи скачиваний.</p>
                        </div>

                        <form action="" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту версию чита? Это действие нельзя отменить.');">
                            <button type="submit" name="delete_version" class="btn btn-danger"><i class="fas fa-trash me-2"></i> Удалить версию</button>
                            <a href="/admin/cheat-versions.php" class="btn btn-secondary ms-2">Отмена</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
