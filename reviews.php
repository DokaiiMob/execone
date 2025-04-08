<?php
$pageTitle = 'Отзывы - Чит для SAMP';
require_once __DIR__ . '/config/init.php';

// Получаем ID версии чита и проверяем его существование
$versionId = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;

if (!$versionId) {
    displayError('Не указан ID версии чита');
    header('Location: /downloads.php');
    exit;
}

$cheatVersionModel = new CheatVersion();
$version = $cheatVersionModel->getVersionById($versionId);

if (!$version) {
    displayError('Указанная версия чита не найдена');
    header('Location: /downloads.php');
    exit;
}

$userModel = new User();
$reviewModel = new Review();
$subscriptionModel = new Subscription();

$currentUser = $userModel->getCurrentUser();
$isLoggedIn = $userModel->isLoggedIn();
$isAdmin = $isLoggedIn && $userModel->isAdmin();

// Получаем рейтинг и отзывы для версии
$rating = $reviewModel->getVersionRating($versionId);
$reviews = $reviewModel->getVersionReviews($versionId, !$isAdmin);

// Проверяем, может ли пользователь оставить отзыв
$canReview = false;
if ($isLoggedIn) {
    $canReview = $reviewModel->canUserReview($currentUser['id'], $versionId);
}

// Обработка формы добавления отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review']) && $isLoggedIn && $canReview) {
    $reviewTitle = sanitizeInput($_POST['review_title'] ?? '');
    $reviewText = sanitizeInput($_POST['review_text'] ?? '');
    $reviewRating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;

    if (empty($reviewTitle) || empty($reviewText)) {
        displayError('Пожалуйста, заполните все поля формы');
    } else {
        $result = $reviewModel->createReview(
            $currentUser['id'],
            $versionId,
            $reviewRating,
            $reviewTitle,
            $reviewText
        );

        if ($result['success']) {
            if ($result['is_approved']) {
                displaySuccess('Ваш отзыв успешно добавлен');
            } else {
                displaySuccess('Ваш отзыв отправлен на модерацию и будет опубликован после проверки');
            }

            // Перенаправляем на страницу отзывов
            header("Location: /reviews.php?version_id={$versionId}");
            exit;
        } else {
            displayError($result['message']);
        }
    }
}

// Обработка действий администратора с отзывами
if ($isAdmin && isset($_GET['action']) && isset($_GET['review_id'])) {
    $action = $_GET['action'];
    $reviewId = (int)$_GET['review_id'];

    if ($action === 'approve') {
        $result = $reviewModel->approveReview($reviewId);
        if ($result['success']) {
            displaySuccess('Отзыв успешно одобрен');
        } else {
            displayError($result['message']);
        }
    } elseif ($action === 'reject') {
        $reason = isset($_GET['reason']) ? sanitizeInput($_GET['reason']) : '';
        $result = $reviewModel->rejectReview($reviewId, $reason);
        if ($result['success']) {
            displaySuccess('Отзыв успешно отклонен');
        } else {
            displayError($result['message']);
        }
    }

    // Перенаправляем на страницу отзывов
    header("Location: /reviews.php?version_id={$versionId}");
    exit;
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/downloads.php">Скачать</a></li>
                    <li class="breadcrumb-item active">Отзывы для версии <?= htmlspecialchars($version['version']) ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Отзывы для версии <?= htmlspecialchars($version['version']) ?></h1>
                <?php if ($isLoggedIn && $canReview): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                    <i class="fas fa-plus me-1"></i> Добавить отзыв
                </button>
                <?php endif; ?>
            </div>

            <!-- Информация о рейтинге -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="display-3 fw-bold text-primary"><?= $rating['average'] ?></div>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($rating['average'])): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php elseif ($i - 0.5 <= $rating['average']): ?>
                                        <i class="fas fa-star-half-alt text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="text-muted">Основано на <?= $rating['count'] ?> <?= pluralize($rating['count'], 'отзыве', 'отзывах', 'отзывах') ?></div>
                        </div>
                        <div class="col-md-9">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="text-warning me-2"><?= $i ?> <i class="fas fa-star"></i></div>
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $rating['distribution'][$i]['percentage'] ?>%;"
                                            aria-valuenow="<?= $rating['distribution'][$i]['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="ms-2 text-muted"><?= $rating['distribution'][$i]['count'] ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Список отзывов -->
            <?php if (empty($reviews)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Для этой версии чита пока нет отзывов.
                    <?php if ($isLoggedIn && $canReview): ?>
                    Будьте первым, кто оставит отзыв!
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($reviews as $review): ?>
                        <div class="col-md-6 fade-in fade-in-delay">
                            <div class="card h-100 <?= $review['is_approved'] ? '' : 'border-warning' ?>">
                                <?php if (!$review['is_approved'] && $isAdmin): ?>
                                    <div class="card-header bg-warning text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div><i class="fas fa-exclamation-circle me-1"></i> Ожидает одобрения</div>
                                            <div>
                                                <a href="?version_id=<?= $versionId ?>&action=approve&review_id=<?= $review['id'] ?>" class="btn btn-sm btn-success me-1">
                                                    <i class="fas fa-check"></i> Одобрить
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-review-id="<?= $review['id'] ?>">
                                                    <i class="fas fa-times"></i> Отклонить
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <?php if ($review['avatar']): ?>
                                                <img src="<?= getUserAvatarUrl($review['avatar']) ?>" alt="Аватар" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="avatar-placeholder me-2 d-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                                                <div class="text-muted small"><?= formatDate($review['created_at']) ?></div>
                                            </div>
                                        </div>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <h5 class="card-title"><?= htmlspecialchars($review['title']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления отзыва -->
<?php if ($isLoggedIn && $canReview): ?>
<div class="modal fade" id="addReviewModal" tabindex="-1" aria-labelledby="addReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReviewModalLabel">Добавить отзыв</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-4 text-center">
                        <label for="rating" class="form-label d-block">Ваша оценка</label>
                        <div class="rating-input">
                            <input type="hidden" name="rating" id="rating" value="5">
                            <div class="rating-stars rating-selectable">
                                <i class="fas fa-star text-warning" data-rating="1"></i>
                                <i class="fas fa-star text-warning" data-rating="2"></i>
                                <i class="fas fa-star text-warning" data-rating="3"></i>
                                <i class="fas fa-star text-warning" data-rating="4"></i>
                                <i class="fas fa-star text-warning" data-rating="5"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="review_title" class="form-label">Заголовок отзыва</label>
                        <input type="text" class="form-control" id="review_title" name="review_title" placeholder="Введите заголовок отзыва" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="review_text" class="form-label">Текст отзыва</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="5" placeholder="Поделитесь своим опытом использования чита" required></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Ваш отзыв будет опубликован после проверки модератором.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="add_review" class="btn btn-primary">Отправить отзыв</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модальное окно для отклонения отзыва (для админов) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Отклонить отзыв</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm" action="" method="GET">
                    <input type="hidden" name="version_id" value="<?= $versionId ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="review_id" id="reject_review_id" value="">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Причина отклонения (необязательно)</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Укажите причину отклонения отзыва"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="submitReject">Отклонить отзыв</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.rating-stars {
    font-size: 1.25rem;
}

.rating-selectable .fa-star {
    cursor: pointer;
    padding: 0 2px;
    transition: transform 0.2s, color 0.2s;
}

.rating-selectable .fa-star:hover {
    transform: scale(1.2);
}

.rating-selectable .fa-star.selected,
.rating-selectable .fa-star:hover,
.rating-selectable .fa-star:hover ~ .fa-star {
    color: #ffc107 !important;
}

.rating-selectable .fa-star.active {
    color: #ffc107 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация селектора рейтинга
    const ratingStars = document.querySelectorAll('.rating-selectable .fa-star');
    const ratingInput = document.getElementById('rating');

    if (ratingStars.length && ratingInput) {
        // Устанавливаем начальный рейтинг
        const initialRating = parseInt(ratingInput.value) || 5;
        updateRatingUI(initialRating);

        // Обработчики событий для звезд
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                updateRatingUI(rating);
            });
        });

        // Обновление UI рейтинга
        function updateRatingUI(rating) {
            ratingStars.forEach(star => {
                const starRating = parseInt(star.dataset.rating);
                if (starRating <= rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
    }

    // Инициализация модального окна для отклонения отзыва
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reviewId = button.dataset.reviewId;
            document.getElementById('reject_review_id').value = reviewId;
        });

        // Обработчик для кнопки отклонения
        const submitRejectBtn = document.getElementById('submitReject');
        const rejectForm = document.getElementById('rejectForm');

        if (submitRejectBtn && rejectForm) {
            submitRejectBtn.addEventListener('click', function() {
                rejectForm.submit();
            });
        }
    }
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
