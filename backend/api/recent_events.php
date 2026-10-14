<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$source = isset($_GET['source']) && in_array($_GET['source'], ['browser', 'server'], true) ? $_GET['source'] : null;
$eventName = isset($_GET['event_name']) && trim($_GET['event_name']) !== '' ? trim($_GET['event_name']) : null;

$where = [];
$params = [];
$types = '';

if ($source !== null) {
    $where[] = 'source = ?';
    $params[] = $source;
    $types .= 's';
}

if ($eventName !== null) {
    $where[] = 'event_name = ?';
    $params[] = $eventName;
    $types .= 's';
}

$sql = 'SELECT id, event_id, event_name, timestamp, timestamp_epoch, source, anonymous_id, session_id, user_id, properties, context, created_at FROM events';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY timestamp_epoch DESC, id DESC LIMIT ?';
$params[] = $limit;
$types .= 'i';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$data = array_map(function ($row) {
    $row['properties'] = json_decode($row['properties'] ?? '{}', true) ?: [];
    $row['context'] = json_decode($row['context'] ?? '{}', true) ?: [];
    return $row;
}, $rows);

send_json(['status' => 'success', 'data' => $data]);
