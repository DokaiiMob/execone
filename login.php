<?php
$pageTitle = 'Вход - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Если пользователь уже авторизован, перенаправляем на главную
$user = new User();
if ($user->isLoggedIn()) {
    redirect('/');
}

// Инициализация переменных для формы
$usernameOrEmail = '';
$rememberMe = false;
$error = '';

// Обработка отправки формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = $_POST['username_email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    // Валидация входных данных
    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        // Попытка входа
        $result = $user->login($usernameOrEmail, $password);

        if ($result['success']) {
            // Если стоит галочка "Запомнить меня", устанавливаем более долгий срок сессии
            if ($rememberMe) {
                ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30); // 30 дней
            }

            // Перенаправляем на главную страницу
            redirect('/');
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Вход в личный кабинет</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="username_email" class="form-label">Логин или Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input
                                    type="text"
                                    class="form-control <?= !empty($error) && empty($usernameOrEmail) ? 'is-invalid' : '' ?>"
                                    id="username_email"
                                    name="username_email"
                                    placeholder="Введите ваш логин или email"
                                    value="<?= htmlspecialchars($usernameOrEmail) ?>"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control <?= !empty($error) && empty($password) ? 'is-invalid' : '' ?>"
                                    id="password"
                                    name="password"
                                    placeholder="Введите ваш пароль"
                                    required
                                >
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember_me"
                                name="remember_me"
                                <?= $rememberMe ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="remember_me">Запомнить меня</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Войти
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Еще нет аккаунта? <a href="/register.php">Зарегистрироваться</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
