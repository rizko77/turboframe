<?php

$rootPath = 'E:/FILE PENTING/Hasil Ngoding/Project PHP/TurboFrame_Stable_v1.0.0';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requestedFile = $rootPath . $uri;
$publicFile = $rootPath . '/public' . $uri;

// 1. Check if file exists in root (rare)
if ($uri !== '/' && file_exists($requestedFile) && !is_dir($requestedFile)) {
    return false;
}

// 2. Check if file exists in /public (common for assets)
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $mime = mime_content_type($publicFile) ?: 'application/octet-stream';
    if (str_ends_with($uri, '.css')) $mime = 'text/css';
    if (str_ends_with($uri, '.js')) $mime = 'application/javascript';
    
    header("Content-Type: $mime");
    readfile($publicFile);
    exit;
}

// 3. Fallback to root index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $rootPath . '/index.php';

require_once $rootPath . '/index.php';