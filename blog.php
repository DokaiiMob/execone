<?php
$pageTitle = 'Блог - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

$articleModel = new Article();

// Получаем параметры для фильтрации и пагинации
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($currentPage - 1) * $perPage;

$categorySlug = isset($_GET['category']) ? $_GET['category'] : null;
$tagSlug = isset($_GET['tag']) ? $_GET['tag'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Опции для запроса статей
$options = [
    'status' => 'published',
    'limit' => $perPage,
    'offset' => $offset,
    'order_by' => 'published_at',
    'order_dir' => 'DESC'
];

// Фильтр по категории
$currentCategory = null;
if ($categorySlug) {
    $currentCategory = $articleModel->getCategoryBySlug($categorySlug);
    if ($currentCategory) {
        $options['category_id'] = $currentCategory['id'];
    }
}

// Фильтр по тегу
$currentTag = null;
if ($tagSlug) {
    $currentTag = $articleModel->getTagBySlug($tagSlug);
    if ($currentTag) {
        $options['tag_id'] = $currentTag['id'];
    }
}

// Поиск
if ($search) {
    $options['search'] = $search;
}

// Получаем статьи с учетом фильтров
$articles = $articleModel->getArticles($options);

// Получаем общее количество статей для пагинации
$totalArticles = $articleModel->getArticlesCount($options);
$totalPages = ceil($totalArticles / $perPage);

// Получаем все категории для бокового меню
$categories = $articleModel->getCategories();

// Получаем популярные теги для бокового меню
$tags = $articleModel->getPopularTags(10);

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Основная колонка со статьями -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <?php if ($currentCategory): ?>
                        <?= htmlspecialchars($currentCategory['name']) ?>
                    <?php elseif ($currentTag): ?>
                        Тег: <?= htmlspecialchars($currentTag['name']) ?>
                    <?php elseif ($search): ?>
                        Результаты поиска: "<?= htmlspecialchars($search) ?>"
                    <?php else: ?>
                        Блог
                    <?php endif; ?>
                </h1>

                <!-- Форма поиска -->
                <form class="d-none d-md-flex" action="/blog.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Поиск..." name="search" value="<?= htmlspecialchars($search ?? '') ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <?php if ($currentCategory && !empty($currentCategory['description'])): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($currentCategory['description'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($articles)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Статьи не найдены. Попробуйте изменить параметры поиска или вернитесь позже.
            </div>
            <?php else: ?>
                <!-- Список статей -->
                <?php foreach ($articles as $article): ?>
                <div class="card mb-4">
                    <?php if (!empty($article['image'])): ?>
                    <img src="<?= htmlspecialchars($article['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h2 class="card-title h4">
                            <a href="/article.php?slug=<?= $article['slug'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h2>
                        <div class="mb-2 text-muted small">
                            <span><i class="far fa-calendar-alt me-1"></i> <?= formatDate($article['published_at']) ?></span>
                            <span class="ms-3"><i class="far fa-user me-1"></i> <?= htmlspecialchars($article['author_name']) ?></span>
                            <?php if ($article['category_name']): ?>
                            <span class="ms-3">
                                <i class="far fa-folder me-1"></i>
                                <a href="/blog.php?category=<?= $article['category_slug'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($article['category_name']) ?>
                                </a>
                            </span>
                            <?php endif; ?>
                            <span class="ms-3"><i class="far fa-eye me-1"></i> <?= $article['views'] ?></span>
                        </div>

                        <p class="card-text">
                            <?= !empty($article['excerpt']) ? nl2br(htmlspecialchars($article['excerpt'])) : substr(strip_tags($article['content']), 0, 200) . '...' ?>
                        </p>

                        <!-- Теги -->
                        <?php if (!empty($article['tags'])): ?>
                        <div class="mb-3">
                            <?php foreach ($article['tags'] as $tag): ?>
                            <a href="/blog.php?tag=<?= $tag['slug'] ?>" class="badge bg-secondary text-decoration-none me-1">
                                <?= htmlspecialchars($tag['name']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <a href="/article.php?slug=<?= $article['slug'] ?>" class="btn btn-primary">Читать далее</a>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Пагинация">
                    <ul class="pagination justify-content-center">
                        <!-- Кнопка "Предыдущая" -->
                        <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Предыдущая">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <!-- Номера страниц -->
                        <?php
                        $startPage = max(1, min($currentPage - 2, $totalPages - 4));
                        $endPage = min($totalPages, max($currentPage + 2, 5));

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <!-- Кнопка "Следующая" -->
                        <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Следующая">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Боковая колонка с виджетами -->
        <div class="col-lg-4">
            <!-- Форма поиска (показывается только на мобильных устройствах) -->
            <div class="d-md-none mb-4">
                <form action="/blog.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Поиск..." name="search" value="<?= htmlspecialchars($search ?? '') ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <!-- Категории -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Категории</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $category): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center <?= ($currentCategory && $currentCategory['id'] === $category['id']) ? 'active' : '' ?>">
                            <a href="/blog.php?category=<?= $category['slug'] ?>" class="text-decoration-none <?= ($currentCategory && $currentCategory['id'] === $category['id']) ? 'text-white' : 'text-dark' ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                            <span class="badge bg-primary rounded-pill"><?= $articleModel->getCategoryArticlesCount($category['id']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Популярные теги -->
            <?php if (!empty($tags)): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Популярные теги</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                        <a href="/blog.php?tag=<?= $tag['slug'] ?>" class="badge bg-secondary text-decoration-none fs-6 <?= ($currentTag && $currentTag['id'] === $tag['id']) ? 'bg-dark' : '' ?>">
                            <?= htmlspecialchars($tag['name']) ?>
                            <span class="badge bg-light text-dark rounded-pill"><?= $tag['articles_count'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Популярные статьи -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Популярные статьи</h5>
                </div>
                <div class="card-body">
                    <?php
                    $popularArticles = $articleModel->getArticles([
                        'limit' => 5,
                        'order_by' => 'views',
                        'order_dir' => 'DESC'
                    ]);
                    ?>

                    <?php if (empty($popularArticles)): ?>
                    <p class="mb-0">Нет популярных статей.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($popularArticles as $popularArticle): ?>
                        <li class="list-group-item px-0">
                            <a href="/article.php?slug=<?= $popularArticle['slug'] ?>" class="text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($popularArticle['image'])): ?>
                                    <div class="flex-shrink-0 me-3">
                                        <img src="<?= htmlspecialchars($popularArticle['image']) ?>" alt="<?= htmlspecialchars($popularArticle['title']) ?>" style="width: 60px; height: 60px; object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($popularArticle['title']) ?></h6>
                                        <div class="small text-muted">
                                            <i class="far fa-calendar-alt me-1"></i> <?= formatDate($popularArticle['published_at']) ?>
                                            <span class="ms-2"><i class="far fa-eye me-1"></i> <?= $popularArticle['views'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
