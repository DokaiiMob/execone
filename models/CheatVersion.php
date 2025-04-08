<?php

class CheatVersion {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем директорию для файлов читов, если она не существует
        $uploadDir = $this->config['uploads']['cheat_files']['path'];
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    }

    /**
     * Получение списка всех версий чита
     */
    public function getAllVersions($onlyActive = true, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM cheat_versions";
        $params = [];

        if ($onlyActive) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Получение версии чита по ID
     */
    public function getVersionById($versionId) {
        return $this->db->fetch("SELECT * FROM cheat_versions WHERE id = ?", [$versionId]);
    }

    /**
     * Добавление новой версии чита
     */
    public function addVersion($version, $description, $file, $requiredPlan = 'basic') {
        // Проверяем директорию для загрузки
        $uploadConfig = $this->config['uploads']['cheat_files'];
        if (!file_exists($uploadConfig['path'])) {
            mkdir($uploadConfig['path'], 0755, true);
        }

        // Проверяем расширение файла
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $uploadConfig['allowed_extensions'])) {
            return [
                'success' => false,
                'message' => 'Недопустимый формат файла. Разрешены: ' . implode(', ', $uploadConfig['allowed_extensions'])
            ];
        }

        // Проверяем размер файла
        if ($file['size'] > $uploadConfig['max_size']) {
            return [
                'success' => false,
                'message' => 'Размер файла превышает допустимый (' . ($uploadConfig['max_size'] / 1024 / 1024) . ' MB)'
            ];
        }

        // Генерируем уникальное имя файла
        $filename = 'cheat_' . $version . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadConfig['path'] . $filename;

        // Перемещаем загруженный файл
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
        }

        // Создаем запись в базе данных
        $versionId = $this->db->insert('cheat_versions', [
            'version' => $version,
            'file_path' => $filename,
            'description' => $description,
            'required_plan' => $requiredPlan,
            'is_active' => 1
        ]);

        return [
            'success' => true,
            'version_id' => $versionId,
            'file_path' => $filename
        ];
    }

    /**
     * Обновление версии чита
     */
    public function updateVersion($versionId, $data, $file = null) {
        // Проверяем существование версии
        $version = $this->getVersionById($versionId);
        if (!$version) {
            return ['success' => false, 'message' => 'Версия чита не найдена'];
        }

        // Если загружен новый файл, обрабатываем его
        if ($file !== null && $file['error'] === 0) {
            $uploadConfig = $this->config['uploads']['cheat_files'];

            // Проверяем расширение файла
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $uploadConfig['allowed_extensions'])) {
                return [
                    'success' => false,
                    'message' => 'Недопустимый формат файла. Разрешены: ' . implode(', ', $uploadConfig['allowed_extensions'])
                ];
            }

            // Проверяем размер файла
            if ($file['size'] > $uploadConfig['max_size']) {
                return [
                    'success' => false,
                    'message' => 'Размер файла превышает допустимый (' . ($uploadConfig['max_size'] / 1024 / 1024) . ' MB)'
                ];
            }

            // Генерируем уникальное имя файла
            $filename = 'cheat_' . $version['version'] . '_' . uniqid() . '.' . $fileExtension;
            $targetPath = $uploadConfig['path'] . $filename;

            // Перемещаем загруженный файл
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
            }

            // Удаляем старый файл
            if (file_exists($uploadConfig['path'] . $version['file_path'])) {
                unlink($uploadConfig['path'] . $version['file_path']);
            }

            // Обновляем путь к файлу в данных для обновления
            $data['file_path'] = $filename;
        }

        // Обновляем запись в базе данных
        $this->db->update('cheat_versions', $data, 'id = ?', [$versionId]);

        return ['success' => true];
    }

    /**
     * Активация/деактивация версии чита
     */
    public function toggleVersionStatus($versionId, $isActive) {
        // Проверяем существование версии
        $version = $this->getVersionById($versionId);
        if (!$version) {
            return ['success' => false, 'message' => 'Версия чита не найдена'];
        }

        // Обновляем статус
        $this->db->update('cheat_versions', [
            'is_active' => $isActive ? 1 : 0
        ], 'id = ?', [$versionId]);

        return ['success' => true];
    }

    /**
     * Удаление версии чита
     */
    public function deleteVersion($versionId) {
        // Проверяем существование версии
        $version = $this->getVersionById($versionId);
        if (!$version) {
            return ['success' => false, 'message' => 'Версия чита не найдена'];
        }

        // Удаляем файл
        $filePath = $this->config['uploads']['cheat_files']['path'] . $version['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Удаляем запись из базы данных
        $this->db->delete('cheat_versions', 'id = ?', [$versionId]);

        return ['success' => true];
    }

    /**
     * Получение списка доступных версий чита для пользователя
     */
    public function getAvailableVersionsForUser($userId) {
        // Получаем текущую подписку пользователя
        $subscription = new Subscription();
        $userSubscription = $subscription->getUserSubscription($userId);

        // Если у пользователя нет активной подписки, возвращаем пустой массив
        if (!$userSubscription) {
            return [];
        }

        $userPlan = $userSubscription['plan_type'];

        // В зависимости от плана подписки, возвращаем доступные версии
        $sql = "SELECT * FROM cheat_versions WHERE is_active = 1";
        $params = [];

        // Если у пользователя базовый план, он имеет доступ только к базовым версиям
        if ($userPlan === 'basic') {
            $sql .= " AND required_plan = 'basic'";
        }
        // Если у пользователя премиум план, он имеет доступ к базовым и премиум версиям
        else if ($userPlan === 'premium') {
            $sql .= " AND (required_plan = 'basic' OR required_plan = 'premium')";
        }
        // Если у пользователя VIP план, он имеет доступ ко всем версиям
        // для VIP не добавляем дополнительные условия

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Запись лога скачивания чита
     */
    public function logDownload($userId, $versionId) {
        $this->db->insert('download_logs', [
            'user_id' => $userId,
            'cheat_version_id' => $versionId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    /**
     * Получение лога скачиваний для конкретного пользователя
     */
    public function getUserDownloadLogs($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT dl.*, cv.version, cv.required_plan
            FROM download_logs dl
            JOIN cheat_versions cv ON dl.cheat_version_id = cv.id
            WHERE dl.user_id = ?
            ORDER BY dl.download_date DESC
            LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Получение всех логов скачиваний (для админа)
     */
    public function getAllDownloadLogs($limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT dl.*, cv.version, cv.required_plan, u.username, u.email
            FROM download_logs dl
            JOIN cheat_versions cv ON dl.cheat_version_id = cv.id
            JOIN users u ON dl.user_id = u.id
            ORDER BY dl.download_date DESC
            LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Получение количества загрузок за определенный период
     *
     * @param string $period Период ('today', 'week', 'month', 'year')
     * @return int Количество загрузок за указанный период
     */
    public function getDownloadCountByPeriod($period = 'today') {
        $dateCondition = '';

        switch ($period) {
            case 'today':
                $dateCondition = "DATE(download_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "download_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "download_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "download_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
            default:
                $dateCondition = "1=1"; // Все записи
        }

        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM download_logs WHERE $dateCondition"
        );
    }
}
