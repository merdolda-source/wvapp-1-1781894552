<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('TEMPLATES_PATH', BASE_PATH . '/templates');

spl_autoload_register(function (string $class): void {
    foreach ([BASE_PATH . '/src/', BASE_PATH . '/src/Models/', BASE_PATH . '/src/Support/'] as $dir) {
        $file = $dir . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

Config::load(BASE_PATH . '/.env');

date_default_timezone_set('Europe/Istanbul');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', Config::get('APP_DEBUG', '0') === '1' ? '1' : '0');
