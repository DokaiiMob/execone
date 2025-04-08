<?php
/**
 * API для интеграции с другими сервисами
 *
 * Версия 1.0
 */

// Включаем общие файлы
require_once __DIR__ . '/../config/init.php';

// Отключаем лишний вывод
ob_clean();

// Устанавливаем заголовки для JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обрабатываем OPTIONS запрос для CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Получаем путь запроса
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api';
$path = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
$path = strtok($path, '?'); // Удаляем GET-параметры

// Читаем данные запроса
$requestData = json_decode(file_get_contents('php://input'), true) ?? [];

// Объединяем с GET и POST данными
$requestData = array_merge($_GET, $_POST, $requestData);

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Инициализируем переменные ответа
$response = [
    'success' => false,
    'message' => 'Unknown endpoint',
    'data' => null
];
$statusCode = 404;

// Проверяем токен API
$apiToken = getRequestToken();
$isAuthenticated = validateApiToken($apiToken);

// Маршрутизация API
switch ($path) {
    // Информация об API
    case '/':
    case '':
        $response = [
            'success' => true,
            'message' => 'SAMP Cheat API',
            'version' => '1.0.1',
            'endpoints' => [
                // Аутентификация
                '/auth/login' => 'POST - Авторизация пользователя',
                '/auth/logout' => 'POST - Завершение сессии',

                // Информация о пользователе
                '/user/info' => 'GET - Информация о пользователе (требует токен)',
                '/user/subscription' => 'GET - Информация о подписке (требует токен)',
                '/user/referrals' => 'GET - Информация о рефералах (требует токен)',

                // Подписки
                '/subscription/plans' => 'GET - Доступные планы подписки',
                '/subscription/purchase' => 'POST - Покупка подписки (требует токен)',

                // Читы
                '/cheat/versions' => 'GET - Список версий чита',
                '/cheat/download' => 'GET - Ссылка на скачивание чита (требует токен)',

                // Уведомления
                '/notifications/check' => 'GET - Проверка новых уведомлений (требует токен)',
                '/notifications/mark-read' => 'POST - Отметить уведомление прочитанным (требует токен)',
                '/notifications/mark-all-read' => 'POST - Отметить все уведомления прочитанными (требует токен)',
                '/notifications/delete' => 'DELETE - Удалить уведомление (требует токен)',
                '/notifications/delete-all' => 'DELETE - Удалить все уведомления (требует токен)',
            ]
        ];
        $statusCode = 200;
        break;

    // Авторизация
    case '/auth/login':
        if ($method === 'POST') {
            require_once __DIR__ . '/auth/login.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    case '/auth/logout':
        if ($method === 'POST') {
            require_once __DIR__ . '/auth/logout.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    // Информация о пользователе
    case '/user/info':
        if (!$isAuthenticated) {
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
            break;
        }

        if ($method === 'GET') {
            require_once __DIR__ . '/user/info.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    case '/user/subscription':
        if (!$isAuthenticated) {
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
            break;
        }

        if ($method === 'GET') {
            require_once __DIR__ . '/user/subscription.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    case '/user/referrals':
        if (!$isAuthenticated) {
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
            break;
        }

        if ($method === 'GET') {
            require_once __DIR__ . '/user/referrals.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    // Подписки
    case '/subscription/plans':
        if ($method === 'GET') {
            require_once __DIR__ . '/subscription/plans.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    case '/subscription/purchase':
        if (!$isAuthenticated) {
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
            break;
        }

        if ($method === 'POST') {
            require_once __DIR__ . '/subscription/purchase.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    // Информация о читах
    case '/cheat/versions':
        if ($method === 'GET') {
            require_once __DIR__ . '/cheat/versions.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    case '/cheat/download':
        if (!$isAuthenticated) {
            $response['message'] = 'Unauthorized';
            $statusCode = 401;
            break;
        }

        if ($method === 'GET') {
            require_once __DIR__ . '/cheat/download.php';
        } else {
            $response['message'] = 'Method not allowed';
            $statusCode = 405;
        }
        break;

    default:
        $response['message'] = 'Endpoint not found';
        $statusCode = 404;
}

// Отправляем ответ
http_response_code($statusCode);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * Получение токена из заголовков или параметров запроса
 */
function getRequestToken() {
    // Проверяем заголовок Authorization
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            return substr($auth, 7);
        }
    }

    // Проверяем параметр token
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }

    // Проверяем в данных POST
    if (isset($_POST['token'])) {
        return $_POST['token'];
    }

    return null;
}

/**
 * Проверка валидности API токена
 */
function validateApiToken($token) {
    if (empty($token)) {
        return false;
    }

    // Получаем экземпляр базы данных
    $db = Database::getInstance();

    // Проверяем токен в базе
    $apiToken = $db->fetch(
        "SELECT * FROM api_tokens WHERE token = ? AND active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
        [$token]
    );

    if (!$apiToken) {
        return false;
    }

    // Сохраняем ID пользователя для дальнейшего использования
    global $userId;
    $userId = $apiToken['user_id'];

    // Обновляем время последнего использования токена
    $db->update('api_tokens', [
        'last_used_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$apiToken['id']]);

    return true;
}
