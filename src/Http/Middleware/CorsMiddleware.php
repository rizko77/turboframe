<?php

namespace TurboFrame\Http\Middleware;

use Closure;
use TurboFrame\Http\Request;
use TurboFrame\Http\Response;

class CorsMiddleware
{
    protected array $allowedOrigins = ['*'];
    protected array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    protected array $allowedHeaders = ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN'];
    protected bool $allowCredentials = false;
    protected int $maxAge = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflight();
        }

        $response = $next($request);
        return $this->addCorsHeaders($response);
    }

    protected function handlePreflight(): Response
    {
        $response = Response::make('', 204);
        return $this->addCorsHeaders($response);
    }

    protected function addCorsHeaders(Response $response): Response
    {
        $response->header('Access-Control-Allow-Origin', implode(', ', $this->allowedOrigins));
        $response->header('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response->header('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        $response->header('Access-Control-Max-Age', (string) $this->maxAge);

        if ($this->allowCredentials) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
