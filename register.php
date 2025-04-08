<?php
$pageTitle = 'Регистрация - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Если пользователь уже авторизован, перенаправляем на главную
$user = new User();
if ($user->isLoggedIn()) {
    redirect('/');
}

// Инициализация переменных для формы
$username = '';
$email = '';
$referralCode = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';
$error = '';

// Обработка отправки формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $referralCode = $_POST['referral_code'] ?? '';
    $terms = isset($_POST['terms']);

    // Валидация входных данных
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Указан неверный формат email';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен содержать не менее 8 символов';
    } elseif (!$terms) {
        $error = 'Для регистрации необходимо согласиться с правилами использования и политикой конфиденциальности';
    } else {
        // Попытка регистрации
        $result = $user->register($username, $email, $password);

        if ($result['success']) {
            // Обработка реферального кода
            if (!empty($referralCode)) {
                $referralModel = new Referral();
                $referrerId = $referralModel->getUserIdByCode($referralCode);

                if ($referrerId) {
                    // Добавляем реферальную связь
                    $referralModel->addReferral($referrerId, $result['user_id']);
                }
            }

            if ($result['require_verification']) {
                // Если требуется подтверждение email, сообщаем об этом пользователю
                displaySuccess('Регистрация успешна! Пожалуйста, подтвердите ваш email, перейдя по ссылке в письме.');
                // Перенаправляем на страницу входа
                redirect('/login.php');
            } else {
                // Если подтверждение не требуется, автоматически авторизуем пользователя
                $user->login($email, $password);
                redirect('/');
            }
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Регистрация</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Логин</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input
                                        type="text"
                                        class="form-control <?= !empty($error) && empty($username) ? 'is-invalid' : '' ?>"
                                        id="username"
                                        name="username"
                                        placeholder="Придумайте логин"
                                        value="<?= htmlspecialchars($username) ?>"
                                        required
                                    >
                                </div>
                                <small class="text-muted">Логин будет использоваться для входа на сайт</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input
                                        type="email"
                                        class="form-control <?= !empty($error) && empty($email) ? 'is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        placeholder="Введите ваш email"
                                        value="<?= htmlspecialchars($email) ?>"
                                        required
                                    >
                                </div>
                                <small class="text-muted">На указанный email будет отправлена ссылка для подтверждения</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input
                                        type="password"
                                        class="form-control <?= !empty($error) && empty($password) ? 'is-invalid' : '' ?>"
                                        id="password"
                                        name="password"
                                        placeholder="Придумайте пароль"
                                        required
                                    >
                                </div>
                                <small class="text-muted">Минимум 8 символов</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input
                                        type="password"
                                        class="form-control <?= !empty($error) && empty($password_confirm) ? 'is-invalid' : '' ?>"
                                        id="password_confirm"
                                        name="password_confirm"
                                        placeholder="Повторите пароль"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                        <!-- Поле для реферального кода -->
                        <div class="mb-3">
                            <label for="referral_code" class="form-label">Реферальный код (необязательно)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="referral_code"
                                    name="referral_code"
                                    placeholder="Если у вас есть реферальный код, введите его здесь"
                                    value="<?= htmlspecialchars($referralCode) ?>"
                                >
                            </div>
                            <small class="text-muted">Если вас пригласил друг, укажите его реферальный код для получения бонусов</small>
                        </div>
                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input <?= !empty($error) && !$terms ? 'is-invalid' : '' ?>"
                                id="terms"
                                name="terms"
                                required
                            >
                            <label class="form-check-label" for="terms">Я согласен с <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">правилами использования</a> и <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">политикой конфиденциальности</a></label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Зарегистрироваться
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Уже есть аккаунт? <a href="/login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с правилами использования -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Правила использования</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Общие положения</h5>
                <p>1.1. Настоящие Правила регулируют отношения между Администрацией сайта и Пользователями.</p>
                <p>1.2. Используя сайт, Пользователь соглашается соблюдать данные Правила.</p>

                <h5>2. Права и обязанности Пользователя</h5>
                <p>2.1. Пользователь обязуется не передавать свои учетные данные третьим лицам.</p>
                <p>2.2. Пользователь несет ответственность за все действия, совершенные под его учетной записью.</p>
                <p>2.3. Пользователь обязуется не использовать сайт для незаконной деятельности.</p>

                <h5>3. Ограничение ответственности</h5>
                <p>3.1. Администрация сайта не несет ответственности за любые потери или убытки, связанные с использованием сайта.</p>
                <p>3.2. Администрация сайта не гарантирует постоянную доступность сайта.</p>

                <h5>4. Заключительные положения</h5>
                <p>4.1. Администрация сайта имеет право изменять данные Правила без предварительного уведомления Пользователей.</p>
                <p>4.2. Актуальная версия Правил всегда доступна на сайте.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с политикой конфиденциальности -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Политика конфиденциальности</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Общие положения</h5>
                <p>1.1. Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных Пользователей.</p>
                <p>1.2. Используя сайт, Пользователь соглашается с условиями данной Политики.</p>

                <h5>2. Сбор и использование персональных данных</h5>
                <p>2.1. Администрация сайта собирает следующие персональные данные:</p>
                <ul>
                    <li>Имя пользователя</li>
                    <li>Адрес электронной почты</li>
                    <li>IP-адрес</li>
                    <li>Информация о браузере и устройстве</li>
                </ul>
                <p>2.2. Персональные данные используются для:</p>
                <ul>
                    <li>Предоставления доступа к сайту и его функциям</li>
                    <li>Улучшения работы сайта</li>
                    <li>Коммуникации с Пользователем</li>
                </ul>

                <h5>3. Защита персональных данных</h5>
                <p>3.1. Администрация сайта принимает все необходимые меры для защиты персональных данных Пользователей.</p>
                <p>3.2. Доступ к персональным данным имеют только уполномоченные сотрудники Администрации.</p>

                <h5>4. Права Пользователя</h5>
                <p>4.1. Пользователь имеет право на получение информации о своих персональных данных.</p>
                <p>4.2. Пользователь имеет право на удаление своих персональных данных, направив соответствующий запрос Администрации.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
