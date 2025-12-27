<?php

namespace App\Controllers;

use TurboFrame\Http\Request;
use TurboFrame\Http\Response;

class WelcomeController
{
    public function index(Request $request): Response
    {
        return view('welcome');
    }
}