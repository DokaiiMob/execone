<?php
require_once __DIR__ . '/config/init.php';

// Выходим из системы
$user = new User();
$user->logout();

// Перенаправляем на главную страницу
redirect('/');
