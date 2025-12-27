<?php

namespace TurboFrame\Console\Commands;

use TurboFrame\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller {name}';
    protected string $description = 'Create a new controller class';

    public function handle(array $args): int
    {
        $name = $this->argument($args, 0);

        if (!$name) {
            $this->error("Please provide a controller name.");
            $this->line("Usage: php lambo make:controller UserController");
            return 1;
        }

        $name = str_replace('/', '\\', $name);
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $parts = explode('\\', $name);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);
        $fullNamespace = $namespace ? "App\\Controllers\\{$namespace}" : "App\\Controllers";

        $directory = BASE_PATH . '/application/Controllers';
        if ($namespace) {
            $directory .= '/' . str_replace('\\', '/', $namespace);
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath) && !$this->hasOption($args, 'force')) {
            $this->error("Controller already exists: {$filePath}");
            $this->line("Use --force to overwrite.");
            return 1;
        }

        $isResource = $this->hasOption($args, 'resource');
        $isApi = $this->hasOption($args, 'api');

        $content = $this->generateController($fullNamespace, $className, $isResource, $isApi);

        file_put_contents($filePath, $content);

        $this->success("✅ Controller created successfully!");
        $this->line("   {$filePath}");

        return 0;
    }

    private function generateController(string $namespace, string $className, bool $resource, bool $api): string
    {
        $methods = $this->getMethods($resource, $api);

        return <<<PHP
<?php

namespace {$namespace};

use TurboFrame\Http\Request;
use TurboFrame\Http\Response;

class {$className}
{
{$methods}
}
PHP;
    }

    private function getMethods(bool $resource, bool $api): string
    {
        if (!$resource && !$api) {
            return <<<'PHP'
    public function index(Request $request): Response
    {
        return Response::html(view('welcome'));
    }
PHP;
        }

        if ($api) {
            return <<<'PHP'
    public function index(Request $request): Response
    {
        return Response::json(['data' => []]);
    }

    public function show(Request $request, string $id): Response
    {
        return Response::json(['data' => ['id' => $id]]);
    }

    public function store(Request $request): Response
    {
        $data = $request->json();
        return Response::json(['data' => $data], 201);
    }

    public function update(Request $request, string $id): Response
    {
        $data = $request->json();
        return Response::json(['data' => array_merge(['id' => $id], $data)]);
    }

    public function destroy(Request $request, string $id): Response
    {
        return Response::json(['message' => 'Deleted'], 204);
    }
PHP;
        }

        return <<<'PHP'
    public function index(Request $request): Response
    {
        return Response::html(view('index'));
    }

    public function create(Request $request): Response
    {
        return Response::html(view('create'));
    }

    public function store(Request $request): Response
    {
        return Response::redirect('/');
    }

    public function show(Request $request, string $id): Response
    {
        return Response::html(view('show', ['id' => $id]));
    }

    public function edit(Request $request, string $id): Response
    {
        return Response::html(view('edit', ['id' => $id]));
    }

    public function update(Request $request, string $id): Response
    {
        return Response::redirect('/');
    }

    public function destroy(Request $request, string $id): Response
    {
        return Response::redirect('/');
    }
PHP;
    }
}
