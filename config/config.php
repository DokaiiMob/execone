<?php
/**
 * Конфигурационный файл сайта для чита в SAMP
 */

return array (
  'site' =>
  array (
    'name' => 'EXECONE',
    'description' => 'Портал для загрузки и управления читами для SAMP',
    'url' => 'http://localhost',
    'version' => '1.0.0',
    'debug' => true,
  ),
  'database' =>
  array (
    'driver' => 'mysql', // либо mysql, либо sqlite
    'sqlite' =>
    array (
      'path' => __DIR__ . '/../database/database.sqlite',
    ),
    'mysql' =>
    array (
      'host' => 'MySQL-5.7',
      'database' => 'samp_cheat_db',
      'username' => 'root',
      'password' => '',
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
    ),
  ),
  'auth' =>
  array (
    'session_lifetime' => 43200,
    'password_min_length' => 8,
    'require_email_verification' => true,
  ),
  'subscription' =>
  array (
    'plans' =>
    array (
      'basic' =>
      array (
        'name' => 'Базовая',
        'price' => 299,
        'duration' => 30,
        'features' =>
        array (
          0 => 'Базовые функции чита',
          1 => 'Поддержка через форум',
        ),
      ),
      'premium' =>
      array (
        'name' => 'Премиум',
        'price' => 599,
        'duration' => 30,
        'features' =>
        array (
          0 => 'Все функции чита',
          1 => 'Приоритетная поддержка',
          2 => 'Ранний доступ к обновлениям',
        ),
      ),
      'vip' =>
      array (
        'name' => 'VIP',
        'price' => 1499,
        'duration' => 30,
        'features' =>
        array (
          0 => 'Все функции чита',
          1 => 'Поддержка 24/7',
          2 => 'Эксклюзивные функции',
          3 => 'Доступ к бета-версиям',
        ),
      ),
    ),
  ),
  'uploads' =>
  array (
    'cheat_files' =>
    array (
      'path' => __DIR__ . '/../uploads/cheats/',
      'allowed_extensions' =>
      array (
        0 => 'exe',
        1 => 'dll',
        2 => 'zip',
        3 => 'rar',
      ),
      'max_size' => 52428800,
    ),
    'user_avatars' =>
    array (
      'path' => __DIR__ . '/../uploads/avatars/',
      'allowed_extensions' =>
      array (
        0 => 'jpg',
        1 => 'jpeg',
        2 => 'png',
        3 => 'gif',
      ),
      'max_size' => 2097152,
    ),
  ),
);
