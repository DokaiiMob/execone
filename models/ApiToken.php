<?php

class ApiToken {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицу API токенов, если не существует
        $this->createApiTokensTable();
    }

    /**
     * Создание таблицы API токенов
     */
    private function createApiTokensTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS api_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    description TEXT,
                    scope VARCHAR(255) DEFAULT '*',
                    active INTEGER DEFAULT 1,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    last_used_at TIMESTAMP,
                    expires_at TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } else if ($driver === 'mysql') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS api_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    description TEXT,
                    scope VARCHAR(255) DEFAULT '*',
                    active TINYINT(1) DEFAULT 1,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Генерация нового API токена для пользователя
     *
     * @param int $userId ID пользователя
     * @param string $description Описание токена
     * @param string $scope Область видимости (разрешения)
     * @param string $expires Срок действия (null = бессрочно)
     * @return array Результат операции и токен
     */
    public function generateToken($userId, $description = '', $scope = '*', $expires = null) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Генерируем токен
        $token = bin2hex(random_bytes(32));

        // Определяем срок действия
        $expiresAt = null;
        if ($expires) {
            $expiresAt = date('Y-m-d H:i:s', strtotime($expires));
        }

        // Сохраняем токен в базу данных
        $data = [
            'user_id' => $userId,
            'token' => $token,
            'description' => $description,
            'scope' => $scope,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => $expiresAt
        ];

        $tokenId = $this->db->insert('api_tokens', $data);

        return [
            'success' => true,
            'token_id' => $tokenId,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Проверка токена
     *
     * @param string $token Токен
     * @return array|false Информация о токене или false, если токен недействителен
     */
    public function validateToken($token) {
        return $this->db->fetch(
            "SELECT * FROM api_tokens WHERE token = ? AND active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
            [$token]
        );
    }

    /**
     * Деактивация токена
     *
     * @param string $token Токен
     * @param int $userId ID пользователя (для проверки, что токен принадлежит пользователю)
     * @return bool Результат операции
     */
    public function deactivateToken($token, $userId = null) {
        $conditions = ['token = ?'];
        $params = [$token];

        if ($userId) {
            $conditions[] = 'user_id = ?';
            $params[] = $userId;
        }

        $result = $this->db->update('api_tokens', [
            'active' => 0
        ], implode(' AND ', $conditions), $params);

        return $result > 0;
    }

    /**
     * Получение списка токенов пользователя
     *
     * @param int $userId ID пользователя
     * @return array Список токенов
     */
    public function getUserTokens($userId) {
        return $this->db->fetchAll(
            "SELECT id, token, description, scope, active, ip_address, user_agent, last_used_at, expires_at, created_at
            FROM api_tokens
            WHERE user_id = ?
            ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Удаление токена
     *
     * @param int $tokenId ID токена
     * @param int $userId ID пользователя (для проверки, что токен принадлежит пользователю)
     * @return bool Результат операции
     */
    public function deleteToken($tokenId, $userId = null) {
        $conditions = ['id = ?'];
        $params = [$tokenId];

        if ($userId) {
            $conditions[] = 'user_id = ?';
            $params[] = $userId;
        }

        $result = $this->db->delete('api_tokens', implode(' AND ', $conditions), $params);

        return $result > 0;
    }

    /**
     * Обновление информации о токене
     *
     * @param int $tokenId ID токена
     * @param array $data Данные для обновления
     * @param int $userId ID пользователя (для проверки, что токен принадлежит пользователю)
     * @return bool Результат операции
     */
    public function updateToken($tokenId, $data, $userId = null) {
        $conditions = ['id = ?'];
        $params = [$tokenId];

        if ($userId) {
            $conditions[] = 'user_id = ?';
            $params[] = $userId;
        }

        $result = $this->db->update('api_tokens', $data, implode(' AND ', $conditions), $params);

        return $result > 0;
    }
}
