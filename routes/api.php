<?php

use TurboFrame\Http\Response;

$router->group(['prefix' => 'api'], function($router) {
    
    $router->get('/status', function() {
        return Response::json([
            'status' => 'healthy',
            'framework' => 'TurboFrame',
            'version' => '1.0.0'
        ]);
    });

});
