<?php

class Article {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/config.php';

        // Создаем таблицы для блога, если не существуют
        $this->createArticlesTables();
    }

    /**
     * Создание таблиц для блога и новостей
     */
    private function createArticlesTables() {
        // Проверяем тип базы данных
        $driver = $this->config['database']['driver'];

        if ($driver === 'sqlite') {
            // Создаем таблицу статей
            $this->db->query("
                CREATE TABLE IF NOT EXISTS articles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    image VARCHAR(255),
                    category_id INTEGER,
                    author_id INTEGER NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'draft',
                    views INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    published_at TIMESTAMP,
                    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
                )
            ");

            // Создаем таблицу категорий
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    parent_id INTEGER,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
                )
            ");

            // Создаем таблицу тегов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_tags (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(50) NOT NULL,
                    slug VARCHAR(50) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Создаем таблицу связей статей и тегов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_tag_relations (
                    article_id INTEGER NOT NULL,
                    tag_id INTEGER NOT NULL,
                    PRIMARY KEY (article_id, tag_id),
                    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                    FOREIGN KEY (tag_id) REFERENCES article_tags(id) ON DELETE CASCADE
                )
            ");

            // Создаем таблицу комментариев к статьям
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_comments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    article_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    parent_id INTEGER,
                    content TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES article_comments(id) ON DELETE CASCADE
                )
            ");
        } else if ($driver === 'mysql') {
            // Создаем таблицу категорий (сначала, так как на неё ссылается таблица статей)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    parent_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Создаем таблицу статей
            $this->db->query("
                CREATE TABLE IF NOT EXISTS articles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL UNIQUE,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    image VARCHAR(255),
                    category_id INT,
                    author_id INT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'draft',
                    views INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    published_at TIMESTAMP NULL,
                    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Создаем таблицу тегов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_tags (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    slug VARCHAR(50) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Создаем таблицу связей статей и тегов
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_tag_relations (
                    article_id INT NOT NULL,
                    tag_id INT NOT NULL,
                    PRIMARY KEY (article_id, tag_id),
                    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                    FOREIGN KEY (tag_id) REFERENCES article_tags(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Создаем таблицу комментариев к статьям
            $this->db->query("
                CREATE TABLE IF NOT EXISTS article_comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    article_id INT NOT NULL,
                    user_id INT NOT NULL,
                    parent_id INT,
                    content TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES article_comments(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Создание новой статьи
     *
     * @param array $data Данные статьи
     * @return array Результат операции
     */
    public function createArticle($data) {
        // Проверяем обязательные поля
        if (empty($data['title']) || empty($data['content']) || empty($data['author_id'])) {
            return ['success' => false, 'message' => 'Не заполнены обязательные поля'];
        }

        // Генерируем slug, если его нет
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Устанавливаем дату публикации для опубликованных статей
        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        // Вставляем статью в БД
        $articleId = $this->db->insert('articles', $data);

        // Обрабатываем теги, если они есть
        if (!empty($data['tags']) && is_array($data['tags'])) {
            $this->updateArticleTags($articleId, $data['tags']);
        }

        return ['success' => true, 'article_id' => $articleId];
    }

    /**
     * Обновление статьи
     *
     * @param int $articleId ID статьи
     * @param array $data Данные для обновления
     * @return array Результат операции
     */
    public function updateArticle($articleId, $data) {
        // Проверяем существование статьи
        $article = $this->getArticleById($articleId);
        if (!$article) {
            return ['success' => false, 'message' => 'Статья не найдена'];
        }

        // Обновляем slug, если изменился заголовок и slug не указан явно
        if (isset($data['title']) && !isset($data['slug']) && $data['title'] !== $article['title']) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Устанавливаем дату публикации, если статья публикуется
        if (isset($data['status']) && $data['status'] === 'published' && $article['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        // Обновляем дату изменения
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Обновляем статью в БД
        $this->db->update('articles', $data, 'id = ?', [$articleId]);

        // Обрабатываем теги, если они есть
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateArticleTags($articleId, $data['tags']);
        }

        return ['success' => true];
    }

    /**
     * Получение статьи по ID
     *
     * @param int $articleId ID статьи
     * @param bool $incrementViews Увеличивать ли счетчик просмотров
     * @return array|false Данные статьи или false, если статья не найдена
     */
    public function getArticleById($articleId, $incrementViews = false) {
        $article = $this->db->fetch(
            "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.id = ?",
            [$articleId]
        );

        if (!$article) {
            return false;
        }

        // Получаем теги для статьи
        $article['tags'] = $this->getArticleTags($articleId);

        // Увеличиваем счетчик просмотров, если нужно
        if ($incrementViews) {
            $this->incrementArticleViews($articleId);
            $article['views']++;
        }

        return $article;
    }

    /**
     * Получение статьи по slug
     *
     * @param string $slug Slug статьи
     * @param bool $incrementViews Увеличивать ли счетчик просмотров
     * @return array|false Данные статьи или false, если статья не найдена
     */
    public function getArticleBySlug($slug, $incrementViews = false) {
        $article = $this->db->fetch(
            "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name
            FROM articles a
            LEFT JOIN article_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.slug = ?",
            [$slug]
        );

        if (!$article) {
            return false;
        }

        // Получаем теги для статьи
        $article['tags'] = $this->getArticleTags($article['id']);

        // Увеличиваем счетчик просмотров, если нужно
        if ($incrementViews) {
            $this->incrementArticleViews($article['id']);
            $article['views']++;
        }

        return $article;
    }

    /**
     * Получение списка статей
     *
     * @param array $options Параметры для фильтрации и пагинации
     * @return array Список статей
     */
    public function getArticles($options = []) {
        // Устанавливаем параметры по умолчанию
        $defaults = [
            'status' => 'published',
            'category_id' => null,
            'tag_id' => null,
            'author_id' => null,
            'search' => null,
            'limit' => 10,
            'offset' => 0,
            'order_by' => 'published_at',
            'order_dir' => 'DESC'
        ];
        $options = array_merge($defaults, $options);

        // Строим базовый запрос
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name
                 FROM articles a
                 LEFT JOIN article_categories c ON a.category_id = c.id
                 LEFT JOIN users u ON a.author_id = u.id";

        $conditions = [];
        $params = [];

        // Фильтр по статусу
        if ($options['status']) {
            $conditions[] = "a.status = ?";
            $params[] = $options['status'];
        }

        // Фильтр по категории
        if ($options['category_id']) {
            $conditions[] = "a.category_id = ?";
            $params[] = $options['category_id'];
        }

        // Фильтр по автору
        if ($options['author_id']) {
            $conditions[] = "a.author_id = ?";
            $params[] = $options['author_id'];
        }

        // Фильтр по тегу
        if ($options['tag_id']) {
            $query .= " JOIN article_tag_relations tr ON a.id = tr.article_id";
            $conditions[] = "tr.tag_id = ?";
            $params[] = $options['tag_id'];
        }

        // Фильтр по поисковому запросу
        if ($options['search']) {
            $searchTerm = "%{$options['search']}%";
            $conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Добавляем условия, если они есть
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Добавляем сортировку
        $query .= " ORDER BY a.{$options['order_by']} {$options['order_dir']}";

        // Добавляем ограничение
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $options['limit'];
        $params[] = $options['offset'];

        // Выполняем запрос
        $articles = $this->db->fetchAll($query, $params);

        // Получаем теги для статей
        foreach ($articles as &$article) {
            $article['tags'] = $this->getArticleTags($article['id']);
        }

        return $articles;
    }

    /**
     * Получение количества статей (для пагинации)
     *
     * @param array $options Параметры для фильтрации
     * @return int Количество статей
     */
    public function getArticlesCount($options = []) {
        // Устанавливаем параметры по умолчанию
        $defaults = [
            'status' => 'published',
            'category_id' => null,
            'tag_id' => null,
            'author_id' => null,
            'search' => null
        ];
        $options = array_merge($defaults, $options);

        // Строим базовый запрос
        $query = "SELECT COUNT(*) FROM articles a";

        $conditions = [];
        $params = [];

        // Фильтр по статусу
        if ($options['status']) {
            $conditions[] = "a.status = ?";
            $params[] = $options['status'];
        }

        // Фильтр по категории
        if ($options['category_id']) {
            $conditions[] = "a.category_id = ?";
            $params[] = $options['category_id'];
        }

        // Фильтр по автору
        if ($options['author_id']) {
            $conditions[] = "a.author_id = ?";
            $params[] = $options['author_id'];
        }

        // Фильтр по тегу
        if ($options['tag_id']) {
            $query .= " JOIN article_tag_relations tr ON a.id = tr.article_id";
            $conditions[] = "tr.tag_id = ?";
            $params[] = $options['tag_id'];
        }

        // Фильтр по поисковому запросу
        if ($options['search']) {
            $searchTerm = "%{$options['search']}%";
            $conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Добавляем условия, если они есть
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Выполняем запрос
        return (int) $this->db->fetchColumn($query, $params);
    }

    /**
     * Удаление статьи
     *
     * @param int $articleId ID статьи
     * @return array Результат операции
     */
    public function deleteArticle($articleId) {
        // Проверяем существование статьи
        $article = $this->getArticleById($articleId);
        if (!$article) {
            return ['success' => false, 'message' => 'Статья не найдена'];
        }

        // Удаляем статью из БД
        $this->db->delete('articles', 'id = ?', [$articleId]);

        return ['success' => true];
    }

    // Вспомогательные методы

    /**
     * Генерация slug из заголовка
     *
     * @param string $title Заголовок
     * @return string Slug
     */
    private function generateSlug($title) {
        // Транслитерация и приведение к нижнему регистру
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        // Проверяем уникальность slug
        $existingSlug = $this->db->fetch(
            "SELECT slug FROM articles WHERE slug = ?",
            [$slug]
        );

        // Если такой slug уже существует, добавляем к нему уникальный суффикс
        if ($existingSlug) {
            $slug .= '-' . substr(uniqid(), -5);
        }

        return $slug;
    }

    /**
     * Увеличение счетчика просмотров статьи
     *
     * @param int $articleId ID статьи
     */
    private function incrementArticleViews($articleId) {
        $this->db->query(
            "UPDATE articles SET views = views + 1 WHERE id = ?",
            [$articleId]
        );
    }

    /**
     * Получение тегов статьи
     *
     * @param int $articleId ID статьи
     * @return array Список тегов
     */
    private function getArticleTags($articleId) {
        return $this->db->fetchAll(
            "SELECT t.id, t.name, t.slug
            FROM article_tags t
            JOIN article_tag_relations tr ON t.id = tr.tag_id
            WHERE tr.article_id = ?
            ORDER BY t.name",
            [$articleId]
        );
    }

    /**
     * Обновление тегов статьи
     *
     * @param int $articleId ID статьи
     * @param array $tags Список тегов
     */
    private function updateArticleTags($articleId, $tags) {
        // Удаляем все текущие связи
        $this->db->delete('article_tag_relations', 'article_id = ?', [$articleId]);

        foreach ($tags as $tag) {
            // Если передан ID тега, используем его
            if (is_numeric($tag)) {
                $tagId = $tag;
            } else {
                // Иначе ищем тег по имени или создаем новый
                $existingTag = $this->db->fetch(
                    "SELECT id FROM article_tags WHERE name = ?",
                    [$tag]
                );

                if ($existingTag) {
                    $tagId = $existingTag['id'];
                } else {
                    // Создаем новый тег
                    $tagId = $this->db->insert('article_tags', [
                        'name' => $tag,
                        'slug' => $this->generateSlug($tag)
                    ]);
                }
            }

            // Добавляем связь
            $this->db->insert('article_tag_relations', [
                'article_id' => $articleId,
                'tag_id' => $tagId
            ]);
        }
    }

    /**
     * Создание новой категории
     *
     * @param array $data Данные категории
     * @return array Результат операции
     */
    public function createCategory($data) {
        // Проверяем обязательные поля
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Не заполнено название категории'];
        }

        // Генерируем slug, если его нет
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Вставляем категорию в БД
        $categoryId = $this->db->insert('article_categories', $data);

        return ['success' => true, 'category_id' => $categoryId];
    }

    /**
     * Получение всех категорий
     *
     * @return array Список категорий
     */
    public function getCategories() {
        return $this->db->fetchAll(
            "SELECT * FROM article_categories ORDER BY name"
        );
    }

    /**
     * Получение всех тегов
     *
     * @return array Список тегов
     */
    public function getTags() {
        return $this->db->fetchAll(
            "SELECT * FROM article_tags ORDER BY name"
        );
    }

    /**
     * Получение категории по ID
     *
     * @param int $categoryId ID категории
     * @return array|false Данные категории или false, если категория не найдена
     */
    public function getCategoryById($categoryId) {
        return $this->db->fetch(
            "SELECT * FROM article_categories WHERE id = ?",
            [$categoryId]
        );
    }

    /**
     * Получение категории по slug
     *
     * @param string $slug Slug категории
     * @return array|false Данные категории или false, если категория не найдена
     */
    public function getCategoryBySlug($slug) {
        return $this->db->fetch(
            "SELECT * FROM article_categories WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Обновление категории
     *
     * @param int $categoryId ID категории
     * @param array $data Данные для обновления
     * @return array Результат операции
     */
    public function updateCategory($categoryId, $data) {
        // Проверяем существование категории
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            return ['success' => false, 'message' => 'Категория не найдена'];
        }

        // Обновляем slug, если изменилось название и slug не указан явно
        if (isset($data['name']) && !isset($data['slug']) && $data['name'] !== $category['name']) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Обновляем категорию в БД
        $this->db->update('article_categories', $data, 'id = ?', [$categoryId]);

        return ['success' => true];
    }

    /**
     * Удаление категории
     *
     * @param int $categoryId ID категории
     * @return array Результат операции
     */
    public function deleteCategory($categoryId) {
        // Проверяем существование категории
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            return ['success' => false, 'message' => 'Категория не найдена'];
        }

        // Удаляем категорию из БД
        $this->db->delete('article_categories', 'id = ?', [$categoryId]);

        return ['success' => true];
    }

    /**
     * Получение комментариев к статье
     *
     * @param int $articleId ID статьи
     * @return array Список комментариев с вложенными ответами
     */
    public function getArticleComments($articleId) {
        // Получаем все комментарии к статье
        $allComments = $this->db->fetchAll(
            "SELECT c.*, u.username, u.avatar as user_avatar
            FROM article_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.article_id = ? AND c.status = 'approved'
            ORDER BY c.created_at ASC",
            [$articleId]
        );

        // Группируем комментарии по родительскому ID
        $commentsByParent = [];
        foreach ($allComments as $comment) {
            $parentId = $comment['parent_id'] ?: 0; // 0 для корневых комментариев
            $commentsByParent[$parentId][] = $comment;
        }

        // Извлекаем корневые комментарии
        $rootComments = $commentsByParent[0] ?? [];

        // Добавляем вложенные ответы к каждому корневому комментарию
        foreach ($rootComments as &$rootComment) {
            $rootComment['replies'] = $commentsByParent[$rootComment['id']] ?? [];
        }

        return $rootComments;
    }

    /**
     * Добавление комментария к статье
     *
     * @param array $data Данные комментария
     * @return array Результат операции
     */
    public function addComment($data) {
        // Проверяем обязательные поля
        if (empty($data['article_id']) || empty($data['user_id']) || empty($data['content'])) {
            return ['success' => false, 'message' => 'Не заполнены обязательные поля'];
        }

        // Проверяем существование статьи
        $article = $this->getArticleById($data['article_id']);
        if (!$article) {
            return ['success' => false, 'message' => 'Статья не найдена'];
        }

        // Проверяем существование родительского комментария, если указан
        if (!empty($data['parent_id'])) {
            $parentComment = $this->db->fetch(
                "SELECT * FROM article_comments WHERE id = ? AND article_id = ?",
                [$data['parent_id'], $data['article_id']]
            );

            if (!$parentComment) {
                return ['success' => false, 'message' => 'Родительский комментарий не найден'];
            }
        }

        // Устанавливаем статус комментария
        // Если у пользователя роль admin, сразу одобряем комментарий
        $userModel = new User();
        $user = $userModel->getUserById($data['user_id']);

        $data['status'] = ($user && $user['role'] === 'admin') ? 'approved' : 'pending';

        // Вставляем комментарий в БД
        $commentId = $this->db->insert('article_comments', $data);

        return ['success' => true, 'comment_id' => $commentId];
    }

    /**
     * Получение тега по slug
     *
     * @param string $slug Slug тега
     * @return array|false Данные тега или false, если тег не найден
     */
    public function getTagBySlug($slug) {
        return $this->db->fetch(
            "SELECT * FROM article_tags WHERE slug = ?",
            [$slug]
        );
    }

    /**
     * Получение количества статей в категории
     *
     * @param int $categoryId ID категории
     * @return int Количество статей
     */
    public function getCategoryArticlesCount($categoryId) {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'",
            [$categoryId]
        );
    }

    /**
     * Получение списка популярных тегов
     *
     * @param int $limit Количество тегов
     * @return array Список тегов с количеством статей
     */
    public function getPopularTags($limit = 10) {
        return $this->db->fetchAll(
            "SELECT t.*, COUNT(tr.article_id) as articles_count
            FROM article_tags t
            JOIN article_tag_relations tr ON t.id = tr.tag_id
            JOIN articles a ON tr.article_id = a.id
            WHERE a.status = 'published'
            GROUP BY t.id
            ORDER BY articles_count DESC
            LIMIT ?",
            [$limit]
        );
    }
}
