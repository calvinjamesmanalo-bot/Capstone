<?php

$publicPath = realpath(__DIR__.DIRECTORY_SEPARATOR.'public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$requestedFile = $publicPath === false
    ? false
    : realpath($publicPath.str_replace('/', DIRECTORY_SEPARATOR, $uri));

if ($uri !== '/'
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicPath.DIRECTORY_SEPARATOR)
    && is_file($requestedFile)) {
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    $contentType = match ($extension) {
        'css' => 'text/css; charset=UTF-8',
        'js', 'mjs' => 'application/javascript; charset=UTF-8',
        'json', 'map' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        default => mime_content_type($requestedFile) ?: 'application/octet-stream',
    };

    header('Content-Type: '.$contentType);
    header('Content-Length: '.filesize($requestedFile));
    readfile($requestedFile);

    return true;
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
