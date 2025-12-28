<?php

namespace TurboFrame\Core;

class Autoloader
{
    private static array $namespaces = [
        'TurboFrame\\' => 'src/',
        'App\\' => 'application/',
    ];
    private static ?array $classMap = null;

    public static function register(): void
    {
        // Load manifest if exists (Nitrous Mode optimization)
        $manifestPath = BASE_PATH . '/storage/nitrous/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            self::$classMap = $manifest['class_map'] ?? null;
        }

        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        // 1. Check Static Class Map (Fastest)
        if (self::$classMap !== null && isset(self::$classMap[$class])) {
            $file = self::$classMap[$class];
            if (file_exists($file)) {
                require $file;
                return;
            }
        }

        // 2. PSR-4 Fallback
        foreach (self::$namespaces as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $file = BASE_PATH . '/' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        self::$namespaces[$prefix] = $baseDir;
    }
}
