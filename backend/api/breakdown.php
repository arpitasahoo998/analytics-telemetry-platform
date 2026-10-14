<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$nowMs = (int)(microtime(true) * 1000);
$sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

$startMs = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : $sevenDaysAgoMs;
$endMs = isset($_GET['end']) && is_numeric($_GET['end']) ? (int)$_GET['end'] : $nowMs;

$stmt = mysqli_prepare($conn, "
    SELECT
        event_name,
        source,
        COUNT(*) AS count,
        COUNT(DISTINCT anonymous_id) AS unique_users
    FROM events
    WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
    GROUP BY event_name, source
    ORDER BY count DESC
");

mysqli_stmt_bind_param($stmt, 'ii', $startMs, $endMs);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$data = array_map(function ($row) {
    return [
        'event_name' => (string)$row['event_name'],
        'source' => (string)$row['source'],
        'count' => (int)$row['count'],
        'unique_users' => (int)$row['unique_users'],
    ];
}, $rows);

send_json(['status' => 'success', 'data' => $data]);
