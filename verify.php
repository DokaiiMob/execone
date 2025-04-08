<?php
$pageTitle = 'Подтверждение email - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Инициализация переменных
$success = false;
$message = '';

// Проверяем наличие токена
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Неверная ссылка для подтверждения email';
} else {
    // Проверяем токен и активируем аккаунт
    $userModel = new User();
    $result = $userModel->verifyEmail($token);

    if ($result['success']) {
        $success = true;
        $message = 'Ваш email успешно подтвержден! Теперь вы можете войти в свой аккаунт.';
    } else {
        $message = $result['message'];
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Подтверждение email</h4>
                </div>
                <div class="card-body text-center">
                    <?php if ($success): ?>
                    <div class="my-4">
                        <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                        <h3>Email подтвержден</h3>
                        <p class="lead"><?= htmlspecialchars($message) ?></p>
                    </div>
                    <a href="/login.php" class="btn btn-primary btn-lg">Войти в аккаунт</a>
                    <?php else: ?>
                    <div class="my-4">
                        <i class="fas fa-times-circle text-danger fa-5x mb-3"></i>
                        <h3>Ошибка подтверждения</h3>
                        <p class="lead"><?= htmlspecialchars($message) ?></p>
                    </div>
                    <a href="/login.php" class="btn btn-primary">Войти в аккаунт</a>
                    <a href="/register.php" class="btn btn-outline-secondary ms-2">Зарегистрироваться</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
