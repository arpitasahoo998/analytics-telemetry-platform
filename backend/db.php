<?php

// Database connection helper using procedural mysqli
function get_db_connection() {
    static $conn = null;

    if ($conn !== null && @mysqli_ping($conn)) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = (int)(getenv('DB_PORT') ?: 3306);
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    $dbname = getenv('DB_NAME') ?: 'analytics_db';

    $conn = @mysqli_connect($host, $user, $pass, '', $port);

    if (!$conn) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'MySQL connection failed: ' . mysqli_connect_error()
        ]);
        exit;
    }

    // Ensure database exists
    mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mysqli_select_db($conn, $dbname);
    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}

// Global response helper
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Session-Id, X-Anonymous-Id');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle CORS OPTIONS preflight
function handle_cors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Session-Id, X-Anonymous-Id');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
