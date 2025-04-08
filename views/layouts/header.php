<?php
require_once __DIR__ . '/../../config/init.php';
$config = require __DIR__ . '/../../config/config.php';
$user = new User();
$currentUser = $user->getCurrentUser();

// Инициализируем счетчик уведомлений для авторизованных пользователей
$unreadNotificationsCount = 0;
if ($user->isLoggedIn()) {
    $notificationModel = new Notification();
    $unreadNotificationsCount = $notificationModel->getUnreadCount($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? $config['site']['name'] ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Dark Theme CSS -->
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <!-- Animations CSS -->
    <link rel="stylesheet" href="/assets/css/animations.css">
    <?php if (isset($extraCss)): ?>
    <?= $extraCss ?>
    <?php endif; ?>
</head>
<body class="<?= $user->isLoggedIn() ? 'user-logged-in' : 'user-guest' ?>">
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <i class="fas fa-gamepad me-2"></i>
                    <?= $config['site']['name'] ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/"><i class="fas fa-home me-1"></i> Главная</a>
                        </li>
                        <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/downloads.php"><i class="fas fa-download me-1"></i> Скачать чит</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/subscription.php"><i class="fas fa-gem me-1"></i> Подписки</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/faq.php"><i class="fas fa-question-circle me-1"></i> FAQ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/about.php"><i class="fas fa-info-circle me-1"></i> О читах</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/blog.php">Блог</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/api-docs.php"><i class="fas fa-code me-1"></i> API</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <!-- Theme Switcher -->
                        <li class="nav-item theme-switch-wrapper me-2">
                            <span class="theme-switch">
                                <label class="form-check-label" for="theme-switch">
                                    <input type="checkbox" id="theme-switch">
                                    <span class="slider round"></span>
                                </label>
                            </span>
                        </li>
                        <?php if ($user->isLoggedIn()): ?>
                            <?php if ($user->isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link text-warning" href="/admin/"><i class="fas fa-crown me-1"></i> Админ-панель</a>
                            </li>
                            <?php endif; ?>
                            <!-- Добавляем индикатор уведомлений -->
                            <li class="nav-item">
                                <a class="nav-link position-relative" href="/notifications.php">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unreadNotificationsCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?>
                                        <span class="visually-hidden">Новые уведомления</span>
                                    </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php if (!empty($currentUser['avatar'])): ?>
                                    <img src="<?= getUserAvatarUrl($currentUser['avatar']) ?>" alt="Аватар" class="rounded-circle me-1" style="width: 25px; height: 25px; object-fit: cover;">
                                    <?php else: ?>
                                    <i class="fas fa-user me-1"></i>
                                    <?php endif; ?>
                                    <?= $currentUser['username'] ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-cog me-1"></i> Профиль</a></li>
                                    <li><a class="dropdown-item" href="/subscription.php"><i class="fas fa-credit-card fa-fw me-2"></i> Подписка</a></li>
                                    <li><a class="dropdown-item" href="/referrals.php"><i class="fas fa-users fa-fw me-2"></i> Рефералы</a></li>
                                    <li><a class="dropdown-item" href="/notifications.php"><i class="fas fa-bell fa-fw me-2"></i> Уведомления
                                        <?php if ($unreadNotificationsCount > 0): ?>
                                        <span class="badge bg-danger"><?= $unreadNotificationsCount ?></span>
                                        <?php endif; ?>
                                    </a></li>
                                    <li><a class="dropdown-item" href="/api-tokens.php"><i class="fas fa-key fa-fw me-2"></i> API Токены</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Выйти</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/login.php"><i class="fas fa-sign-in-alt me-1"></i> Войти</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/register.php"><i class="fas fa-user-plus me-1"></i> Регистрация</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container py-4">
        <?php if (!empty(getErrorMessage())): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= getErrorMessage() ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty(getSuccessMessage())): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= getSuccessMessage() ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
