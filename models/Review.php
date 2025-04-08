<?php

class Review {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицу отзывов, если не существует
        $this->createReviewsTable();
    }

    /**
     * Создание таблицы отзывов в базе данных, если она не существует
     */
    private function createReviewsTable() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS reviews (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    cheat_version_id INTEGER NOT NULL,
                    rating INTEGER NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    review_text TEXT NOT NULL,
                    is_approved INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
                )
            ");
        } else if ($driver === 'mysql') {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    cheat_version_id INT NOT NULL,
                    rating INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    review_text TEXT NOT NULL,
                    is_approved TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (cheat_version_id) REFERENCES cheat_versions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Создание нового отзыва
     *
     * @param int $userId ID пользователя
     * @param int $versionId ID версии чита
     * @param int $rating Рейтинг (от 1 до 5)
     * @param string $title Заголовок отзыва
     * @param string $text Текст отзыва
     * @return array Результат операции
     */
    public function createReview($userId, $versionId, $rating, $title, $text) {
        // Проверяем, существует ли пользователь
        $userModel = new User();
        $user = $userModel->getUserById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }

        // Проверяем, существует ли версия чита
        $cheatVersionModel = new CheatVersion();
        $version = $cheatVersionModel->getVersionById($versionId);

        if (!$version) {
            return ['success' => false, 'message' => 'Версия чита не найдена'];
        }

        // Валидация рейтинга
        $rating = max(1, min(5, $rating));

        // Проверяем, не оставлял ли пользователь уже отзыв на эту версию
        $existingReview = $this->db->fetch(
            "SELECT * FROM reviews WHERE user_id = ? AND cheat_version_id = ?",
            [$userId, $versionId]
        );

        if ($existingReview) {
            return ['success' => false, 'message' => 'Вы уже оставили отзыв на эту версию чита'];
        }

        // Создаем новый отзыв
        $reviewId = $this->db->insert('reviews', [
            'user_id' => $userId,
            'cheat_version_id' => $versionId,
            'rating' => $rating,
            'title' => $title,
            'review_text' => $text,
            'is_approved' => $userModel->isAdmin() ? 1 : 0 // Если пользователь админ, то отзыв сразу одобряется
        ]);

        // Отправляем уведомление администратору о новом отзыве
        if (!$userModel->isAdmin()) {
            $adminUsers = $this->db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
            if (!empty($adminUsers)) {
                $notificationModel = new Notification();
                foreach ($adminUsers as $admin) {
                    $notificationModel->createNotification(
                        $admin['id'],
                        'Новый отзыв на модерацию',
                        "Пользователь {$user['username']} оставил отзыв на версию чита {$version['version']}. Отзыв ожидает одобрения.",
                        'info'
                    );
                }
            }
        }

        return [
            'success' => true,
            'review_id' => $reviewId,
            'is_approved' => $userModel->isAdmin() ? 1 : 0
        ];
    }

    /**
     * Получение отзыва по ID
     *
     * @param int $reviewId ID отзыва
     * @return mixed Данные отзыва или false, если отзыв не найден
     */
    public function getReviewById($reviewId) {
        return $this->db->fetch(
            "SELECT r.*, u.username, u.avatar, cv.version
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN cheat_versions cv ON r.cheat_version_id = cv.id
            WHERE r.id = ?",
            [$reviewId]
        );
    }

    /**
     * Получение всех отзывов для версии чита
     *
     * @param int $versionId ID версии чита
     * @param bool $onlyApproved Показывать только одобренные отзывы
     * @param int $limit Лимит отзывов (по умолчанию 50)
     * @param int $offset Смещение выборки (по умолчанию 0)
     * @return array Массив отзывов
     */
    public function getVersionReviews($versionId, $onlyApproved = true, $limit = 50, $offset = 0) {
        $sql = "
            SELECT r.*, u.username, u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.cheat_version_id = ?
        ";

        if ($onlyApproved) {
            $sql .= " AND r.is_approved = 1";
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, [$versionId, $limit, $offset]);
    }

    /**
     * Получение отзывов пользователя
     *
     * @param int $userId ID пользователя
     * @param int $limit Лимит отзывов (по умолчанию 50)
     * @param int $offset Смещение выборки (по умолчанию 0)
     * @return array Массив отзывов
     */
    public function getUserReviews($userId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT r.*, cv.version
            FROM reviews r
            JOIN cheat_versions cv ON r.cheat_version_id = cv.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Получение всех отзывов (для админ-панели)
     *
     * @param bool $onlyPending Показывать только ожидающие одобрения отзывы
     * @param int $limit Лимит отзывов (по умолчанию 50)
     * @param int $offset Смещение выборки (по умолчанию 0)
     * @return array Массив отзывов
     */
    public function getAllReviews($onlyPending = false, $limit = 50, $offset = 0) {
        $sql = "
            SELECT r.*, u.username, u.email, cv.version
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN cheat_versions cv ON r.cheat_version_id = cv.id
        ";

        if ($onlyPending) {
            $sql .= " WHERE r.is_approved = 0";
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Одобрение отзыва
     *
     * @param int $reviewId ID отзыва
     * @return array Результат операции
     */
    public function approveReview($reviewId) {
        $review = $this->getReviewById($reviewId);

        if (!$review) {
            return ['success' => false, 'message' => 'Отзыв не найден'];
        }

        // Обновляем статус отзыва
        $this->db->update('reviews', [
            'is_approved' => 1
        ], 'id = ?', [$reviewId]);

        // Отправляем уведомление пользователю
        $notificationModel = new Notification();
        $notificationModel->createNotification(
            $review['user_id'],
            'Ваш отзыв одобрен',
            "Ваш отзыв на версию чита {$review['version']} был одобрен и теперь виден другим пользователям.",
            'success'
        );

        return ['success' => true];
    }

    /**
     * Отклонение отзыва
     *
     * @param int $reviewId ID отзыва
     * @param string $reason Причина отклонения
     * @return array Результат операции
     */
    public function rejectReview($reviewId, $reason = '') {
        $review = $this->getReviewById($reviewId);

        if (!$review) {
            return ['success' => false, 'message' => 'Отзыв не найден'];
        }

        // Удаляем отзыв
        $this->db->delete('reviews', 'id = ?', [$reviewId]);

        // Отправляем уведомление пользователю
        $notificationModel = new Notification();
        $notificationModel->createNotification(
            $review['user_id'],
            'Ваш отзыв отклонен',
            "Ваш отзыв на версию чита {$review['version']} был отклонен. " . ($reason ? "Причина: {$reason}" : ''),
            'warning'
        );

        return ['success' => true];
    }

    /**
     * Получение среднего рейтинга версии чита
     *
     * @param int $versionId ID версии чита
     * @return array Информация о рейтинге
     */
    public function getVersionRating($versionId) {
        $avgRating = (float) $this->db->fetchColumn(
            "SELECT AVG(rating) FROM reviews WHERE cheat_version_id = ? AND is_approved = 1",
            [$versionId]
        );

        $reviewCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reviews WHERE cheat_version_id = ? AND is_approved = 1",
            [$versionId]
        );

        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM reviews WHERE cheat_version_id = ? AND is_approved = 1 AND rating = ?",
                [$versionId, $i]
            );

            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => $reviewCount > 0 ? round(($count / $reviewCount) * 100) : 0
            ];
        }

        return [
            'average' => round($avgRating, 1),
            'count' => $reviewCount,
            'distribution' => $ratingDistribution
        ];
    }

    /**
     * Проверка, может ли пользователь оставить отзыв на версию чита
     *
     * @param int $userId ID пользователя
     * @param int $versionId ID версии чита
     * @return bool Может ли пользователь оставить отзыв
     */
    public function canUserReview($userId, $versionId) {
        // Проверяем, не оставлял ли пользователь уже отзыв на эту версию
        $existingReview = $this->db->fetch(
            "SELECT * FROM reviews WHERE user_id = ? AND cheat_version_id = ?",
            [$userId, $versionId]
        );

        if ($existingReview) {
            return false;
        }

        // Проверяем, скачивал ли пользователь эту версию чита
        $cheatVersionModel = new CheatVersion();
        $downloads = $cheatVersionModel->getUserDownloadLogs($userId);

        foreach ($downloads as $download) {
            if ($download['cheat_version_id'] == $versionId) {
                return true;
            }
        }

        return false;
    }
}
