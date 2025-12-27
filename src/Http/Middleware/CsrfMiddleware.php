<?php

namespace TurboFrame\Http\Middleware;

use Closure;
use TurboFrame\Http\Request;
use TurboFrame\Http\Response;

class CsrfMiddleware
{
    protected array $except = [];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isReading($request) || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return $next($request);
        }

        return Response::json(['error' => 'CSRF token mismatch'], 419);
    }

    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']);
    }

    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->path() === $except) {
                return true;
            }
        }
        return false;
    }

    protected function tokensMatch(Request $request): bool
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        $sessionToken = $_SESSION['_csrf_token'] ?? null;

        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }
}
