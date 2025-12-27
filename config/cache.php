<?php

return [
    'default' => 'opcache',

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => BASE_PATH . '/storage/cache',
        ],

        'opcache' => [
            'driver' => 'opcache',
            'preload' => true,
            'warmup_on_boot' => true,
        ],
    ],

    'prefix' => 'turbo_cache_',
    'ttl' => 3600,
];
