<?php

namespace TurboFrame\Http\Middleware;

use Closure;
use TurboFrame\Http\Request;
use TurboFrame\Http\Response;
use TurboFrame\Log\Logger;

class LogRequestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $logger = new Logger('request');
        $logger->info('{method} {uri} - {status} ({duration}ms)', [
            'method' => $request->method(),
            'uri' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration' => $duration,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
