<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/autoload.php';

use Analytics\Http\Request;
use Analytics\Router;
use Analytics\Database\Connection;

// Ensure database tables exist
Connection::runMigrations();

$request = Request::createFromGlobals();
$router = new Router();
$response = $router->dispatch($request);
$response->send();
