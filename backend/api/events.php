<?php

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/server_sdk.php';

handle_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['status' => 'error', 'message' => 'Method not allowed. Use POST.'], 405);
}

$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

if (empty($body) || !is_array($body)) {
    send_json(['status' => 'error', 'message' => 'Invalid or empty JSON body'], 400);
}

// Support single event or batch format
$eventsList = [];
if (isset($body['events']) && is_array($body['events'])) {
    $eventsList = $body['events'];
} elseif (array_is_list($body) && !empty($body) && is_array($body[0])) {
    $eventsList = $body;
} else {
    $eventsList = [$body];
}

$conn = get_db_connection();
$stmt = mysqli_prepare($conn, "
    INSERT IGNORE INTO events (
        event_id, event_name, timestamp, timestamp_epoch, source,
        anonymous_id, session_id, user_id, properties, context
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    send_json(['status' => 'error', 'message' => 'Failed to prepare database statement: ' . mysqli_error($conn)], 500);
}

$accepted = 0;
$inserted = 0;
$errors = [];

mysqli_begin_transaction($conn);

try {
    foreach ($eventsList as $idx => $evt) {
        if (!is_array($evt)) {
            $errors[] = ['index' => $idx, 'error' => 'Event must be a JSON object'];
            continue;
        }

        $eventName = trim((string)($evt['event_name'] ?? ''));
        $anonId = trim((string)($evt['anonymous_id'] ?? ''));

        if ($eventName === '' || $anonId === '') {
            $errors[] = ['index' => $idx, 'error' => 'event_name and anonymous_id are required'];
            continue;
        }

        $eventId = trim((string)($evt['event_id'] ?? '')) ?: generate_uuid_v4();
        $timestamp = (string)($evt['timestamp'] ?? gmdate('Y-m-d\TH:i:s.v\Z'));

        // Calculate epoch in milliseconds
        $time = strtotime($timestamp);
        $epochMs = $time !== false ? ($time * 1000) : (int)(microtime(true) * 1000);
        if (preg_match('/\.(\d{1,3})/', $timestamp, $m)) {
            $epochMs = ($time !== false ? $time * 1000 : 0) + (int)str_pad($m[1], 3, '0', STR_PAD_RIGHT);
        }

        $source = in_array($evt['source'] ?? '', ['browser', 'server'], true) ? $evt['source'] : 'browser';
        $sessionId = !empty($evt['session_id']) ? (string)$evt['session_id'] : null;
        $userId = !empty($evt['user_id']) ? (string)$evt['user_id'] : null;

        $properties = is_array($evt['properties'] ?? null) ? $evt['properties'] : [];
        $context = is_array($evt['context'] ?? null) ? $evt['context'] : [];

        // Enrich context with client IP if missing
        if (empty($context['ip_address'])) {
            $context['ip_address'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        if (empty($context['user_agent'])) {
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        }

        $propJson = json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ctxJson = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        mysqli_stmt_bind_param(
            $stmt,
            'sssissssss',
            $eventId,
            $eventName,
            $timestamp,
            $epochMs,
            $source,
            $anonId,
            $sessionId,
            $userId,
            $propJson,
            $ctxJson
        );

        mysqli_stmt_execute($stmt);
        $accepted++;

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $inserted++;
        }
    }

    mysqli_commit($conn);
} catch (\Throwable $e) {
    mysqli_rollback($conn);
    mysqli_stmt_close($stmt);
    send_json(['status' => 'error', 'message' => 'Ingestion transaction failed: ' . $e->getMessage()], 500);
}

mysqli_stmt_close($stmt);

send_json([
    'status' => 'success',
    'data' => [
        'received' => count($eventsList),
        'accepted' => $accepted,
        'inserted' => $inserted,
        'errors' => $errors,
    ]
], 201);
