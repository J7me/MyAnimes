<?php
// init.php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        //'cookie_secure' => true, // включи если HTTPS
        'use_strict_mode' => true,
    ]);
}

// Подключаем базу и хелперы
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';   // middleware и CSRF (ниже)
require_once __DIR__ . '/helpers.php';// вспомогательные функции (ниже)
 
