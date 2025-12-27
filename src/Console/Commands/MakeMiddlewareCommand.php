<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $signature = 'make:middleware {name}';
    protected string $description = 'Create a new middleware class';

    public function handle(array $args): int
    {
        $name = $this->argument($args, 0);

        if (!$name) {
            $this->error("Please provide a middleware name.");
            $this->line("Usage: php lambo make:middleware AuthMiddleware");
            return 1;
        }

        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $directory = BASE_PATH . '/application/Middleware';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $name . '.php';

        if (file_exists($filePath) && !$this->hasOption($args, 'force')) {
            $this->error("Middleware already exists: {$filePath}");
            return 1;
        }

        $content = $this->generateMiddleware($name);
        file_put_contents($filePath, $content);

        $this->success("✅ Middleware created successfully!");
        $this->line("   {$filePath}");

        return 0;
    }

    private function generateMiddleware(string $className): string
    {
        return <<<PHP
<?php

namespace App\Middleware;

use Closure;
use TurboFrame\Http\Request;
use TurboFrame\Http\Response;

class {$className}
{
    public function handle(Request \$request, Closure \$next): Response
    {
        return \$next(\$request);
    }
}
PHP;
    }
}
