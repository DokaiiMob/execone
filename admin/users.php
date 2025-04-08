<?php
$pageTitle = 'Управление пользователями - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$userModel = new User();
$subscriptionModel = new Subscription();

$action = $_GET['action'] ?? 'list';
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление нового пользователя
    if (isset($_POST['add_user'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? 'user');

        if (empty($username) || empty($email) || empty($password)) {
            displayError('Все поля обязательны для заполнения');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            displayError('Указан неверный формат email');
        } else {
            $result = $userModel->register($username, $email, $password);

            if ($result['success']) {
                // Если выбрана роль администратора, устанавливаем её
                if ($role === 'admin') {
                    $userModel->changeUserRole($result['user_id'], 'admin');
                }

                displaySuccess('Пользователь успешно добавлен');
                header("Location: users.php");
                exit;
            } else {
                displayError($result['message']);
            }
        }
    }
    // Редактирование пользователя
    elseif (isset($_POST['edit_user']) && $userId > 0) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'user');
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($username) || empty($email)) {
            displayError('Поля Логин и Email обязательны для заполнения');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            displayError('Указан неверный формат email');
        } else {
            // Обновляем основные данные
            $data = [
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];

            $result = $userModel->updateProfile($userId, $data);

            if ($result['success']) {
                // Если указан новый пароль, обновляем его
                if (!empty($newPassword)) {
                    // Обходим проверку текущего пароля
                    $db = Database::getInstance();
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
                }

                displaySuccess('Данные пользователя успешно обновлены');
                header("Location: users.php");
                exit;
            } else {
                displayError($result['message']);
            }
        }
    }
    // Удаление пользователя
    elseif (isset($_POST['delete_user']) && $userId > 0) {
        $db = Database::getInstance();

        // Проверяем, не удаляем ли мы текущего администратора
        if ($userId == $_SESSION['user_id']) {
            displayError('Вы не можете удалить свой аккаунт');
        } else {
            // Удаляем записи из всех связанных таблиц
            $db->delete('download_logs', 'user_id = ?', [$userId]);
            $db->delete('subscriptions', 'user_id = ?', [$userId]);
            $db->delete('payments', 'user_id = ?', [$userId]);
            $db->delete('users', 'id = ?', [$userId]);

            displaySuccess('Пользователь успешно удален');
            header("Location: users.php");
            exit;
        }
    }
}

// Получаем данные в зависимости от действия
$user = null;
if ($action === 'edit' || $action === 'delete') {
    $user = $userModel->getUserById($userId);
    if (!$user) {
        displayError('Пользователь не найден');
        header("Location: users.php");
        exit;
    }
}

// Получаем список пользователей для отображения
$users = [];
if ($action === 'list') {
    $users = $userModel->getAllUsers();
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
                        <a href="/admin/users.php" class="nav-link active">
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
            <?php if ($action === 'list'): ?>
                <!-- Список пользователей -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Управление пользователями</h2>
                    <a href="?action=add" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i> Добавить пользователя</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Логин</th>
                                        <th>Email</th>
                                        <th>Роль</th>
                                        <th>Email подтвержден</th>
                                        <th>Дата регистрации</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Нет данных для отображения</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                    <?= $user['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['email_verified']): ?>
                                                <span class="badge bg-success">Да</span>
                                                <?php else: ?>
                                                <span class="badge bg-warning">Нет</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDate($user['created_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-primary" title="Редактировать">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $user['id'] ?>" class="btn btn-danger" title="Удалить">
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
                <!-- Форма добавления пользователя -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Добавление пользователя</h2>
                    <a href="/admin/users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-3">
                                <label for="username" class="col-sm-2 col-form-label">Логин</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="email" class="col-sm-2 col-form-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="password" class="col-sm-2 col-form-label">Пароль</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="role" class="col-sm-2 col-form-label">Роль</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="role" name="role">
                                        <option value="user">Пользователь</option>
                                        <option value="admin">Администратор</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" name="add_user" class="btn btn-primary">Добавить пользователя</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'edit' && $user): ?>
                <!-- Форма редактирования пользователя -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Редактирование пользователя</h2>
                    <a href="/admin/users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row mb-3">
                                <label for="username" class="col-sm-2 col-form-label">Логин</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="email" class="col-sm-2 col-form-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="new_password" class="col-sm-2 col-form-label">Новый пароль</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="text-muted">Оставьте пустым, если не хотите менять пароль</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="role" class="col-sm-2 col-form-label">Роль</label>
                                <div class="col-sm-10">
                                    <select class="form-select" id="role" name="role">
                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-10 offset-sm-2">
                                    <button type="submit" name="edit_user" class="btn btn-primary">Сохранить изменения</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Информация о подписках пользователя -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Подписки пользователя</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $subscriptions = $subscriptionModel->getUserSubscriptionHistory($user['id']);
                        if (empty($subscriptions)):
                        ?>
                        <p class="mb-0">У пользователя нет подписок</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>План</th>
                                        <th>Статус</th>
                                        <th>Дата начала</th>
                                        <th>Дата окончания</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $subscription): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $plans = $subscriptionModel->getPlans();
                                            $planType = $subscription['plan_type'];
                                            echo $plans[$planType]['name'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($subscription['status'] === 'active'): ?>
                                            <span class="badge bg-success">Активна</span>
                                            <?php elseif ($subscription['status'] === 'expired'): ?>
                                            <span class="badge bg-secondary">Истекла</span>
                                            <?php elseif ($subscription['status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">Отменена</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($subscription['start_date']) ?></td>
                                        <td><?= formatDate($subscription['end_date']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($action === 'delete' && $user): ?>
                <!-- Форма удаления пользователя -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Удаление пользователя</h2>
                    <a href="/admin/users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Вернуться к списку</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Подтвердите удаление</h5>
                            <p>Вы собираетесь удалить пользователя <strong><?= htmlspecialchars($user['username']) ?></strong> (<?= htmlspecialchars($user['email']) ?>).</p>
                            <p>Это действие нельзя отменить. Вместе с пользователем будут удалены все его данные, включая подписки, платежи и логи загрузок.</p>
                        </div>

                        <form action="" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.');">
                            <button type="submit" name="delete_user" class="btn btn-danger"><i class="fas fa-trash me-2"></i> Удалить пользователя</button>
                            <a href="/admin/users.php" class="btn btn-secondary ms-2">Отмена</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
