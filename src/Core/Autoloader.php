<?php

namespace TurboFrame\Core;

class Autoloader
{
    private static array $namespaces = [
        'TurboFrame\\' => 'src/',
        'App\\' => 'application/',
    ];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
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
