<?php
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 404;

// Установка заголовка ответа
http_response_code($errorCode);

// Определение сообщения об ошибке
$errorMessages = [
    400 => 'Неверный запрос',
    401 => 'Необходима авторизация',
    403 => 'Доступ запрещен',
    404 => 'Страница не найдена',
    500 => 'Внутренняя ошибка сервера',
    503 => 'Сервис временно недоступен'
];

$errorTitle = isset($errorMessages[$errorCode]) ? $errorCode . ' - ' . $errorMessages[$errorCode] : $errorCode . ' - Ошибка';
$errorDesc = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Произошла ошибка';

$pageTitle = $errorTitle;
require_once __DIR__ . '/config/init.php';

// Безопасная проверка реферера
$showBackButton = false;
$referer = '';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    // Проверяем, что реферер с того же домена
    $refererHost = parse_url($referer, PHP_URL_HOST);
    $currentHost = $_SERVER['HTTP_HOST'];

    if ($refererHost === $currentHost) {
        $showBackButton = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .error-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 70vh;
            text-align: center;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 2rem;
            margin-bottom: 2rem;
        }
        .error-actions {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container error-page">
        <div class="error-code"><?= $errorCode ?></div>
        <div class="error-message"><?= htmlspecialchars($errorDesc) ?></div>
        <div class="error-desc mb-4">
            <?php if ($errorCode == 404): ?>
            <p>Страница, которую вы ищете, не существует или была перемещена.</p>
            <?php elseif ($errorCode == 403): ?>
            <p>У вас нет прав для доступа к запрашиваемой странице.</p>
            <?php elseif ($errorCode == 500): ?>
            <p>На сервере произошла ошибка. Мы уже работаем над её устранением.</p>
            <?php else: ?>
            <p>Произошла ошибка при обработке вашего запроса.</p>
            <?php endif; ?>
        </div>
        <div class="error-actions">
            <a href="/" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-home me-2"></i> На главную
            </a>
            <?php if ($showBackButton): ?>
            <a href="<?= htmlspecialchars($referer) ?>" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i> Вернуться назад
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
