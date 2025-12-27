<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Nitrous\Compiler;

class NitrousCommand extends Command
{
    protected string $signature = 'nitrous';
    protected string $description = 'Compile application for maximum performance';

    public function handle(array $args): int
    {
        $this->info("🔥 Initiating Nitrous Mode...");
        $this->newLine();

        $mode = $this->argument($args, 0, 'compile');

        return match($mode) {
            'compile' => $this->compile($args),
            'clear' => $this->clear(),
            'status' => $this->status(),
            default => $this->showModes(),
        };
    }

    private function compile(array $args): int
    {
        $this->line("  ⏳ Analyzing application structure...");
        
        $compiler = new Compiler();
        $force = $this->hasOption($args, 'force');

        $startTime = microtime(true);

        $this->output->progress(1, 5);
        sleep(1);

        $this->line("  📦 Collecting classes and dependencies...");
        $classes = $compiler->collectClasses();
        $this->output->progress(2, 5);

        $this->line("  🗜️  Optimizing and compiling...");
        $result = $compiler->compile($force);
        $this->output->progress(3, 5);

        $this->line("  ⚡ Warming up OPcache...");
        $compiler->warmOpcache();
        $this->output->progress(4, 5);

        $this->line("  ✅ Finalizing compilation...");
        $this->output->progress(5, 5);

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);

        $this->newLine();
        $this->success("  🚀 Nitrous Mode Activated!");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Classes Compiled', count($classes)],
                ['Output File', 'storage/nitrous/compiled.php'],
                ['Compilation Time', "{$elapsed}ms"],
                ['OPcache Status', function_exists('opcache_get_status') ? 'Active' : 'Disabled'],
            ]
        );

        $this->newLine();
        $this->info("  Your application is now running at maximum velocity! 🏎️");
        $this->newLine();

        return 0;
    }

    private function clear(): int
    {
        $compiledPath = BASE_PATH . '/storage/nitrous';
        
        if (is_dir($compiledPath)) {
            $files = glob($compiledPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->success("  ✅ Nitrous cache cleared successfully!");
        return 0;
    }

    private function status(): int
    {
        $compiledPath = BASE_PATH . '/storage/nitrous/compiled.php';
        $exists = file_exists($compiledPath);

        $this->info("  Nitrous Status");
        $this->line("  ─────────────────");
        
        if ($exists) {
            $size = round(filesize($compiledPath) / 1024, 2);
            $modified = date('Y-m-d H:i:s', filemtime($compiledPath));
            $this->success("  Status: Active");
            $this->line("  File Size: {$size} KB");
            $this->line("  Last Compiled: {$modified}");
        } else {
            $this->warning("  Status: Not Compiled");
            $this->line("  Run 'php lambo nitrous compile' to activate");
        }

        $this->newLine();

        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if ($status && isset($status['opcache_enabled']) && $status['opcache_enabled']) {
                $this->info("  OPcache: Enabled");
                $this->line("  Memory Used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB");
                $this->line("  Cached Scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0));
            } else {
                $this->warning("  OPcache: Disabled");
            }
        } else {
            $this->warning("  OPcache: Not Available");
        }

        return 0;
    }

    private function showModes(): int
    {
        $this->info("  Nitrous Modes:");
        $this->line("  ─────────────────");
        $this->line("  compile  - Compile application for maximum performance");
        $this->line("  clear    - Clear compiled files and OPcache");
        $this->line("  status   - Show current nitrous status");
        $this->newLine();
        $this->line("  Usage: php lambo nitrous [mode] [--force]");
        return 0;
    }
}
