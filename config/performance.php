<?php

/**
 * Performance Optimization Bootstrap
 * This file contains critical performance optimizations
 */

// Enable OPcache optimizations
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status && isset($status['opcache_enabled']) && $status['opcache_enabled']) {
        // OPcache is enabled - framework will be FAST
        ini_set('opcache.enable', '1');
        ini_set('opcache.memory_consumption', '128');
        ini_set('opcache.interned_strings_buffer', '8');
        ini_set('opcache.max_accelerated_files', '10000');
        ini_set('opcache.revalidate_freq', '60');
        ini_set('opcache.fast_shutdown', '1');
    }
}

// Disable unnecessary features in production
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Optimize session handling (only if session not started yet)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

// Memory limit optimization
ini_set('memory_limit', '128M');

// Output buffering for better performance
if (!ob_get_level()) {
    ob_start();
}
