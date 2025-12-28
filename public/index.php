<?php

define('TURBO_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));

// Load performance optimizations BEFORE starting session
if (file_exists(BASE_PATH . '/config/performance.php')) {
    require BASE_PATH . '/config/performance.php';
}

// Now start session with optimized settings
session_start();

// 1. Extreme Performance (Nitrous Mode)
$compiledPath = BASE_PATH . '/storage/nitrous/compiled.php';
if (file_exists($compiledPath)) {
    require $compiledPath;
    $autoloaderFound = true;
} else {
    // 2. Standard Performance (Autoloader)
    $autoloadPaths = [
        BASE_PATH . '/vendor/autoload.php',
    ];

    $autoloaderFound = false;
    foreach ($autoloadPaths as $autoloadPath) {
        if (file_exists($autoloadPath)) {
            require $autoloadPath;
            $autoloaderFound = true;
            break;
        }
    }

    if (!$autoloaderFound) {
        require BASE_PATH . '/src/Core/Autoloader.php';
        TurboFrame\Core\Autoloader::register();
    }
}

require BASE_PATH . '/src/helpers.php';

$app = TurboFrame\Core\Application::create();
$app->run();
