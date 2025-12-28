<?php

/**
 * TurboFrame RoadRunner Worker
 * This script handles high-performance requests using the RoadRunner Go server.
 */

use Spiral\RoadRunner;
use Nyholm\Psr7;
use TurboFrame\Core\Application;

define('TURBO_START', microtime(true));
define('BASE_PATH', __DIR__);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/helpers.php';

// Initialize Persistent Application
$app = Application::getInstance();
$app->boot();

// RoadRunner Worker Initialization
$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();
$psr7 = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($request = $psr7->waitRequest()) {
    try {
        // 1. Convert PSR-7 Request to TurboFrame compatible state
        // (For now using simple Method/URI bridge)
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // Populate globals for legacy/helper compatibility
        $_GET = $request->getQueryParams();
        $_POST = $request->getParsedBody() ?? [];
        $_COOKIE = $request->getCookieParams();
        $_SERVER = $request->getServerParams();

        // 2. Handle Request
        $tfResponse = $app->handle($method, $uri);

        // 3. Convert TurboFrame Response to PSR-7
        $response = $psrFactory->createResponse($tfResponse->getStatusCode());
        
        foreach ($tfResponse->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        $response->getBody()->write($tfResponse->getBody());

        $psr7->respond($response);
    } catch (\Throwable $e) {
        $psr7->getWorker()->error((string)$e);
    }
}
