<?php
declare(strict_types=1);

$configFile = __DIR__ . '/../config/config.php';
$configPath = is_file($configFile)
    ? $configFile
    : __DIR__ . '/../config/config.example.php';
$config = require $configPath;

date_default_timezone_set($config['timezone'] ?? 'Europe/Paris');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name($config['session_name'] ?? 'AGENDA_COMMUNAL');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require __DIR__ . '/functions.php';
require __DIR__ . '/database.php';
require __DIR__ . '/calendar.php';
require __DIR__ . '/notifications.php';
require __DIR__ . '/backups.php';
require __DIR__ . '/pages.php';

migrate_database();
