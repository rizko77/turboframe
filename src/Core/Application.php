<?php

namespace TurboFrame\Core;

use TurboFrame\Cache\OPCacheManager;
use TurboFrame\Database\Connection;
use TurboFrame\Log\Logger;
use TurboFrame\Http\Router;

class Application
{
    private static ?Application $instance = null;
    private Container $container;
    private array $config = [];
    private bool $booted = false;
    private float $startTime;
    private static ?array $envCache = null;
    private static bool $stateLoaded = false;

    private function __construct()
    {
        self::$instance = $this;
        $this->startTime = defined('TURBO_START') ? TURBO_START : microtime(true);
        $this->container = new Container();
        
        if (!$this->loadFromState()) {
            $this->loadEnvironment();
            $this->loadConfiguration();
        }
        
        $this->registerCoreServices();
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function create(): Application
    {
        return self::getInstance();
    }

    private function loadEnvironment(): void
    {
        // Use static cache to avoid re-parsing .env
        if (self::$envCache !== null) {
            foreach (self::$envCache as $key => $value) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
            return;
        }

        $envFile = BASE_PATH . '/.env';
        if (!file_exists($envFile)) {
            self::$envCache = [];
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::$envCache = [];
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $_ENV[$key] = $value;
                putenv("$key=$value");
                self::$envCache[$key] = $value;
            }
        }
    }

    private static ?array $configCache = null;

    private function loadConfiguration(): void
    {
        // Use static cache for config files
        if (self::$configCache !== null) {
            $this->config = self::$configCache;
            return;
        }

        $configPath = BASE_PATH . '/config';
        if (!is_dir($configPath)) {
            self::$configCache = [];
            return;
        }

        foreach (glob($configPath . '/*.php') as $file) {
            $name = basename($file, '.php');
            $this->config[$name] = require $file;
        }
        
        self::$configCache = $this->config;
    }

    private function registerCoreServices(): void
    {
        $this->container->singleton(Application::class, fn() => $this);
        $this->container->singleton(Router::class, fn() => new Router());
        $this->container->singleton(Logger::class, fn() => new Logger());
        $this->container->singleton(OPCacheManager::class, fn() => new OPCacheManager());
        
        $this->container->singleton(Connection::class, function() {
            return new Connection($this->getDatabaseConfig());
        });
    }

    private function getDatabaseConfig(): array
    {
        return [
            'driver' => $this->env('DB_CONNECTION', 'mysql'),
            'host' => $this->env('DB_HOST', '127.0.0.1'),
            'port' => $this->env('DB_PORT', '3306'),
            'database' => $this->env('DB_DATABASE', 'turboframe'),
            'username' => $this->env('DB_USERNAME', 'root'),
            'password' => $this->env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadRoutes();
        
        if ($this->env('CACHE_DRIVER') === 'opcache') {
            $this->container->make(OPCacheManager::class)->warmUp();
        }

        $this->booted = true;
    }

    private function loadRoutes(): void
    {
        $routesPath = BASE_PATH . '/routes';
        $router = $this->container->make(Router::class);
        
        $routeFiles = ['site.php', 'api.php'];
        foreach ($routeFiles as $file) {
            $path = $routesPath . '/' . $file;
            if (file_exists($path)) {
                (function($router) use ($path) {
                    require $path;
                })($router);
            }
        }
    }

    public function handle(?string $method = null, ?string $uri = null): \TurboFrame\Http\Response
    {
        $this->boot();
        
        $router = $this->container->make(Router::class);
        $response = $router->dispatch(
            $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri ?? $_SERVER['REQUEST_URI'] ?? '/'
        );

        return $response;
    }

    public function run(): void
    {
        $this->handle()->send();
    }

    public function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = $this->config;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    public function getExecutionTime(): float
    {
        return (microtime(true) - $this->startTime) * 1000;
    }

    public function isDebug(): bool
    {
        return filter_var($this->env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function basePath(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }

    private function loadFromState(): bool
    {
        if (self::$stateLoaded) return true;

        $statePath = BASE_PATH . '/storage/nitrous/state.php';
        if (file_exists($statePath)) {
            $state = require $statePath;
            
            // Restore Env
            foreach ($state['env'] as $key => $value) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
            self::$envCache = $state['env'];

            // Restore Config
            $this->config = $state['config'];
            self::$configCache = $this->config;

            self::$stateLoaded = true;
            return true;
        }

        return false;
    }
}
