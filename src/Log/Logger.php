<?php

namespace TurboFrame\Log;

use DateTime;

class Logger
{
    private string $logPath;
    private string $channel;
    private array $levels = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    public function __construct(?string $channel = null)
    {
        $this->logPath = BASE_PATH . '/storage/logs';
        $this->channel = $channel ?? 'turbo';
        $this->ensureLogDirectory();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);

        if (!isset($this->levels[$level])) {
            $level = 'info';
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
        $message = $this->interpolate($message, $context);

        $logEntry = sprintf(
            "[%s] %s.%s: %s%s\n",
            $timestamp,
            $this->channel,
            strtoupper($level),
            $message,
            $context ? ' ' . json_encode($context) : ''
        );

        $this->write($logEntry);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_bool($val)) {
                $replace['{' . $key . '}'] = $val ? 'true' : 'false';
            } elseif (is_null($val)) {
                $replace['{' . $key . '}'] = 'null';
            }
        }
        return strtr($message, $replace);
    }

    private function write(string $content): void
    {
        $filename = $this->logPath . '/' . $this->channel . '-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    }

    public function channel(string $channel): self
    {
        return new self($channel);
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function getLogs(string $date = null, ?string $level = null): array
    {
        $date = $date ?? date('Y-m-d');
        $filename = $this->logPath . '/' . $this->channel . '-' . $date . '.log';

        if (!file_exists($filename)) {
            return [];
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[([\d\-\s:.]+)\]\s+(\w+)\.(\w+):\s+(.*)$/', $line, $matches)) {
                $entry = [
                    'timestamp' => $matches[1],
                    'channel' => $matches[2],
                    'level' => strtolower($matches[3]),
                    'message' => $matches[4],
                ];

                if ($level === null || $entry['level'] === strtolower($level)) {
                    $logs[] = $entry;
                }
            }
        }

        return $logs;
    }

    public function clear(): void
    {
        $files = glob($this->logPath . '/*.log');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function rotate(int $days = 7): void
    {
        $files = glob($this->logPath . '/*.log');
        $threshold = time() - ($days * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
            }
        }
    }

    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
}
