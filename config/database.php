<?php

return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'turboframe',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_DATABASE'] ?? 'turboframe',
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => BASE_PATH . '/storage/database.sqlite',
            'prefix' => '',
        ],

        'mongodb' => [
            'driver' => 'mongodb',
            'host' => $_ENV['MONGODB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['MONGODB_PORT'] ?? '27017',
            'database' => $_ENV['MONGODB_DATABASE'] ?? 'turboframe',
            'username' => $_ENV['MONGODB_USERNAME'] ?? null,
            'password' => $_ENV['MONGODB_PASSWORD'] ?? null,
        ],
    ],

    'migrations' => 'migrations',
];
