<?php

namespace TurboFrame\Http;

use Closure;
use TurboFrame\Core\Application;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];
    private array $middlewareGroups = [];
    private static ?Router $instance = null;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    public function post(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function options(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    public function any(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function match(array $methods, string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(array $methods, string $uri, Closure|array|string $action): Route
    {
        $uri = $this->prefixUri($uri);
        $middleware = $this->getGroupMiddleware();
        $namespace = $this->getGroupNamespace();

        $route = new Route($methods, $uri, $action, $middleware, $namespace);
        
        foreach ($methods as $method) {
            $this->routes[$method][] = $route;
        }

        return $route;
    }

    private function prefixUri(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix . '/' . ltrim($uri, '/');
    }

    private function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return $middleware;
    }

    private function getGroupNamespace(): string
    {
        $namespace = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['namespace'])) {
                $namespace = $group['namespace'];
            }
        }
        return $namespace;
    }

    public function dispatch(string $method, string $uri): Response
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = $uri === '' ? '/' : $uri;

        if (!isset($this->routes[$method])) {
            return $this->notFound();
        }

        foreach ($this->routes[$method] as $route) {
            $params = $route->match($uri);
            if ($params !== false) {
                return $this->runRoute($route, $params);
            }
        }

        return $this->notFound();
    }

    private function runRoute(Route $route, array $params): Response
    {
        $request = new Request();
        $request->setRouteParams($params);

        $middlewarePipeline = $this->buildMiddlewarePipeline($route->getMiddleware());

        $response = $middlewarePipeline($request, function($request) use ($route, $params) {
            return $this->executeRouteAction($route, $request, $params);
        });

        if (!$response instanceof Response) {
            if (is_array($response)) {
                return Response::json($response);
            }
            return Response::html((string) $response);
        }

        return $response;
    }

    private function buildMiddlewarePipeline(array $middlewareList): Closure
    {
        return function($request, $next) use ($middlewareList) {
            $pipeline = array_reduce(
                array_reverse($middlewareList),
                function($carry, $middleware) {
                    return function($request) use ($carry, $middleware) {
                        if (is_string($middleware)) {
                            $middleware = new $middleware();
                        }
                        return $middleware->handle($request, $carry);
                    };
                },
                $next
            );
            return $pipeline($request);
        };
    }

    private function executeRouteAction(Route $route, Request $request, array $params): mixed
    {
        $action = $route->getAction();

        if ($action instanceof Closure) {
            return $action($request, ...array_values($params));
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
        } elseif (is_string($action)) {
            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action);
            } else {
                $controller = $action;
                $method = '__invoke';
            }
        }

        $namespace = $route->getNamespace() ?: 'App\\Controllers\\';
        if (!str_starts_with($controller, '\\')) {
            $controller = $namespace . $controller;
        }

        $app = Application::getInstance();
        $controllerInstance = $app->container()->make($controller);

        return $controllerInstance->$method($request, ...array_values($params));
    }

    private function notFound(): Response
    {
        return Response::html($this->getErrorPage(404, 'Page Not Found'), 404);
    }

    private function getErrorPage(int $code, string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$message}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(90deg, #00d4ff, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5rem;
            color: #94a3b8;
            margin-top: 1rem;
        }
        .home-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 2rem;
            background: linear-gradient(90deg, #00d4ff, #7c3aed);
            color: #fff;
            text-decoration: none;
            border-radius: 2rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .home-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.4);
        }
        .turbo-badge {
            margin-top: 3rem;
            font-size: 0.875rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$code}</div>
        <div class="error-message">{$message}</div>
        <a href="/" class="home-link">Go Home</a>
        <div class="turbo-badge">⚡ Powered by TurboFrame</div>
    </div>
</body>
</html>
HTML;
    }

    public function name(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]->getUri();
        
        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . $key . '\??}/', $value, $uri);
        }

        return $uri;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
