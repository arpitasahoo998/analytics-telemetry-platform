<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

send_json([
    'status' => 'ok',
    'service' => 'analytics-core-php-api',
    'database' => 'mysql',
    'driver' => 'mysqli',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
]);
