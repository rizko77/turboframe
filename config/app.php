<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'TurboFrame',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:7000',
    'key' => $_ENV['APP_KEY'] ?? null,

    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
    'fallback_locale' => 'en',

    'providers' => [
    ],

    'aliases' => [
    ],
];
