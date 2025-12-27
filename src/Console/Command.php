<?php

namespace TurboFrame\Console;

abstract class Command
{
    protected Output $output;
    protected string $signature = '';
    protected string $description = '';

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    abstract public function handle(array $args): int;

    public function run(array $args): int
    {
        return $this->handle($args);
    }

    protected function argument(array $args, int $index, mixed $default = null): mixed
    {
        return $args[$index] ?? $default;
    }

    protected function option(array $args, string $name, mixed $default = null): mixed
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, "--{$name}=")) {
                return substr($arg, strlen("--{$name}="));
            }
            if ($arg === "--{$name}") {
                return true;
            }
            if (str_starts_with($arg, "-{$name[0]}=")) {
                return substr($arg, 3);
            }
            if ($arg === "-{$name[0]}" && !str_contains($name, '-')) {
                return true;
            }
        }
        return $default;
    }

    protected function hasOption(array $args, string $name): bool
    {
        foreach ($args as $arg) {
            if ($arg === "--{$name}" || str_starts_with($arg, "--{$name}=")) {
                return true;
            }
        }
        return false;
    }

    protected function info(string $message): void
    {
        $this->output->info($message);
    }

    protected function success(string $message): void
    {
        $this->output->success($message);
    }

    protected function warning(string $message): void
    {
        $this->output->warning($message);
    }

    protected function error(string $message): void
    {
        $this->output->error($message);
    }

    protected function line(string $message): void
    {
        $this->output->line($message);
    }

    protected function newLine(int $count = 1): void
    {
        $this->output->newLine($count);
    }

    protected function table(array $headers, array $rows): void
    {
        $this->output->table($headers, $rows);
    }

    protected function ask(string $question, string $default = ''): string
    {
        return $this->output->ask($question, $default);
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        return $this->output->confirm($question, $default);
    }

    protected function progress(int $current, int $total, int $width = 50): void
    {
        $this->output->progress($current, $total, $width);
    }
}
