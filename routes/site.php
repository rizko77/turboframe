<?php

use TurboFrame\Http\Response;

$router->get('/', function() {
    return view('welcome');
})->name('home');
