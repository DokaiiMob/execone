<?php
$pageTitle = 'Главная - Чит для SAMP';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="hero-title">Лучший чит для SAMP</h1>
                <p class="hero-text">Получите преимущество в игре с нашим функциональным и безопасным читом. Оптимальная производительность и регулярные обновления гарантируют отсутствие банов.</p>
                <?php if (!$user->isLoggedIn()): ?>
                <div class="d-flex gap-3">
                    <a href="/register.php" class="btn btn-light btn-lg">Зарегистрироваться</a>
                    <a href="/login.php" class="btn btn-outline-light btn-lg">Войти</a>
                </div>
                <?php else: ?>
                <a href="/downloads.php" class="btn btn-light btn-lg">Скачать чит</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                <img src="/assets/images/hero-image.png" alt="SAMP Чит" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<div class="container">
    <section class="mb-5">
        <h2 class="section-title">Функции чита</h2>
        <p class="section-subtitle">Наш чит для SAMP включает множество полезных функций, которые помогут вам стать лучшим игроком</p>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-crosshairs fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">Аимбот</h5>
                        <p class="card-text">Автоматически наводит прицел на ближайшего противника, повышая точность стрельбы и снижая время реакции.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-eye fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">WallHack</h5>
                        <p class="card-text">Позволяет видеть противников сквозь стены и другие препятствия, давая стратегическое преимущество в бою.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-tachometer-alt fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">SpeedHack</h5>
                        <p class="card-text">Увеличивает скорость передвижения вашего персонажа, позволяя быстрее достигать цели и уходить от преследования.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-bullseye fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">TriggerBot</h5>
                        <p class="card-text">Автоматически стреляет при наведении на противника, повышая эффективность и скорость реакции в перестрелках.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-rocket fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">NoRecoil</h5>
                        <p class="card-text">Устраняет отдачу оружия, делая стрельбу более точной и контролируемой даже при длительной очереди.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">Анти-бан система</h5>
                        <p class="card-text">Продвинутая система защиты от обнаружения, минимизирующая риск получения бана даже при активном использовании чита.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5 py-5 bg-light">
        <div class="container">
            <h2 class="section-title">API для разработчиков</h2>
            <p class="section-subtitle">Интегрируйте возможности нашей платформы в свои приложения и сервисы</p>

            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body">
                            <h4 class="mb-3">Мощный REST API</h4>
                            <p>Наш API предоставляет разработчикам доступ к функциям платформы:</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent">🔐 Авторизация пользователей</li>
                                <li class="list-group-item bg-transparent">👤 Управление профилем</li>
                                <li class="list-group-item bg-transparent">💰 Работа с подписками</li>
                                <li class="list-group-item bg-transparent">📥 Доступ к версиям чита</li>
                                <li class="list-group-item bg-transparent">🔔 Уведомления в реальном времени</li>
                            </ul>
                            <a href="/api-docs.php" class="btn btn-primary mt-3">
                                <i class="fas fa-code me-2"></i> Изучить документацию API
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-dark text-light">
                            <span class="badge bg-success me-2">GET</span>/api/cheat/versions
                        </div>
                        <div class="card-body" style="background-color: #f8f9fa;">
                            <pre class="mb-0" style="background-color: #f8f9fa;"><code>{
  "success": true,
  "message": "Cheat versions retrieved successfully",
  "data": {
    "versions": [
      {
        "id": 1,
        "version": "2.0.0",
        "description": "Новая версия с улучшенными функциями",
        "required_plan": "vip",
        "required_plan_name": "VIP",
        "is_available": true
      },
      ...
    ]
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="section-title">Наши преимущества</h2>
        <p class="section-subtitle">Почему стоит выбрать именно наш чит для SAMP</p>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="d-flex align-items-start mb-4">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-sync-alt fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5>Регулярные обновления</h5>
                        <p>Мы постоянно обновляем наш чит, добавляя новые функции и улучшая существующие, а также адаптируя его под последние версии SAMP.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-start mb-4">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-shield-alt fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5>Безопасность</h5>
                        <p>Наш чит разработан с учетом всех современных механизмов защиты, что минимизирует риск обнаружения и бана вашего аккаунта.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-start mb-4">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-cogs fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5>Гибкие настройки</h5>
                        <p>Все функции чита можно настроить под себя, выбрав оптимальные параметры для вашего стиля игры и конфигурации компьютера.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-start mb-4">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-headset fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5>Техническая поддержка</h5>
                        <p>Наша команда поддержки готова помочь вам с любыми вопросами по установке, настройке и использованию чита 24/7.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="section-title">Тарифные планы</h2>
        <p class="section-subtitle">Выберите подходящий тарифный план и получите доступ к читу уже сегодня</p>

        <div class="row g-4">
            <?php
            $subscriptionModel = new Subscription();
            $plans = $subscriptionModel->getPlans();

            foreach ($plans as $planType => $plan) {
                $featured = ($planType === 'premium') ? 'featured' : '';
            ?>
            <div class="col-lg-4">
                <div class="card subscription-card <?= $featured ?>">
                    <div class="card-header">
                        <h4 class="mb-0"><?= $plan['name'] ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="subscription-price"><?= formatPrice($plan['price']) ?></div>
                            <div class="subscription-period">на <?= $plan['duration'] ?> дней</div>
                        </div>
                        <ul class="subscription-features mb-4">
                            <?php foreach ($plan['features'] as $feature): ?>
                            <li class="text-center"><i class="fas fa-check"></i> <?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center">
                            <?php if (!$user->isLoggedIn()): ?>
                            <a href="/register.php" class="btn btn-primary">Зарегистрироваться</a>
                            <?php else: ?>
                            <a href="/subscription.php?plan=<?= $planType ?>" class="btn btn-primary">Купить подписку</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="section-title">Отзывы пользователей</h2>
        <p class="section-subtitle">Что говорят о нашем чите реальные пользователи</p>

        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/assets/images/avatar1.jpg" alt="Аватар" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-0">Александр</h5>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text">"Использую чит уже более полугода, ни разу не было бана. Функционал отличный, особенно нравится возможность тонкой настройки. Рекомендую VIP подписку, там функций гораздо больше, чем в базовой версии."</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/assets/images/avatar2.jpg" alt="Аватар" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-0">Дмитрий</h5>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text">"Поначалу был скептически настроен, но после недели использования мое мнение изменилось. Чит работает стабильно, нет лагов и вылетов. Техподдержка отвечает быстро, помогли с настройкой под мой компьютер."</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="/assets/images/avatar3.jpg" alt="Аватар" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-0">Максим</h5>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text">"Лучший чит для SAMP из всех, что я пробовал. Функционал на высоте, особенно порадовал аимбот и wallhack. Обновления выходят регулярно, разработчики прислушиваются к сообществу и добавляют новые функции."</p>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Предыдущий</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Следующий</span>
            </button>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="section-title">Часто задаваемые вопросы</h2>
        <p class="section-subtitle">Ответы на самые популярные вопросы о нашем чите</p>

        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                        Как установить чит?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Установка чита очень проста:</p>
                        <ol>
                            <li>Скачайте файл чита из личного кабинета</li>
                            <li>Распакуйте архив в любую папку</li>
                            <li>Запустите файл <code>launcher.exe</code></li>
                            <li>В открывшемся окне введите свой логин и пароль от сайта</li>
                            <li>Нажмите кнопку "Запустить"</li>
                            <li>Запустите игру SAMP</li>
                        </ol>
                        <p>Чит автоматически подключится к игре. Для открытия меню чита нажмите клавишу <kbd>INSERT</kbd>.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                        Безопасен ли чит? Могут ли меня забанить?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Наш чит разработан с учетом всех современных систем анти-чит. Мы используем продвинутые методы обхода защиты, что делает риск бана минимальным.</p>
                        <p>Тем не менее, мы рекомендуем:</p>
                        <ul>
                            <li>Не использовать слишком агрессивные настройки аимбота и других визуальных функций</li>
                            <li>Не хвастаться использованием чита в игровом чате</li>
                            <li>Регулярно обновлять чит до последней версии</li>
                        </ul>
                        <p>При соблюдении этих простых правил вероятность бана практически нулевая.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                        Какие системные требования у чита?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Наш чит имеет минимальное влияние на производительность игры. Для комфортной работы рекомендуются следующие характеристики:</p>
                        <ul>
                            <li>Операционная система: Windows 7/8/10/11 (64-бит)</li>
                            <li>Процессор: Intel Core i3 или аналогичный AMD</li>
                            <li>Оперативная память: 4 ГБ</li>
                            <li>Видеокарта: совместимая с DirectX 9</li>
                            <li>Свободное место на диске: 50 МБ</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading4">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                        Как продлить подписку?
                    </button>
                </h2>
                <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Продлить подписку очень просто:</p>
                        <ol>
                            <li>Войдите в свой личный кабинет</li>
                            <li>Перейдите в раздел "Подписки"</li>
                            <li>Выберите желаемый тарифный план и нажмите кнопку "Продлить"</li>
                            <li>Выберите способ оплаты и следуйте инструкциям</li>
                        </ol>
                        <p>После успешной оплаты ваша подписка будет автоматически продлена. Если у вас уже есть активная подписка, то новый срок будет добавлен к существующему.</p>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading5">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                        Что делать, если чит не работает?
                    </button>
                </h2>
                <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <p>Если у вас возникли проблемы с работой чита, выполните следующие действия:</p>
                        <ol>
                            <li>Убедитесь, что у вас активная подписка</li>
                            <li>Проверьте, что вы используете последнюю версию чита</li>
                            <li>Запустите чит от имени администратора</li>
                            <li>Временно отключите антивирус и брандмауэр</li>
                            <li>Проверьте, что у вас установлены все необходимые компоненты (DirectX, Visual C++ Redistributable)</li>
                        </ol>
                        <p>Если проблема не решена, обратитесь в нашу службу поддержки через личный кабинет или на почту support@example.com. Мы обязательно поможем вам решить проблему.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
