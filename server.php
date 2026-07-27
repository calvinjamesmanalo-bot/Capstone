<?php

$publicPath = __DIR__.DIRECTORY_SEPARATOR.'public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$requestedFile = $publicPath.str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_file($requestedFile)) {
    return false;
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
