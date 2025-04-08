<?php
require_once __DIR__ . '/config/init.php';

$articleModel = new Article();

// Получаем slug статьи из URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if (!$slug) {
    // Перенаправляем на список статей, если slug не указан
    header('Location: /blog.php');
    exit;
}

// Получаем статью по slug с увеличением счетчика просмотров
$article = $articleModel->getArticleBySlug($slug, true);

if (!$article) {
    // Если статья не найдена, показываем 404 ошибку
    header("HTTP/1.0 404 Not Found");
    include('error.php');
    exit;
}

// Устанавливаем заголовок страницы
$pageTitle = $article['title'] . ' - Чит для SAMP';

// Получаем другие статьи из той же категории (если есть категория)
$relatedArticles = [];
if ($article['category_id']) {
    $relatedArticles = $articleModel->getArticles([
        'category_id' => $article['category_id'],
        'limit' => 3,
        'order_by' => 'published_at',
        'order_dir' => 'DESC'
    ]);

    // Удаляем текущую статью из списка связанных
    foreach ($relatedArticles as $key => $relatedArticle) {
        if ($relatedArticle['id'] === $article['id']) {
            unset($relatedArticles[$key]);
            break;
        }
    }
}

// Получаем комментарии к статье
$comments = $articleModel->getArticleComments($article['id']);

// Флаг для отображения формы комментариев
$showCommentForm = isLoggedIn();

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && $showCommentForm) {
    $content = trim($_POST['comment_content'] ?? '');

    if (!empty($content)) {
        $currentUser = getUserData();

        $result = $articleModel->addComment([
            'article_id' => $article['id'],
            'user_id' => $currentUser['id'],
            'content' => $content,
            'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null
        ]);

        if ($result['success']) {
            // Перезагружаем страницу для отображения нового комментария
            header('Location: /article.php?slug=' . $slug . '#comments');
            exit;
        } else {
            $commentError = $result['message'];
        }
    } else {
        $commentError = 'Пожалуйста, введите текст комментария';
    }
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Основная колонка с статьей -->
        <div class="col-lg-8">
            <article class="blog-post">
                <?php if (!empty($article['image'])): ?>
                <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="img-fluid rounded mb-4">
                <?php endif; ?>

                <h1 class="mb-3"><?= htmlspecialchars($article['title']) ?></h1>

                <div class="mb-3 text-muted">
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
                    <span class="ms-3"><i class="far fa-eye me-1"></i> <?= $article['views'] ?> просмотров</span>
                </div>

                <?php if (!empty($article['tags'])): ?>
                <div class="mb-4">
                    <?php foreach ($article['tags'] as $tag): ?>
                    <a href="/blog.php?tag=<?= $tag['slug'] ?>" class="badge bg-secondary text-decoration-none me-1">
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="blog-content mb-5">
                    <?= $article['content'] ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-5">
                    <a href="/blog.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i> Назад к блогу</a>

                    <!-- Кнопки социальных сетей -->
                    <div class="social-share">
                        <span class="me-2">Поделиться:</span>
                        <a href="https://vk.com/share.php?url=<?= urlencode($config['site']['url'] . '/article.php?slug=' . $article['slug']) ?>&title=<?= urlencode($article['title']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="Поделиться ВКонтакте">
                            <i class="fab fa-vk"></i>
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode($config['site']['url'] . '/article.php?slug=' . $article['slug']) ?>&text=<?= urlencode($article['title']) ?>" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Поделиться в Telegram">
                            <i class="fab fa-telegram"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-secondary copy-link-btn" data-url="<?= $config['site']['url'] ?>/article.php?slug=<?= $article['slug'] ?>" title="Копировать ссылку">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>

                <!-- Комментарии -->
                <div id="comments" class="mt-5">
                    <h3 class="mb-4">Комментарии (<?= count($comments) ?>)</h3>

                    <?php if (!empty($commentError)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($commentError) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($showCommentForm): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Оставить комментарий</h5>
                            <form action="#comments" method="POST">
                                <input type="hidden" name="parent_id" id="comment-parent-id" value="">
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment_content" rows="3" required></textarea>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div id="reply-info" class="d-none">
                                        <span class="text-muted">Ответ на комментарий <span id="reply-to-name"></span></span>
                                        <button type="button" class="btn btn-sm btn-link cancel-reply">Отменить</button>
                                    </div>
                                    <button type="submit" name="add_comment" class="btn btn-primary">Отправить комментарий</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> Чтобы оставить комментарий, пожалуйста, <a href="/login.php">авторизуйтесь</a>.
                    </div>
                    <?php endif; ?>

                    <?php if (empty($comments)): ?>
                    <div class="alert alert-light">
                        <i class="far fa-comment-alt me-2"></i> Пока нет комментариев. Будьте первым, кто оставит комментарий!
                    </div>
                    <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3" id="comment-<?= $comment['id'] ?>">
                            <div class="card-body">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($comment['user_avatar'])): ?>
                                        <img src="<?= getUserAvatarUrl($comment['user_avatar']) ?>" alt="<?= htmlspecialchars($comment['username']) ?>" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><?= htmlspecialchars($comment['username']) ?></h6>
                                            <small class="text-muted"><?= formatDate($comment['created_at']) ?></small>
                                        </div>
                                        <p class="mt-2 mb-1"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                        <?php if ($showCommentForm): ?>
                                        <button type="button" class="btn btn-sm btn-link reply-btn p-0"
                                                data-comment-id="<?= $comment['id'] ?>"
                                                data-username="<?= htmlspecialchars($comment['username']) ?>">
                                            <small><i class="fas fa-reply me-1"></i> Ответить</small>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($comment['replies'])): ?>
                                <div class="ms-5 mt-3">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                    <div class="card mb-2" id="comment-<?= $reply['id'] ?>">
                                        <div class="card-body py-2">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <?php if (!empty($reply['user_avatar'])): ?>
                                                    <img src="<?= getUserAvatarUrl($reply['user_avatar']) ?>" alt="<?= htmlspecialchars($reply['username']) ?>" class="rounded-circle" width="30" height="30">
                                                    <?php else: ?>
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0 small"><?= htmlspecialchars($reply['username']) ?></h6>
                                                        <small class="text-muted"><?= formatDate($reply['created_at']) ?></small>
                                                    </div>
                                                    <p class="mt-1 mb-0 small"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>

        <!-- Боковая колонка -->
        <div class="col-lg-4">
            <!-- Блок автора -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Об авторе</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <?php
                            $authorModel = new User();
                            $author = $authorModel->getUserById($article['author_id']);
                            $authorAvatar = $author && isset($author['avatar']) ? $author['avatar'] : null;
                            ?>

                            <?php if ($authorAvatar): ?>
                            <img src="<?= getUserAvatarUrl($authorAvatar) ?>" alt="<?= htmlspecialchars($article['author_name']) ?>" class="rounded-circle" width="60" height="60">
                            <?php else: ?>
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mt-0"><?= htmlspecialchars($article['author_name']) ?></h5>
                            <p class="mb-0"><?= isset($author['bio']) ? htmlspecialchars($author['bio']) : 'Автор статей на нашем сайте.' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Похожие статьи -->
            <?php if (!empty($relatedArticles)): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Похожие статьи</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($relatedArticles as $relatedArticle): ?>
                        <li class="list-group-item px-0">
                            <a href="/article.php?slug=<?= $relatedArticle['slug'] ?>" class="text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($relatedArticle['image'])): ?>
                                    <div class="flex-shrink-0 me-3">
                                        <img src="<?= htmlspecialchars($relatedArticle['image']) ?>" alt="<?= htmlspecialchars($relatedArticle['title']) ?>" style="width: 60px; height: 60px; object-fit: cover;">
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($relatedArticle['title']) ?></h6>
                                        <div class="small text-muted">
                                            <i class="far fa-calendar-alt me-1"></i> <?= formatDate($relatedArticle['published_at']) ?>
                                            <span class="ms-2"><i class="far fa-eye me-1"></i> <?= $relatedArticle['views'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Теги -->
            <?php if (!empty($article['tags'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Теги</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($article['tags'] as $tag): ?>
                        <a href="/blog.php?tag=<?= $tag['slug'] ?>" class="badge bg-secondary text-decoration-none fs-6">
                            <?= htmlspecialchars($tag['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Подписка на новости -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Подписка на новости</h5>
                </div>
                <div class="card-body">
                    <p>Подпишитесь на нашу рассылку, чтобы получать самые свежие новости и обновления:</p>
                    <form action="/subscribe.php" method="POST">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Ваш email" name="email" required>
                            <button class="btn btn-primary" type="submit">Подписаться</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кнопки "Ответить"
    const replyButtons = document.querySelectorAll('.reply-btn');
    const cancelReplyButton = document.querySelector('.cancel-reply');
    const commentParentIdInput = document.getElementById('comment-parent-id');
    const replyInfo = document.getElementById('reply-info');
    const replyToName = document.getElementById('reply-to-name');

    replyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const username = this.getAttribute('data-username');

            commentParentIdInput.value = commentId;
            replyToName.textContent = username;
            replyInfo.classList.remove('d-none');

            // Прокручиваем к форме комментариев
            document.querySelector('form[action="#comments"]').scrollIntoView({ behavior: 'smooth' });

            // Устанавливаем фокус на поле для комментария
            document.querySelector('textarea[name="comment_content"]').focus();
        });
    });

    // Отмена ответа
    if (cancelReplyButton) {
        cancelReplyButton.addEventListener('click', function() {
            commentParentIdInput.value = '';
            replyInfo.classList.add('d-none');
        });
    }

    // Копирование ссылки на статью
    const copyLinkBtn = document.querySelector('.copy-link-btn');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                // Показываем уведомление
                const originalTooltip = this.getAttribute('title');
                this.setAttribute('title', 'Скопировано!');
                this.innerHTML = '<i class="fas fa-check"></i>';

                setTimeout(() => {
                    this.setAttribute('title', originalTooltip);
                    this.innerHTML = '<i class="fas fa-link"></i>';
                }, 2000);
            }).catch(err => {
                // Запасной вариант для старых браузеров
                const textarea = document.createElement('textarea');
                textarea.value = url;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                // Показываем уведомление
                const originalTooltip = this.getAttribute('title');
                this.setAttribute('title', 'Скопировано!');
                this.innerHTML = '<i class="fas fa-check"></i>';

                setTimeout(() => {
                    this.setAttribute('title', originalTooltip);
                    this.innerHTML = '<i class="fas fa-link"></i>';
                }, 2000);
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
