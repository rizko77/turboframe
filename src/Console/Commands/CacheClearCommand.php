<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;

class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear';
    protected string $description = 'Clear application cache';

    public function handle(array $args): int
    {
        $this->info("🧹 Clearing cache...");
        $this->newLine();

        $cleared = 0;

        $cachePaths = [
            BASE_PATH . '/storage/cache' => 'Application Cache',
            BASE_PATH . '/storage/views' => 'Compiled Views',
            BASE_PATH . '/storage/nitrous' => 'Nitrous Cache',
        ];

        foreach ($cachePaths as $path => $name) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $cleared++;
                    }
                }
                $this->line("  ✓ {$name}");
            }
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
            $this->line("  ✓ OPcache");
        }

        $this->newLine();
        $this->success("✅ Cache cleared! ({$cleared} files removed)");

        return 0;
    }
}
