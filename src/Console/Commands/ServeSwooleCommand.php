<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Core\Application;

class ServeSwooleCommand extends Command
{
    protected string $signature = 'serve:swoole';
    protected string $description = 'Start TurboFrame with Swoole high-performance server';

    public function handle(array $args): int
    {
        if (!extension_loaded('swoole')) {
            $this->error("❌ Swoole extension not found! Please install it to use this command.");
            $this->info("   Hint: 'pecl install swoole' or check your php.ini");
            return 1;
        }

        $host = $this->hasOption($args, 'host') ? $this->getOptionValue($args, 'host') : '127.0.0.1';
        $port = $this->hasOption($args, 'port') ? (int)$this->getOptionValue($args, 'port') : 8000;

        $this->info("🔥 TurboFrame Swoole Server starting on http://{$host}:{$port}");
        $this->line("🚀 Optimized with Persistent Memory & JIT");

        $http = new \Swoole\Http\Server($host, $port);

        // Pre-boot application (Persistent Instance)
        $app = Application::getInstance();
        $app->boot();

        $http->on("start", function ($server) use ($host, $port) {
            echo "✨ Swoole worker started. Press Ctrl+C to stop.\n";
        });

        $http->on("request", function ($request, $response) use ($app) {
            // 1. Populate Globals (Bridge) for compatibility
            $_GET = $request->get ?? [];
            $_POST = $request->post ?? [];
            $_FILES = $request->files ?? [];
            $_COOKIE = $request->cookie ?? [];
            $_SERVER = array_change_key_case($request->server, CASE_UPPER);

            // 2. Handle Request through TurboFrame Core
            $tfResponse = $app->handle(
                $request->server['request_method'],
                $request->server['request_uri']
            );

            // 3. Send Response back to Swoole
            $response->status($tfResponse->getStatusCode());
            foreach ($tfResponse->getHeaders() as $name => $value) {
                $response->header($name, $value);
            }
            
            $response->end($tfResponse->getBody());
        });

        $http->start();

        return 0;
    }
}
