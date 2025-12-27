<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;
use TurboFrame\Database\Connection;
use TurboFrame\Core\Application;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate';
    protected string $description = 'Run database migrations';

    public function handle(array $args): int
    {
        $this->info("⚡ Running migrations...");
        $this->newLine();

        $migrationsPath = BASE_PATH . '/database/migrations';

        if (!is_dir($migrationsPath)) {
            $this->warning("No migrations directory found.");
            return 0;
        }

        $files = glob($migrationsPath . '/*.php');

        if (empty($files)) {
            $this->info("Nothing to migrate.");
            return 0;
        }

        $rollback = $this->hasOption($args, 'rollback');
        $fresh = $this->hasOption($args, 'fresh');
        $seed = $this->hasOption($args, 'seed');

        if ($fresh) {
            $this->warning("Dropping all tables...");
            $this->dropAllTables();
        }

        sort($files);

        if ($rollback) {
            $files = array_reverse($files);
        }

        $count = 0;
        foreach ($files as $file) {
            $migration = require $file;
            $filename = basename($file);

            try {
                if ($rollback) {
                    $migration->down();
                    $this->line("  ⬇️  Rolled back: {$filename}");
                } else {
                    $migration->up();
                    $this->line("  ⬆️  Migrated: {$filename}");
                }
                $count++;
            } catch (\Throwable $e) {
                $this->error("  ❌ Failed: {$filename}");
                $this->error("     " . $e->getMessage());
                return 1;
            }
        }

        $this->newLine();

        if ($rollback) {
            $this->success("✅ Rolled back {$count} migration(s).");
        } else {
            $this->success("✅ Ran {$count} migration(s).");
        }

        if ($seed && !$rollback) {
            $this->runSeeder();
        }

        return 0;
    }

    private function dropAllTables(): void
    {
        $this->line("  🗑️  Dropping all tables...");
    }

    private function runSeeder(): void
    {
        $seederPath = BASE_PATH . '/database/seeders/DatabaseSeeder.php';
        
        if (file_exists($seederPath)) {
            $seeder = require $seederPath;
            $seeder->run();
            $this->success("✅ Database seeded.");
        }
    }
}
