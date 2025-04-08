<?php
/**
 * Файл инициализации проекта
 */

// Установка отображения ошибок
$config = require __DIR__ . '/config.php';
if ($config['site']['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция автозагрузки классов
spl_autoload_register(function ($className) {
    // Пути к директориям с классами
    $paths = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
    ];

    // Проверяем каждый путь
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Инициализация соединения с базой данных
$db = Database::getInstance();

// Создание таблиц если они не существуют
$db->initializeDatabase();

// Создание директорий для загрузки файлов
$uploadDirs = [
    $config['uploads']['cheat_files']['path'],
    $config['uploads']['user_avatars']['path']
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Функция для перенаправления
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Функция для проверки авторизации
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        $user = new User();
        if (!$user->isLoggedIn()) {
            redirect('/login.php');
        }
    }
}

/**
 * Функция для проверки прав администратора
 */
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        $user = new User();
        if (!$user->isLoggedIn() || !$user->isAdmin()) {
            redirect('/index.php');
        }
    }
}

/**
 * Функция для вывода сообщения об ошибке
 */
if (!function_exists('displayError')) {
    function displayError($message) {
        $_SESSION['error_message'] = $message;

        // Если это AJAX запрос, просто выходим
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        // Если это обычный запрос, делаем перенаправление назад на ту же страницу
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: ' . $_SERVER['REQUEST_URI']);
        }
        exit;
    }
}

/**
 * Функция для вывода сообщения об успехе
 */
if (!function_exists('displaySuccess')) {
    function displaySuccess($message) {
        $_SESSION['success_message'] = $message;
    }
}

/**
 * Функция для получения сообщения об ошибке и очистки
 */
if (!function_exists('getErrorMessage')) {
    function getErrorMessage() {
        $message = $_SESSION['error_message'] ?? '';
        unset($_SESSION['error_message']);
        return $message;
    }
}

/**
 * Функция для получения сообщения об успехе и очистки
 */
if (!function_exists('getSuccessMessage')) {
    function getSuccessMessage() {
        $message = $_SESSION['success_message'] ?? '';
        unset($_SESSION['success_message']);
        return $message;
    }
}

/**
 * Функция для проверки наличия активной подписки
 */
if (!function_exists('requireActiveSubscription')) {
    function requireActiveSubscription() {
        requireLogin();

        $user = new User();
        $userId = $user->getCurrentUser()['id'];

        $subscription = new Subscription();
        if (!$subscription->hasActiveSubscription($userId)) {
            // Сохраняем сообщение об ошибке для отображения на странице подписки
            $_SESSION['error_message'] = 'Для доступа к этому разделу требуется активная подписка';
            redirect('/subscription.php');
        }
    }
}

/**
 * Функция для проверки доступа к определенному плану подписки
 */
if (!function_exists('requirePlanAccess')) {
    function requirePlanAccess($requiredPlan) {
        requireLogin();

        $user = new User();
        $userId = $user->getCurrentUser()['id'];

        $subscription = new Subscription();
        if (!$subscription->hasAccessToPlan($userId, $requiredPlan)) {
            // Сохраняем сообщение об ошибке для отображения на странице подписки
            $_SESSION['error_message'] = 'Для доступа к этому разделу требуется подписка уровня "' .
                $subscription->getPlan($requiredPlan)['name'] . '" или выше';
            redirect('/subscription.php');
        }
    }
}

/**
 * Функция для очистки входных данных
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = sanitizeInput($value);
            }
        } else {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }

        return $input;
    }
}

/**
 * Функция для получения базового URL сайта
 */
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $config = require __DIR__ . '/config.php';
        return $config['site']['url'];
    }
}

/**
 * Функция для получения URL аватара пользователя
 */
if (!function_exists('getUserAvatarUrl')) {
    function getUserAvatarUrl($avatarFilename) {
        if (empty($avatarFilename)) {
            return '/assets/images/default-avatar.jpg';
        }

        $config = require __DIR__ . '/config.php';
        $avatarsDir = str_replace(__DIR__ . '/..', '', $config['uploads']['user_avatars']['path']);

        return $avatarsDir . $avatarFilename;
    }
}

/**
 * Функция для вывода даты в удобном формате
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd.m.Y H:i') {
        return date($format, strtotime($date));
    }
}

/**
 * Функция для форматирования цены
 */
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 0, ',', ' ') . ' руб.';
    }
}

/**
 * Функция для склонения слов в зависимости от числа
 */
if (!function_exists('pluralize')) {
    function pluralize($number, $one, $few, $many) {
        if ($number % 10 == 1 && $number % 100 != 11) {
            return $one;
        } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
            return $few;
        } else {
            return $many;
        }
    }
}
