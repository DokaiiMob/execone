<?php

class User {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Регистрация нового пользователя
     */
    public function register($username, $email, $password) {
        // Проверяем, существует ли пользователь с таким email или username
        $existingUser = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? OR username = ?",
            [$email, $username]
        );

        if ($existingUser) {
            if ($existingUser['email'] === $email) {
                return ['success' => false, 'message' => 'Пользователь с таким email уже существует'];
            } else {
                return ['success' => false, 'message' => 'Пользователь с таким именем уже существует'];
            }
        }

        // Валидация пароля
        if (strlen($password) < $this->config['auth']['password_min_length']) {
            return [
                'success' => false,
                'message' => 'Пароль должен содержать не менее ' . $this->config['auth']['password_min_length'] . ' символов'
            ];
        }

        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Генерируем токен верификации, если требуется
        $verificationToken = null;
        if ($this->config['auth']['require_email_verification']) {
            $verificationToken = bin2hex(random_bytes(32));
        }

        // Создаем нового пользователя
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'verification_token' => $verificationToken,
            'email_verified' => $this->config['auth']['require_email_verification'] ? 0 : 1
        ]);

        return [
            'success' => true,
            'user_id' => $userId,
            'verification_token' => $verificationToken,
            'require_verification' => $this->config['auth']['require_email_verification']
        ];
    }

    /**
     * Авторизация пользователя
     */
    public function login($usernameOrEmail, $password) {
        try {
            // Ищем пользователя по email или username
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE email = ? OR username = ?",
                [$usernameOrEmail, $usernameOrEmail]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'Пользователь с таким логином или email не найден'];
            }

            // Проверяем пароль
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Неверный пароль. Пожалуйста, проверьте правильность ввода'];
            }

            // Проверяем, подтвержден ли email
            if ($this->config['auth']['require_email_verification'] && !$user['email_verified']) {
                return ['success' => false, 'message' => 'Пожалуйста, подтвердите вашу электронную почту перед входом'];
            }

            // Создаем сессию для пользователя
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            // Логируем ошибку для администратора
            error_log('Ошибка при входе пользователя: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Произошла ошибка при входе. Пожалуйста, попробуйте позже'];
        }
    }

    /**
     * Проверка, авторизован ли пользователь
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }

        // Проверяем время последней активности
        $sessionLifetime = $this->config['auth']['session_lifetime'] * 60; // в секундах
        if (time() - $_SESSION['last_activity'] > $sessionLifetime) {
            $this->logout();
            return false;
        }

        // Проверяем существование пользователя в базе данных
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );

        if (!$user) {
            $this->logout();
            return false;
        }

        // Обновляем время последней активности
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Выход пользователя
     */
    public function logout() {
        // Уничтожаем все данные сессии
        $_SESSION = [];
        session_destroy();

        return true;
    }

    /**
     * Подтверждение email пользователя
     */
    public function verifyEmail($token) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE verification_token = ?",
            [$token]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Неверный токен верификации'];
        }

        // Обновляем данные пользователя
        $this->db->update('users', [
            'email_verified' => 1,
            'verification_token' => null
        ], 'id = ?', [$user['id']]);

        return ['success' => true];
    }

    /**
     * Получение данных текущего пользователя
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $user = $this->db->fetch(
            "SELECT id, username, email, avatar, role, created_at FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );

        // Если пользователь не найден в базе данных, разлогиниваем его
        if (!$user) {
            $this->logout();
            return null;
        }

        return $user;
    }

    /**
     * Получение данных пользователя по ID
     */
    public function getUserById($userId) {
        return $this->db->fetch(
            "SELECT id, username, email, avatar, role, created_at FROM users WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Обновление профиля пользователя
     */
    public function updateProfile($userId, $data) {
        // Проверяем существующего пользователя
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Если меняем email, проверяем его уникальность
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            $existingUser = $this->db->fetch(
                "SELECT * FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );

            if ($existingUser) {
                return ['success' => false, 'message' => 'Пользователь с таким email уже существует'];
            }
        }

        // Если меняем username, проверяем его уникальность
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            $existingUser = $this->db->fetch(
                "SELECT * FROM users WHERE username = ? AND id != ?",
                [$data['username'], $userId]
            );

            if ($existingUser) {
                return ['success' => false, 'message' => 'Пользователь с таким именем уже существует'];
            }
        }

        // Обновляем данные пользователя
        $this->db->update('users', $data, 'id = ?', [$userId]);

        return ['success' => true];
    }

    /**
     * Обновление пароля пользователя
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        // Получаем данные пользователя
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Проверяем текущий пароль
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Неверный текущий пароль'];
        }

        // Валидация нового пароля
        if (strlen($newPassword) < $this->config['auth']['password_min_length']) {
            return [
                'success' => false,
                'message' => 'Новый пароль должен содержать не менее ' . $this->config['auth']['password_min_length'] . ' символов'
            ];
        }

        // Хешируем новый пароль
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Обновляем пароль
        $this->db->update('users', [
            'password' => $hashedPassword
        ], 'id = ?', [$userId]);

        return ['success' => true];
    }

    /**
     * Загрузка аватара пользователя
     */
    public function uploadAvatar($userId, $avatarFile) {
        // Проверяем существование пользователя
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Получаем настройки для загрузки аватаров
        $uploadConfig = $this->config['uploads']['user_avatars'];

        // Проверяем директорию для загрузки
        if (!file_exists($uploadConfig['path'])) {
            mkdir($uploadConfig['path'], 0755, true);
        }

        // Проверяем расширение файла
        $fileExtension = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $uploadConfig['allowed_extensions'])) {
            return [
                'success' => false,
                'message' => 'Недопустимый формат файла. Разрешены: ' . implode(', ', $uploadConfig['allowed_extensions'])
            ];
        }

        // Проверяем размер файла
        if ($avatarFile['size'] > $uploadConfig['max_size']) {
            return [
                'success' => false,
                'message' => 'Размер файла превышает допустимый (' . ($uploadConfig['max_size'] / 1024 / 1024) . ' MB)'
            ];
        }

        // Генерируем уникальное имя файла
        $filename = $userId . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadConfig['path'] . $filename;

        // Перемещаем загруженный файл
        if (!move_uploaded_file($avatarFile['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
        }

        // Удаляем старый аватар, если он существует
        if ($user['avatar'] && file_exists($uploadConfig['path'] . $user['avatar'])) {
            unlink($uploadConfig['path'] . $user['avatar']);
        }

        // Обновляем данные пользователя
        $this->db->update('users', [
            'avatar' => $filename
        ], 'id = ?', [$userId]);

        return ['success' => true, 'avatar' => $filename];
    }

    /**
     * Удаление аватара пользователя
     */
    public function removeAvatar($userId) {
        // Проверяем существование пользователя
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Если у пользователя нет аватара, возвращаем ошибку
        if (empty($user['avatar'])) {
            return ['success' => false, 'message' => 'У пользователя нет аватара'];
        }

        // Получаем настройки для загрузки аватаров
        $uploadConfig = $this->config['uploads']['user_avatars'];

        // Удаляем файл аватара, если он существует
        $avatarPath = $uploadConfig['path'] . $user['avatar'];
        if (file_exists($avatarPath)) {
            unlink($avatarPath);
        }

        // Обновляем данные пользователя
        $this->db->update('users', [
            'avatar' => null
        ], 'id = ?', [$userId]);

        return ['success' => true];
    }

    /**
     * Проверка, имеет ли пользователь права администратора
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return $_SESSION['role'] === 'admin';
    }

    /**
     * Получение списка всех пользователей (только для админа)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT id, username, email, avatar, role, email_verified, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Изменение роли пользователя (только для админа)
     */
    public function changeUserRole($userId, $newRole) {
        // Проверяем, что роль валидна
        if (!in_array($newRole, ['user', 'admin'])) {
            return ['success' => false, 'message' => 'Неверная роль'];
        }

        // Обновляем роль пользователя
        $this->db->update('users', [
            'role' => $newRole
        ], 'id = ?', [$userId]);

        return ['success' => true];
    }
}
