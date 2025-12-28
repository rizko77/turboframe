<?php

namespace TurboFrame\View;

class Engine
{
    private string $viewPath;
    private string $cachePath;
    private array $shared = [];
    private array $sections = [];
    private array $sectionStack = [];
    private array $cacheStack = [];
    private ?string $layout = null;
    
    // Static cache for compiled views (in-memory for current request)
    private static array $compiledCache = [];

    public function __construct()
    {
        $this->viewPath = BASE_PATH . '/views';
        $this->cachePath = BASE_PATH . '/storage/views';
        $this->ensureCacheDirectory();
    }

    public function render(string $view, array $data = []): string
    {
        $viewFile = str_replace('.', '/', $view);
        $path = $this->viewPath . '/' . $viewFile . '.turbo.php';
        
        if (!file_exists($path)) {
            $path = $this->viewPath . '/' . $viewFile . '.php';
        }

        if (!file_exists($path)) {
            throw new \Exception("View [{$view}] not found at [{$path}]");
        }

        $app = \TurboFrame\Core\Application::getInstance();
        $useCache = !$app->isDebug() || $app->env('NITROUS_MODE') === 'true';

        $data = array_merge($this->shared, $data);

        if ($useCache) {
            $compiledPath = $this->getCachePath($view);
            
            // Check static cache first (fastest)
            if (!isset(self::$compiledCache[$view])) {
                if (!file_exists($compiledPath) || filemtime($path) > filemtime($compiledPath)) {
                    $content = $this->compile(file_get_contents($path));
                    file_put_contents($compiledPath, $content);
                }
                self::$compiledCache[$view] = $compiledPath;
            }
            
            $output = $this->evaluate(self::$compiledCache[$view], $data);
        } else {
            // In debug mode, check static cache to avoid recompiling
            if (!isset(self::$compiledCache[$view])) {
                self::$compiledCache[$view] = $this->compile(file_get_contents($path));
            }
            $output = $this->evaluateString(self::$compiledCache[$view], $data);
        }

        if ($this->layout) {
            $layout = $this->layout;
            $this->layout = null;
            $this->sections['content'] = $output;
            return $this->render($layout, $data);
        }

        return $output;
    }

    private function compile(string $content): string
    {
        // Handle verbatim blocks (ignore everything inside)
        $content = preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function($matches) {
            return 'TF_VERBATIM_START' . base64_encode($matches[1]) . 'TF_VERBATIM_END';
        }, $content);

        $content = preg_replace('/\{\{--(.+?)--\}\}/s', '<?php /* $1 */ ?>', $content);
        $content = preg_replace('/\{\{\{\s*(.+?)\s*\}\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        $content = preg_replace('/\{\!\!\s*(.+?)\s*\!\!\}/', '<?php echo $1; ?>', $content);
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1 ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>', $content);

        // Handle @calling directive separately to avoid ?? syntax issues
        $content = preg_replace_callback(
            '/@calling\s*\([\'"](.+?)[\'"]\s*(?:,\s*(\[.+?\]))?\)/',
            function($matches) {
                $view = $matches[1];
                $data = isset($matches[2]) ? $matches[2] : '[]';
                return "<?php echo view('{$view}', {$data}); ?>";
            },
            $content
        );

        $patterns = [
            '/@if\s*\((.+)\)/' => '<?php if($1): ?>',
            '/@elseif\s*\((.+)\)/' => '<?php elseif($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@unless\s*\((.+)\)/' => '<?php if(!($1)): ?>',
            '/@endunless/' => '<?php endif; ?>',
            '/@foreach\s*\((.+)\)/' => '<?php foreach($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@csrf/' => '<?php echo csrf_field(); ?>',
            '/@cache\s*\((.+)\)/' => '<?php if($this->startCacheFragment($1)): ?>',
            '/@endcache/' => '<?php $this->endCacheFragment(); endif; ?>',
            '/@php/' => '<?php ',
            '/@endphp/' => ' ?>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        $content = preg_replace_callback('/@extends\s*\([\'"](.+)[\'"]\)/', function($matches) {
            return '<?php $this->layout = \'' . $matches[1] . '\'; ?>';
        }, $content);

        $content = preg_replace_callback('/@section\s*\([\'"](.+)[\'"]\)/', function($matches) {
            return '<?php $this->startSection(\'' . $matches[1] . '\'); ?>';
        }, $content);

        $content = preg_replace('/@endsection/', '<?php $this->endSection(); ?>', $content);

        $content = preg_replace_callback('/@yield\s*\([\'"](.+?)[\'"]\s*(?:,\s*[\'"](.+?)[\'"]\s*)?\)/', function($matches) {
            $default = $matches[2] ?? '';
            return '<?php echo $this->yieldSection(\'' . $matches[1] . '\', \'' . $default . '\'); ?>';
        }, $content);

        // Restore verbatim blocks
        $content = preg_replace_callback('/TF_VERBATIM_START(.*?)TF_VERBATIM_END/', function($matches) {
            return base64_decode($matches[1]);
        }, $content);

        return $content;
    }

    private function evaluate(string $path, array $data): string
    {
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    private function evaluateString(string $content, array $data): string
    {
        extract($data);
        ob_start();
        try {
            eval('?>' . $content);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function startCacheFragment(string $key, int $ttl = 3600): bool
    {
        $cache = \app(\TurboFrame\Cache\OPCacheManager::class);
        $content = $cache->get('fragment_' . $key);

        if ($content !== null) {
            echo $content;
            return false;
        }

        $this->cacheStack[] = ['key' => $key, 'ttl' => $ttl];
        ob_start();
        return true;
    }

    public function endCacheFragment(): void
    {
        $fragment = array_pop($this->cacheStack);
        $content = ob_get_clean();
        
        $cache = \app(\TurboFrame\Cache\OPCacheManager::class);
        $cache->put('fragment_' . $fragment['key'], $content, $fragment['ttl']);
        
        echo $content;
    }

    private function getCachePath(string $view): string
    {
        return $this->cachePath . '/' . md5($view) . '.php';
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
}
