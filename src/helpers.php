<?php

use TurboFrame\Core\Application;
use TurboFrame\View\Engine;
use TurboFrame\Http\Response;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $app = Application::getInstance();
        if ($abstract === null) {
            return $app;
        }
        return $app->make($abstract);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Application::getInstance()->env($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Application::getInstance()->config($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        $engine = new Engine();
        return $engine->render($name, $data);
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200): Response
    {
        return Response::make($content, $status);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = Application::getInstance()->env('APP_URL', 'http://localhost:7000');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url(ltrim($path, '/'));
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $router = app(\TurboFrame\Http\Router::class);
        return $router->url($name, $params);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('session')) {
    function session(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('logger')) {
    function logger(): \TurboFrame\Log\Logger
    {
        return app(\TurboFrame\Log\Logger::class);
    }
}

if (!function_exists('cache')) {
    function cache(): \TurboFrame\Cache\OPCacheManager
    {
        return app(\TurboFrame\Cache\OPCacheManager::class);
    }
}

if (!function_exists('db')) {
    function db(): \TurboFrame\Database\Connection
    {
        return app(\TurboFrame\Database\Connection::class);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre style="background:#1a1a2e;color:#00d4ff;padding:1rem;border-radius:0.5rem;margin:0.5rem;font-family:monospace;">';
            var_dump($var);
            echo '</pre>';
        }
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre style="background:#1a1a2e;color:#00d4ff;padding:1rem;border-radius:0.5rem;margin:0.5rem;font-family:monospace;">';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return Application::getInstance()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return base_path('public/' . ltrim($path, '/'));
    }
}



if (!function_exists('now')) {
    function now(): \DateTime
    {
        return new \DateTime();
    }
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}
