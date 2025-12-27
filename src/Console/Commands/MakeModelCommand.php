<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;

class MakeModelCommand extends Command
{
    protected string $signature = 'make:model {name}';
    protected string $description = 'Create a new model class';

    public function handle(array $args): int
    {
        $name = $this->argument($args, 0);

        if (!$name) {
            $this->error("Please provide a model name.");
            $this->line("Usage: php lambo make:model User");
            return 1;
        }

        $name = str_replace('/', '\\', $name);
        $parts = explode('\\', $name);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);
        $fullNamespace = $namespace ? "App\\Models\\{$namespace}" : "App\\Models";

        $directory = BASE_PATH . '/application/Models';
        if ($namespace) {
            $directory .= '/' . str_replace('\\', '/', $namespace);
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath) && !$this->hasOption($args, 'force')) {
            $this->error("Model already exists: {$filePath}");
            $this->line("Use --force to overwrite.");
            return 1;
        }

        $table = $this->option($args, 'table', $this->generateTableName($className));
        $withMigration = $this->hasOption($args, 'migration') || $this->hasOption($args, 'm');

        $content = $this->generateModel($fullNamespace, $className, $table);
        file_put_contents($filePath, $content);

        $this->success("✅ Model created successfully!");
        $this->line("   {$filePath}");

        if ($withMigration) {
            $this->createMigration($table);
        }

        return 0;
    }

    private function generateTableName(string $className): string
    {
        $table = preg_replace('/([a-z])([A-Z])/', '$1_$2', $className);
        return strtolower($table) . 's';
    }

    private function generateModel(string $namespace, string $className, string $table): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use TurboFrame\Database\Model;

class {$className} extends Model
{
    protected string \$table = '{$table}';

    protected string \$primaryKey = 'id';

    protected array \$fillable = [];

    protected array \$hidden = [];

    protected array \$casts = [];
}
PHP;
    }

    private function createMigration(string $table): void
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$table}_table.php";

        $directory = BASE_PATH . '/database/migrations';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $filename;
        $className = 'Create' . str_replace('_', '', ucwords($table, '_')) . 'Table';

        $content = <<<PHP
<?php

use TurboFrame\Database\Migration;
use TurboFrame\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        \$this->schema->create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->dropIfExists('{$table}');
    }
};
PHP;

        file_put_contents($filePath, $content);
        $this->success("✅ Migration created: {$filename}");
    }
}
