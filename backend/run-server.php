<?php

declare(strict_types=1);

// Built-in PHP server router script
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If request points to an existing file in public, serve it directly
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
