<?php

declare(strict_types=1);

namespace Analytics\Database;

use mysqli;
use RuntimeException;

class Connection
{
    private static ?mysqli $instance = null;
    private static ?array $customConfig = null;

    public static function setConfig(array $config): void
    {
        self::$customConfig = $config;
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }

    public static function get(): mysqli
    {
        if (self::$instance === null || !@self::$instance->ping()) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $config = self::$customConfig ?? self::getDefaultConfig();
            $host = $config['host'] ?? '127.0.0.1';
            $port = (int)($config['port'] ?? 3306);
            $user = $config['user'] ?? 'root';
            $password = $config['password'] ?? '';
            $database = $config['database'] ?? 'analytics_db';
            $charset = $config['charset'] ?? 'utf8mb4';

            // Connect to MySQL server first (without database) to ensure DB exists
            try {
                $conn = new mysqli($host, $user, $password, '', $port);
            } catch (\Throwable $e) {
                throw new RuntimeException("Failed to connect to MySQL server at {$host}:{$port} - " . $e->getMessage(), 0, $e);
            }

            // Create database if not exists
            $conn->query("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($database);
            $conn->set_charset($charset);

            self::$instance = $conn;
        }

        return self::$instance;
    }

    public static function getDefaultConfig(): array
    {
        $configFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }

        return [
            'host' => '127.0.0.1',
            'port' => 3306,
            'user' => 'root',
            'password' => '',
            'database' => 'analytics_db',
            'charset' => 'utf8mb4',
        ];
    }

    public static function runMigrations(?mysqli $mysqli = null): void
    {
        $conn = $mysqli ?? self::get();
        $schemaPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';

        if (!file_exists($schemaPath)) {
            throw new RuntimeException("Schema file not found at: {$schemaPath}");
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException("Failed to read schema file: {$schemaPath}");
        }

        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        }
    }
}
