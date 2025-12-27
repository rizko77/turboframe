<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Core\Application;

class ServeCommand extends Command
{
    protected string $signature = 'serve';
    protected string $description = 'Start the Lambo development server';

    public function handle(array $args): int
    {
        $host = $this->option($args, 'host', '127.0.0.1');
        $port = $this->option($args, 'port', $this->getDefaultPort());

        $this->info("Welcome to TurboFrame PHP");
        $this->newLine();
        $this->success("Starting Server...");
        $this->success("Developer by Rizko Imsar");
        $this->success("GitHub: rizko77");
        $this->newLine();
        $this->warning("Dengan PHP anda bisa beli lambo wkwk. Hidup PHP!");
        $this->line(" Local:   http://{$host}:{$port}");
        $this->line("  Press Ctrl+C to stop the server");

        $docRoot = BASE_PATH;
        $routerScript = $this->createRouterScript();

        $command = sprintf(
            'php -S %s:%s -t %s %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($docRoot),
            escapeshellarg($routerScript)
        );


        $this->newLine();

        passthru($command);

        @unlink($routerScript);

        return 0;
    }

    private function getDefaultPort(): string
    {
        if (file_exists(BASE_PATH . '/.env')) {
            $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'TURBO_PORT=')) {
                    return trim(substr($line, 11));
                }
            }
        }
        return '7000';
    }

    private function createRouterScript(): string
    {
        $rootPath = str_replace('\\', '/', BASE_PATH);
        
        $script = <<<PHP
<?php

\$rootPath = '{$rootPath}';
\$uri = urldecode(parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH));
\$requestedFile = \$rootPath . \$uri;
\$publicFile = \$rootPath . '/public' . \$uri;

// 1. Check if file exists in root (rare)
if (\$uri !== '/' && file_exists(\$requestedFile) && !is_dir(\$requestedFile)) {
    return false;
}

// 2. Check if file exists in /public (common for assets)
if (\$uri !== '/' && file_exists(\$publicFile) && !is_dir(\$publicFile)) {
    \$mime = mime_content_type(\$publicFile) ?: 'application/octet-stream';
    if (str_ends_with(\$uri, '.css')) \$mime = 'text/css';
    if (str_ends_with(\$uri, '.js')) \$mime = 'application/javascript';
    
    header("Content-Type: \$mime");
    readfile(\$publicFile);
    exit;
}

// 3. Fallback to root index.php
\$_SERVER['SCRIPT_NAME'] = '/index.php';
\$_SERVER['SCRIPT_FILENAME'] = \$rootPath . '/index.php';

require_once \$rootPath . '/index.php';
PHP;

        $routerPath = BASE_PATH . '/storage/.server-router.php';
        $storageDir = dirname($routerPath);
        
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        file_put_contents($routerPath, $script);

        return $routerPath;
    }
}
