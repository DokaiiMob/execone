<?php
$pageTitle = 'FAQ - Чит для SAMP';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container py-5">
    <!-- Заголовок страницы -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Часто задаваемые вопросы</h1>
        <p class="lead">Ответы на самые популярные вопросы о нашем чите</p>
    </div>

    <!-- FAQ аккордеон -->
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="accordion" id="faqAccordion">
                <!-- Раздел "Общие вопросы" -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingGeneral">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="true" aria-controls="collapseGeneral">
                            Общие вопросы
                        </button>
                    </h2>
                    <div id="collapseGeneral" class="accordion-collapse collapse show" aria-labelledby="headingGeneral" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="mb-4">
                                <h5>Что такое чит для SAMP?</h5>
                                <p>Чит для SAMP (San Andreas Multiplayer) — это программа, которая добавляет дополнительные функции в игру, давая игроку преимущества. Наш чит предоставляет различные возможности, такие как аимбот, wallhack, speedhack и многие другие.</p>
                            </div>

                            <div class="mb-4">
                                <h5>Безопасен ли чит? Могут ли меня забанить?</h5>
                                <p>Наш чит разработан с учетом всех современных систем анти-чит. Мы используем продвинутые методы обхода защиты, что делает риск бана минимальным. Однако, для максимальной безопасности мы рекомендуем:</p>
                                <ul>
                                    <li>Не использовать слишком агрессивные настройки аимбота и других визуальных функций</li>
                                    <li>Не хвастаться использованием чита в игровом чате</li>
                                    <li>Регулярно обновлять чит до последней версии</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5>На каких версиях SAMP работает чит?</h5>
                                <p>Наш чит совместим со всеми популярными версиями SAMP, включая 0.3.7, 0.3.DL и 0.3.7 R1, R2, R3, R4. Мы регулярно обновляем чит для поддержки новых версий SAMP.</p>
                            </div>

                            <div>
                                <h5>Какие функции включены в чит?</h5>
                                <p>Наш чит предлагает широкий спектр функций, включая:</p>
                                <ul>
                                    <li>Аимбот (автоматическое наведение прицела)</li>
                                    <li>WallHack (видеть игроков сквозь стены)</li>
                                    <li>SpeedHack (увеличение скорости передвижения)</li>
                                    <li>TriggerBot (автоматическая стрельба при наведении)</li>
                                    <li>NoRecoil (отсутствие отдачи оружия)</li>
                                    <li>ESP (отображение дополнительной информации об игроках)</li>
                                    <li>Anti-Stun (защита от оглушения)</li>
                                    <li>И многие другие</li>
                                </ul>
                                <p>Набор доступных функций зависит от выбранного тарифного плана.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Раздел "Технические вопросы" -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTechnical">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTechnical" aria-expanded="false" aria-controls="collapseTechnical">
                            Технические вопросы
                        </button>
                    </h2>
                    <div id="collapseTechnical" class="accordion-collapse collapse" aria-labelledby="headingTechnical" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="mb-4">
                                <h5>Какие системные требования у чита?</h5>
                                <p>Наш чит имеет минимальное влияние на производительность игры. Для комфортной работы рекомендуются следующие характеристики:</p>
                                <ul>
                                    <li>Операционная система: Windows 7/8/10/11 (64-бит)</li>
                                    <li>Процессор: Intel Core i3 или аналогичный AMD</li>
                                    <li>Оперативная память: 4 ГБ</li>
                                    <li>Видеокарта: совместимая с DirectX 9</li>
                                    <li>Свободное место на диске: 50 МБ</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5>Как установить чит?</h5>
                                <p>Установка чита очень проста:</p>
                                <ol>
                                    <li>Скачайте файл чита из личного кабинета</li>
                                    <li>Распакуйте архив в любую папку</li>
                                    <li>Запустите файл <code>launcher.exe</code> от имени администратора</li>
                                    <li>В открывшемся окне введите свой логин и пароль от сайта</li>
                                    <li>Нажмите кнопку "Запустить"</li>
                                    <li>Запустите игру SAMP</li>
                                </ol>
                                <p>Чит автоматически подключится к игре. Для открытия меню чита нажмите клавишу <kbd>INSERT</kbd>.</p>
                            </div>

                            <div class="mb-4">
                                <h5>Что делать, если чит не работает?</h5>
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

                            <div>
                                <h5>Почему антивирус блокирует чит?</h5>
                                <p>Антивирусные программы часто помечают читы как потенциально опасное ПО, так как они используют методы внедрения кода в игру. Наш чит абсолютно безопасен для вашего компьютера. Для корректной работы рекомендуется добавить папку с читом в исключения антивируса или временно отключить антивирус перед запуском чита.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Раздел "Подписка и оплата" -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSubscription">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSubscription" aria-expanded="false" aria-controls="collapseSubscription">
                            Подписка и оплата
                        </button>
                    </h2>
                    <div id="collapseSubscription" class="accordion-collapse collapse" aria-labelledby="headingSubscription" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="mb-4">
                                <h5>Какие тарифные планы доступны?</h5>
                                <p>У нас есть три тарифных плана:</p>
                                <ul>
                                    <li><strong>Базовый</strong> - включает основные функции чита, такие как аимбот, wallhack и speedhack</li>
                                    <li><strong>Премиум</strong> - включает все функции базового тарифа, а также дополнительные, такие как anti-stun, ESP и triggerbot</li>
                                    <li><strong>VIP</strong> - включает все функции премиум-тарифа, а также эксклюзивные возможности, такие как доступ к бета-версиям и приоритетную поддержку</li>
                                </ul>
                                <p>Подробное описание функций каждого тарифа доступно на странице <a href="/subscription.php">подписок</a>.</p>
                            </div>

                            <div class="mb-4">
                                <h5>Как оплатить подписку?</h5>
                                <p>Мы предлагаем несколько способов оплаты:</p>
                                <ul>
                                    <li>Банковская карта (Visa, MasterCard)</li>
                                    <li>QIWI кошелек</li>
                                    <li>ЮMoney (бывший Яндекс.Деньги)</li>
                                    <li>Криптовалюта (Bitcoin, Ethereum)</li>
                                </ul>
                                <p>Для оплаты перейдите на <a href="/subscription.php">страницу подписок</a>, выберите подходящий тарифный план и способ оплаты, затем следуйте инструкциям платежной системы.</p>
                            </div>

                            <div class="mb-4">
                                <h5>Как продлить подписку?</h5>
                                <p>Продлить подписку очень просто:</p>
                                <ol>
                                    <li>Войдите в свой личный кабинет</li>
                                    <li>Перейдите в раздел "Подписки"</li>
                                    <li>Выберите желаемый тарифный план и нажмите кнопку "Продлить"</li>
                                    <li>Выберите способ оплаты и следуйте инструкциям</li>
                                </ol>
                                <p>После успешной оплаты ваша подписка будет автоматически продлена. Если у вас уже есть активная подписка, то новый срок будет добавлен к существующему.</p>
                            </div>

                            <div>
                                <h5>Можно ли отменить подписку?</h5>
                                <p>Да, вы можете отменить подписку в любое время. Для этого:</p>
                                <ol>
                                    <li>Войдите в свой личный кабинет</li>
                                    <li>Перейдите в раздел "Подписки"</li>
                                    <li>Нажмите кнопку "Отменить подписку"</li>
                                </ol>
                                <p>Обратите внимание, что при отмене подписки вы сможете пользоваться читом до конца оплаченного периода, но автоматическое продление подписки будет отключено.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Раздел "Аккаунт и безопасность" -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingAccount">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAccount" aria-expanded="false" aria-controls="collapseAccount">
                            Аккаунт и безопасность
                        </button>
                    </h2>
                    <div id="collapseAccount" class="accordion-collapse collapse" aria-labelledby="headingAccount" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="mb-4">
                                <h5>Как создать аккаунт?</h5>
                                <p>Для создания аккаунта выполните следующие шаги:</p>
                                <ol>
                                    <li>Нажмите на кнопку "Регистрация" в верхнем меню</li>
                                    <li>Заполните форму регистрации, указав логин, email и пароль</li>
                                    <li>Примите правила использования сайта и политику конфиденциальности</li>
                                    <li>Нажмите на кнопку "Зарегистрироваться"</li>
                                    <li>Если требуется, подтвердите ваш email, перейдя по ссылке из письма</li>
                                </ol>
                                <p>После этого вы сможете войти в свой аккаунт, используя указанные логин/email и пароль.</p>
                            </div>

                            <div class="mb-4">
                                <h5>Как изменить пароль?</h5>
                                <p>Для изменения пароля:</p>
                                <ol>
                                    <li>Войдите в свой аккаунт</li>
                                    <li>Перейдите в раздел "Профиль"</li>
                                    <li>Выберите вкладку "Безопасность"</li>
                                    <li>Введите текущий пароль, а затем новый пароль и его подтверждение</li>
                                    <li>Нажмите кнопку "Изменить пароль"</li>
                                </ol>
                            </div>

                            <div>
                                <h5>Безопасны ли мои персональные данные?</h5>
                                <p>Мы серьезно относимся к безопасности ваших данных. Все персональные данные хранятся в зашифрованном виде, а пароли хешируются с использованием современных алгоритмов. Мы не храним данные ваших банковских карт, а все платежи обрабатываются надежными платежными системами.</p>
                                <p>Подробнее о том, как мы обрабатываем ваши данные, вы можете прочитать в нашей <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Политике конфиденциальности</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Раздел "Контакты и поддержка" -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSupport">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSupport" aria-expanded="false" aria-controls="collapseSupport">
                            Контакты и поддержка
                        </button>
                    </h2>
                    <div id="collapseSupport" class="accordion-collapse collapse" aria-labelledby="headingSupport" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="mb-4">
                                <h5>Как связаться с технической поддержкой?</h5>
                                <p>Вы можете связаться с нашей технической поддержкой несколькими способами:</p>
                                <ul>
                                    <li>По электронной почте: <a href="mailto:support@example.com">support@example.com</a></li>
                                    <li>Через форму обратной связи на сайте</li>
                                    <li>В нашей группе в <a href="https://t.me/example" target="_blank">Telegram</a></li>
                                    <li>На канале <a href="https://discord.gg/example" target="_blank">Discord</a></li>
                                </ul>
                                <p>Время ответа составляет от 1 часа до 24 часов в зависимости от загруженности службы поддержки.</p>
                            </div>

                            <div>
                                <h5>Как быстро решаются проблемы?</h5>
                                <p>Мы стремимся решать все проблемы максимально оперативно. Время решения зависит от сложности проблемы:</p>
                                <ul>
                                    <li>Простые вопросы по использованию чита - от 1 до 3 часов</li>
                                    <li>Технические проблемы средней сложности - от 3 до 12 часов</li>
                                    <li>Сложные технические проблемы - от 12 до 48 часов</li>
                                </ul>
                                <p>Пользователи с VIP подпиской получают приоритетную поддержку и их проблемы решаются в первую очередь.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Дополнительный блок с призывом к действию -->
    <div class="row mt-5">
        <div class="col-lg-10 mx-auto">
            <div class="card bg-primary text-white">
                <div class="card-body p-4 text-center">
                    <h3 class="mb-3">Остались вопросы?</h3>
                    <p class="lead mb-4">Если вы не нашли ответ на свой вопрос, свяжитесь с нашей службой поддержки, и мы обязательно вам поможем.</p>
                    <a href="mailto:support@example.com" class="btn btn-light btn-lg">Написать в поддержку</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с политикой конфиденциальности -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Политика конфиденциальности</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Общие положения</h5>
                <p>1.1. Настоящая Политика конфиденциальности определяет порядок обработки и защиты персональных данных Пользователей.</p>
                <p>1.2. Используя сайт, Пользователь соглашается с условиями данной Политики.</p>

                <h5>2. Сбор и использование персональных данных</h5>
                <p>2.1. Администрация сайта собирает следующие персональные данные:</p>
                <ul>
                    <li>Имя пользователя</li>
                    <li>Адрес электронной почты</li>
                    <li>IP-адрес</li>
                    <li>Информация о браузере и устройстве</li>
                </ul>
                <p>2.2. Персональные данные используются для:</p>
                <ul>
                    <li>Предоставления доступа к сайту и его функциям</li>
                    <li>Улучшения работы сайта</li>
                    <li>Коммуникации с Пользователем</li>
                </ul>

                <h5>3. Защита персональных данных</h5>
                <p>3.1. Администрация сайта принимает все необходимые меры для защиты персональных данных Пользователей.</p>
                <p>3.2. Доступ к персональным данным имеют только уполномоченные сотрудники Администрации.</p>

                <h5>4. Права Пользователя</h5>
                <p>4.1. Пользователь имеет право на получение информации о своих персональных данных.</p>
                <p>4.2. Пользователь имеет право на удаление своих персональных данных, направив соответствующий запрос Администрации.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
