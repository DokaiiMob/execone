<?php
$pageTitle = 'Управление платежами - Админ-панель';
require_once __DIR__ . '/../config/init.php';

// Проверяем права администратора
requireAdmin();

$userModel = new User();
$subscriptionModel = new Subscription();

// Параметры пагинации
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Обработка действий
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление нового платежа
    if (isset($_POST['add_payment'])) {
        $userId = (int)$_POST['user_id'];
        $amount = (float)$_POST['amount'];
        $paymentMethod = $_POST['payment_method'];
        $status = $_POST['status'];
        $transactionId = $_POST['transaction_id'] ?? null;

        // Проверяем существование пользователя
        $user = $userModel->getUserById($userId);
        if (!$user) {
            $message = 'Пользователь не найден';
            $messageType = 'danger';
        } elseif ($amount <= 0) {
            $message = 'Сумма платежа должна быть положительной';
            $messageType = 'danger';
        } else {
            // Создаем платеж
            $result = $subscriptionModel->createPayment(
                $userId,
                $amount,
                $paymentMethod,
                $status,
                $transactionId
            );

            if ($result['success']) {
                $message = 'Платеж успешно добавлен';
                $messageType = 'success';
            } else {
                $message = $result['message'] ?? 'Ошибка при создании платежа';
                $messageType = 'danger';
            }
        }
    }
}

// Получаем список платежей
$payments = $subscriptionModel->getAllPayments($perPage, $offset);
$totalPayments = count($subscriptionModel->getAllPayments(1000000, 0));

// Параметры пагинации
$totalPages = ceil($totalPayments / $perPage);

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
                        <a href="/admin/payments.php" class="nav-link active">
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
            <h2 class="mb-4">Управление платежами</h2>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Панель действий -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="fas fa-plus"></i> Добавить платеж
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Таблица платежей -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                    <div class="alert alert-info mb-0">
                        Платежи не найдены.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Сумма</th>
                                    <th>Способ оплаты</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                    <th>ID транзакции</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td>
                                        <a href="users.php?action=edit&id=<?= $payment['user_id'] ?>">
                                            <?= htmlspecialchars($payment['username']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($payment['email']) ?></small>
                                    </td>
                                    <td><?= formatPrice($payment['amount']) ?></td>
                                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                    <td>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Выполнен</span>
                                        <?php elseif ($payment['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">В обработке</span>
                                        <?php elseif ($payment['status'] === 'failed'): ?>
                                        <span class="badge bg-danger">Ошибка</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($payment['payment_date']) ?></td>
                                    <td>
                                        <?php if (!empty($payment['transaction_id'])): ?>
                                        <code><?= htmlspecialchars($payment['transaction_id']) ?></code>
                                        <?php else: ?>
                                        <small class="text-muted">Нет данных</small>
                                        <?php endif; ?>
                                    </td>
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

<!-- Модальное окно добавления платежа -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">Добавить платеж</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">ID пользователя</label>
                        <input type="number" class="form-control" id="user_id" name="user_id" required>
                        <small class="text-muted">Введите ID пользователя из таблицы пользователей</small>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Сумма (руб.)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Способ оплаты</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="card">Банковская карта</option>
                            <option value="qiwi">QIWI Кошелек</option>
                            <option value="yoomoney">ЮMoney</option>
                            <option value="crypto">Криптовалюта</option>
                            <option value="admin">Добавлено администратором</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Статус</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="completed">Выполнен</option>
                            <option value="pending">В обработке</option>
                            <option value="failed">Ошибка</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_id" class="form-label">ID транзакции (необязательно)</label>
                        <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_payment" class="btn btn-primary">Добавить платеж</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
