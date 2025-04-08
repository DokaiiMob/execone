</main>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>О нас</h5>
                <p>EXECONE - ваш надежный поставщик читов для SAMP. Мы предлагаем лучшие решения с 2018 года.</p>
            </div>
            <div class="col-md-4">
                <h5>Разделы</h5>
                <ul class="list-unstyled">
                    <li><a href="/" class="text-light">Главная</a></li>
                    <li><a href="/subscription.php" class="text-light">Подписки</a></li>
                    <li><a href="/faq.php" class="text-light">FAQ</a></li>
                    <li><a href="/about.php" class="text-light">О читах</a></li>
                    <li><a href="/blog.php" class="text-light">Блог</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Контакты</h5>
                <p><i class="fas fa-envelope me-2"></i> support@execone.com</p>
                <p><i class="fas fa-comment me-2"></i> Discord: EXECONE#0001</p>
                <p><i class="fas fa-globe me-2"></i> Русский, English</p>
            </div>
        </div>
        <div class="mt-4 text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> EXECONE. Все права защищены.</p>
        </div>
    </div>
</footer>

<!-- Theme Switch JS -->
<script src="/assets/js/theme-switch.js"></script>
<!-- Notifications JS -->
<script src="/assets/js/notifications.js"></script>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/script.js"></script>
<?php if (isset($extraJs)): ?>
<?= $extraJs ?>
<?php endif; ?>
</body>
</html>
