<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Database/Connection.php';

use Analytics\Database\Connection;

echo "Running MySQL database migrations...\n";
try {
    Connection::runMigrations();
    echo "MySQL migrations applied successfully! Connected to MySQL database.\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
