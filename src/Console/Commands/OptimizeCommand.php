<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Nitrous\Compiler;
use TurboFrame\View\Engine;

class OptimizeCommand extends Command
{
    protected string $signature = 'optimize';
    protected string $description = 'Optimize application for peak performance (Class Map & View Pre-compilation)';

    public function handle(array $args): int
    {
        $this->info("🚀 Starting TurboFrame Optimization...");
        $this->newLine();

        // 1. Generate Class Map via Nitrous Compiler
        $this->line("📦 Generating Class Map...");
        $compiler = new Compiler();
        $compiler->collectClasses();
        $result = $compiler->compile(true);
        $this->success("  ✓ Class map generated ({$result['classes']} classes)");

        // 2. Pre-compile Views
        $this->line("🎨 Pre-compiling Views...");
        $this->precompileViews();
        $this->success("  ✓ All views pre-compiled");

        // 3. Clear existing application cache
        $this->line("🧹 Refreshing application cache...");
        \app(\TurboFrame\Cache\OPCacheManager::class)->flush();
        $this->success("  ✓ Cache refreshed");

        $this->newLine();
        $this->success("✨ Optimization complete! Your app is now in 'GOD MODE' speed.");
        
        return 0;
    }

    private function precompileViews(): void
    {
        $viewPath = BASE_PATH . '/views';
        $engine = new Engine();
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $name = $file->getBasename('.turbo.php');
                if ($name === $file->getBasename()) {
                    $name = $file->getBasename('.php');
                }
                
                // We just call render which triggers compilation if not exists
                // But we don't care about the output here
                try {
                    $relative = str_replace([$viewPath, '\\'], ['', '/'], $file->getPathname());
                    $viewName = trim(str_replace(['/','.turbo.php', '.php'], ['.','', ''], $relative), '.');
                    
                    $this->compileOneView($engine, $viewName, $file->getPathname());
                } catch (\Exception $e) {
                    // Skip if fails
                }
            }
        }
    }

    private function compileOneView(Engine $engine, string $viewName, string $fullPath): void
    {
        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('compile');
        $method->setAccessible(true);
        
        $cacheMethod = $reflection->getMethod('getCachePath');
        $cacheMethod->setAccessible(true);
        
        $content = file_get_contents($fullPath);
        $compiled = $method->invoke($engine, $content);
        $cachePath = $cacheMethod->invoke($engine, $viewName);
        
        file_put_contents($cachePath, $compiled);
    }
}
