<?php

// Core PHP Built-in Server Router
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init_db.php';

// Ensure tables exist on boot
init_database();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Handle CORS OPTIONS preflight globally
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    handle_cors();
}

$routes = [
    '/api/health' => __DIR__ . '/api/health.php',
    '/api/v1/events' => __DIR__ . '/api/events.php',
    '/api/v1/analytics/overview' => __DIR__ . '/api/overview.php',
    '/api/v1/analytics/timeseries' => __DIR__ . '/api/timeseries.php',
    '/api/v1/analytics/popular-pages' => __DIR__ . '/api/popular_pages.php',
    '/api/v1/analytics/event-breakdown' => __DIR__ . '/api/breakdown.php',
    '/api/v1/analytics/funnel' => __DIR__ . '/api/funnel.php',
    '/api/v1/analytics/recent-events' => __DIR__ . '/api/recent_events.php',
    '/api/orders' => __DIR__ . '/api/orders.php',
];

if (isset($routes[$uri])) {
    require $routes[$uri];
    exit;
}

// 404 for unknown routes
send_json([
    'status' => 'error',
    'message' => 'Endpoint not found: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $uri
], 404);
