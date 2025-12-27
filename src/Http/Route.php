<?php

namespace TurboFrame\Http;

class Route
{
    private array $methods;
    private string $uri;
    private mixed $action;
    private array $middleware;
    private string $namespace;
    private ?string $name = null;
    private string $pattern;
    private array $parameterNames = [];

    public function __construct(
        array $methods,
        string $uri,
        mixed $action,
        array $middleware = [],
        string $namespace = ''
    ) {
        $this->methods = $methods;
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
        $this->middleware = $middleware;
        $this->namespace = $namespace;
        $this->compilePattern();
    }

    private function compilePattern(): void
    {
        $pattern = $this->uri;
        
        $pattern = preg_replace_callback('/\{([a-zA-Z_]+)\??\}/', function($matches) {
            $this->parameterNames[] = $matches[1];
            $isOptional = str_ends_with($matches[0], '?}');
            if ($isOptional) {
                return '(?:([^/]+))?';
            }
            return '([^/]+)';
        }, $pattern);

        $this->pattern = '#^' . $pattern . '/?$#';
    }

    public function match(string $uri): array|false
    {
        $uri = '/' . trim($uri, '/');
        
        if ($uri === '/') {
            $uri = '/';
        }
        
        if (!preg_match($this->pattern, $uri, $matches)) {
            return false;
        }

        array_shift($matches);
        
        $params = [];
        foreach ($this->parameterNames as $index => $name) {
            $params[$name] = $matches[$index] ?? null;
        }

        return $params;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        Router::getInstance()->name($name, $this);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
