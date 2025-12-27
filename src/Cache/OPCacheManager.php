<?php

namespace TurboFrame\Cache;

class OPCacheManager
{
    private bool $enabled;
    private string $cachePath;

    public function __construct()
    {
        $this->enabled = function_exists('opcache_get_status') && 
                         (opcache_get_status(false)['opcache_enabled'] ?? false);
        $this->cachePath = BASE_PATH . '/storage/cache';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function warmUp(): void
    {
        if (!$this->enabled) {
            return;
        }

        $directories = [
            BASE_PATH . '/src',
            BASE_PATH . '/application',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $this->preloadDirectory($directory);
        }
    }

    private function preloadDirectory(string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                if (function_exists('opcache_compile_file')) {
                    @opcache_compile_file($file->getPathname());
                }
            }
        }
    }

    public function invalidate(string $file): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return function_exists('opcache_invalidate') && opcache_invalidate($file, true);
    }

    public function reset(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return function_exists('opcache_reset') && opcache_reset();
    }

    public function getStatus(): array
    {
        if (!$this->enabled) {
            return ['enabled' => false];
        }

        $status = opcache_get_status(false);

        return [
            'enabled' => true,
            'memory_usage' => $status['memory_usage'] ?? [],
            'statistics' => $status['opcache_statistics'] ?? [],
            'configuration' => opcache_get_configuration() ?? [],
        ];
    }

    public function getCachedScripts(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $status = opcache_get_status(true);
        return $status['scripts'] ?? [];
    }

    public function cache(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $cacheFile = $this->getCacheFilePath($key);

        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            unlink($cacheFile);
        }

        $value = $callback();
        
        $this->ensureCacheDirectory();
        file_put_contents($cacheFile, serialize([
            'value' => $value,
            'expires' => time() + $ttl,
        ]));

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheFile = $this->getCacheFilePath($key);

        if (!file_exists($cacheFile)) {
            return $default;
        }

        $data = unserialize(file_get_contents($cacheFile));

        if ($data['expires'] < time()) {
            unlink($cacheFile);
            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->ensureCacheDirectory();
        
        file_put_contents($this->getCacheFilePath($key), serialize([
            'value' => $value,
            'expires' => time() + $ttl,
        ]));
    }

    public function forget(string $key): void
    {
        $cacheFile = $this->getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function flush(): void
    {
        $files = glob($this->cachePath . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getCacheFilePath(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
}
