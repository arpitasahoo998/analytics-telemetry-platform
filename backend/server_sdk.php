<?php

// Core PHP Server Analytics Tracking Helper
require_once __DIR__ . '/db.php';

function generate_uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Track a business event directly from the server.
 *
 * @param string $event_name e.g. 'order_created', 'payment_confirmed'
 * @param array $properties Business properties
 * @param string|null $user_id
 * @param string|null $anonymous_id
 * @param string|null $session_id
 * @param array $context
 * @return bool True if successfully recorded
 */
function track_server_event(
    string $event_name,
    array $properties = [],
    ?string $user_id = null,
    ?string $anonymous_id = null,
    ?string $session_id = null,
    array $context = []
): bool {
    try {
        $conn = get_db_connection();

        $event_id = generate_uuid_v4();
        $timestamp = gmdate('Y-m-d\TH:i:s.v\Z');
        $timestamp_epoch = (int)(microtime(true) * 1000);
        $source = 'server';

        // Stitched or generated anonymous ID
        $resolved_anon_id = $anonymous_id ?: ($user_id ? 'user_' . $user_id : 'srv_' . bin2hex(random_bytes(6)));

        // Merge server environment context
        $server_context = array_merge([
            'sdk_name' => 'analytics-core-php-sdk',
            'sdk_version' => '1.0.0',
            'runtime' => 'PHP ' . PHP_VERSION,
            'server_hostname' => gethostname() ?: 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Server',
        ], $context);

        $properties_json = json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $context_json = json_encode($server_context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmt = mysqli_prepare($conn, "
            INSERT IGNORE INTO events (
                event_id, event_name, timestamp, timestamp_epoch, source,
                anonymous_id, session_id, user_id, properties, context
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log("[Analytics Server SDK] Prepare failed: " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sssissssss',
            $event_id,
            $event_name,
            $timestamp,
            $timestamp_epoch,
            $source,
            $resolved_anon_id,
            $session_id,
            $user_id,
            $properties_json,
            $context_json
        );

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $success;
    } catch (\Throwable $e) {
        error_log("[Analytics Server SDK] Error tracking event: " . $e->getMessage());
        return false;
    }
}

/**
 * Identify a user from the server.
 */
function identify_server_user(
    string $user_id,
    array $traits = [],
    ?string $anonymous_id = null,
    ?string $session_id = null
): bool {
    return track_server_event(
        'identify',
        ['traits' => $traits],
        $user_id,
        $anonymous_id,
        $session_id
    );
}
