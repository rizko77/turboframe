<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Core\Application;
use TurboFrame\Http\Router;

class RouteListCommand extends Command
{
    protected string $signature = 'route:list';
    protected string $description = 'List all registered routes';

    public function handle(array $args): int
    {
        $this->info("📋 Registered Routes");
        $this->newLine();

        $app = Application::create();
        $app->boot();

        $router = $app->make(Router::class);
        $routes = $router->getRoutes();

        $rows = [];
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            if (!isset($routes[$method])) {
                continue;
            }

            foreach ($routes[$method] as $route) {
                $action = $route->getAction();

                if ($action instanceof \Closure) {
                    $actionName = 'Closure';
                } elseif (is_array($action)) {
                    $actionName = implode('@', $action);
                } else {
                    $actionName = $action;
                }

                $middleware = implode(', ', $route->getMiddleware()) ?: '-';
                $name = $route->getName() ?? '-';

                $rows[] = [
                    $method,
                    $route->getUri(),
                    $actionName,
                    $middleware,
                    $name,
                ];
            }
        }

        if (empty($rows)) {
            $this->warning("No routes registered.");
            return 0;
        }

        $this->table(
            ['Method', 'URI', 'Action', 'Middleware', 'Name'],
            $rows
        );

        $this->newLine();
        $this->line("Total: " . count($rows) . " routes");

        return 0;
    }
}
