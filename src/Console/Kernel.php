<?php

namespace TurboFrame\Console;

use TurboFrame\Core\Application;
use TurboFrame\Console\Commands\ServeCommand;
use TurboFrame\Console\Commands\NitrousCommand;
use TurboFrame\Console\Commands\MakeControllerCommand;
use TurboFrame\Console\Commands\MakeModelCommand;
use TurboFrame\Console\Commands\MakeMiddlewareCommand;
use TurboFrame\Console\Commands\MigrateCommand;
use TurboFrame\Console\Commands\CacheClearCommand;
use TurboFrame\Console\Commands\RouteListCommand;
use TurboFrame\Console\Commands\OptimizeCommand;
use TurboFrame\Console\Commands\ServeSwooleCommand;

class Kernel
{
    protected array $commands = [];
    protected Application $app;
    protected Output $output;

    public function __construct()
    {
        $this->output = new Output();
        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $this->commands = [
            'serve' => ServeCommand::class,
            'nitrous' => NitrousCommand::class,
            'make:controller' => MakeControllerCommand::class,
            'make:model' => MakeModelCommand::class,
            'make:middleware' => MakeMiddlewareCommand::class,
            'migrate' => MigrateCommand::class,
            'cache:clear' => CacheClearCommand::class,
            'route:list' => RouteListCommand::class,
            'optimize' => OptimizeCommand::class,
            'serve:swoole' => ServeSwooleCommand::class,
        ];
    }

    public function handle(array $argv): int
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];
        $args = array_slice($argv, 2);

        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return 0;
        }

        if ($commandName === '--version' || $commandName === '-v') {
            $this->output->line("TurboFrame Framework v1.0.0");
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            $this->output->error("Command [{$commandName}] not found.");
            $this->showHelp();
            return 1;
        }

        try {
            $commandClass = $this->commands[$commandName];
            $command = new $commandClass($this->output);
            return $command->run($args);
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());
            if (getenv('APP_DEBUG') === 'true') {
                $this->output->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    protected function showHelp(): void
    {
        $this->output->line("");
        $this->output->success("Available Commands:");
        $this->output->line("");
        
        $commandDescriptions = [
            'serve' => 'Start the Lambo development server',
            'nitrous' => 'Compile application for maximum performance',
            'make:controller' => 'Create a new controller class',
            'make:model' => 'Create a new model class',
            'make:middleware' => 'Create a new middleware class',
            'migrate' => 'Run database migrations',
            'cache:clear' => 'Clear application cache',
            'route:list' => 'List all registered routes',
            'optimize' => 'Optimize application performance',
        ];

        foreach ($commandDescriptions as $name => $description) {
            $this->output->line("  \033[32m{$name}\033[0m\t\t{$description}");
        }

        $this->output->line("");
        $this->output->line("Usage: php lambo <command> [options]");
        $this->output->line("");
    }
}
