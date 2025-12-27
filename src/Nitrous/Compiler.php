<?php

namespace TurboFrame\Nitrous;

class Compiler
{
    private string $outputPath;
    private array $classes = [];
    private array $includedFiles = [];

    public function __construct()
    {
        $this->outputPath = BASE_PATH . '/storage/nitrous';
    }

    public function collectClasses(): array
    {
        $directories = [
            BASE_PATH . '/src',
            BASE_PATH . '/application',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $this->scanDirectory($directory);
        }

        return $this->classes;
    }

    private function scanDirectory(string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $content = file_get_contents($path);

            if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                $namespace = $matches[1];
                
                if (preg_match('/^(?:abstract\s+|final\s+)?class\s+(\w+)/m', $content, $classMatches)) {
                    $this->classes[] = [
                        'namespace' => $namespace,
                        'class' => $classMatches[1],
                        'file' => $path,
                        'fqcn' => $namespace . '\\' . $classMatches[1],
                    ];
                }
            }

            $this->includedFiles[] = $path;
        }
    }

    public function compile(bool $force = false): array
    {
        $this->ensureOutputDirectory();

        $outputFile = $this->outputPath . '/compiled.php';

        if (!$force && file_exists($outputFile)) {
            $lastModified = filemtime($outputFile);
            $needsRecompile = false;

            foreach ($this->includedFiles as $file) {
                if (filemtime($file) > $lastModified) {
                    $needsRecompile = true;
                    break;
                }
            }

            if (!$needsRecompile) {
                return ['status' => 'cached', 'file' => $outputFile];
            }
        }

        $compiled = $this->buildCompiledFile();
        file_put_contents($outputFile, $compiled);

        $this->generateManifest();

        return [
            'status' => 'compiled',
            'file' => $outputFile,
            'size' => strlen($compiled),
            'classes' => count($this->classes),
        ];
    }

    private function buildCompiledFile(): string
    {
        $output = "<?php\n\n";
        $output .= "declare(strict_types=1);\n\n";

        $processedNamespaces = [];

        foreach ($this->includedFiles as $file) {
            $content = file_get_contents($file);
            
            $content = preg_replace('/<\?php\s*/', '', $content);
            $content = preg_replace('/declare\s*\([^)]+\)\s*;/', '', $content);
            $content = preg_replace('/^require[^;]+;$/m', '', $content);
            $content = preg_replace('/^include[^;]+;$/m', '', $content);
            
            $content = trim($content);
            
            if (!empty($content)) {
                $output .= "\n// File: " . basename($file) . "\n";
                $output .= $content . "\n";
            }
        }

        return $output;
    }

    private function generateManifest(): void
    {
        $manifest = [
            'compiled_at' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'classes' => count($this->classes),
            'files' => count($this->includedFiles),
            'class_map' => [],
        ];

        foreach ($this->classes as $class) {
            $manifest['class_map'][$class['fqcn']] = $class['file'];
        }

        file_put_contents(
            $this->outputPath . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function warmOpcache(): void
    {
        $compiledFile = $this->outputPath . '/compiled.php';

        if (file_exists($compiledFile) && function_exists('opcache_compile_file')) {
            opcache_compile_file($compiledFile);
        }
    }

    public function getCompiledPath(): string
    {
        return $this->outputPath . '/compiled.php';
    }

    public function isCompiled(): bool
    {
        return file_exists($this->outputPath . '/compiled.php');
    }

    public function getManifest(): ?array
    {
        $manifestPath = $this->outputPath . '/manifest.json';

        if (!file_exists($manifestPath)) {
            return null;
        }

        return json_decode(file_get_contents($manifestPath), true);
    }

    public function clear(): void
    {
        $files = glob($this->outputPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function ensureOutputDirectory(): void
    {
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }
}
