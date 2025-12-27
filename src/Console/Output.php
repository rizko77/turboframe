<?php

namespace TurboFrame\Console;

class Output
{
    public function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    public function info(string $message): void
    {
        echo "\033[36m{$message}\033[0m" . PHP_EOL;
    }

    public function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    public function warning(string $message): void
    {
        echo "\033[33m{$message}\033[0m" . PHP_EOL;
    }

    public function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m" . PHP_EOL;
    }

    public function table(array $headers, array $rows): void
    {
        $widths = [];
        
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }
        
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $cell));
            }
        }

        $border = '+';
        foreach ($widths as $width) {
            $border .= str_repeat('-', $width + 2) . '+';
        }

        echo $border . PHP_EOL;
        
        echo '|';
        foreach ($headers as $i => $header) {
            echo ' ' . str_pad($header, $widths[$i]) . ' |';
        }
        echo PHP_EOL;
        
        echo $border . PHP_EOL;

        foreach ($rows as $row) {
            echo '|';
            foreach ($row as $i => $cell) {
                echo ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
            }
            echo PHP_EOL;
        }
        
        echo $border . PHP_EOL;
    }

    public function progress(int $current, int $total, int $width = 50): void
    {
        $percent = $current / $total;
        $filled = (int) ($percent * $width);
        $empty = $width - $filled;
        
        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        $percentDisplay = number_format($percent * 100, 1);
        
        echo "\r  [{$bar}] {$percentDisplay}%";
        
        if ($current >= $total) {
            echo PHP_EOL;
        }
    }

    public function ask(string $question, string $default = ''): string
    {
        $defaultDisplay = $default ? " [{$default}]" : '';
        echo "\033[33m{$question}{$defaultDisplay}: \033[0m";
        $answer = trim(fgets(STDIN));
        return $answer ?: $default;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $options = $default ? '[Y/n]' : '[y/N]';
        echo "\033[33m{$question} {$options}: \033[0m";
        $answer = strtolower(trim(fgets(STDIN)));
        
        if ($answer === '') {
            return $default;
        }
        
        return in_array($answer, ['y', 'yes', 'true', '1']);
    }

    public function newLine(int $count = 1): void
    {
        echo str_repeat(PHP_EOL, $count);
    }

    public function writeln(string $message): void
    {
        $this->line($message);
    }
}
