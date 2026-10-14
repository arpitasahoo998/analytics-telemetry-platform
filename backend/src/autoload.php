<?php

declare(strict_types=1);

spl_autoload_register(function (string $class) {
    $prefixes = [
        'Analytics\\Sdk\\' => dirname(__DIR__, 2) . '/sdk/php/src/',
        'Analytics\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
