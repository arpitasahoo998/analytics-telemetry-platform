<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$nowMs = (int)(microtime(true) * 1000);
$sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

$startMs = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : $sevenDaysAgoMs;
$endMs = isset($_GET['end']) && is_numeric($_GET['end']) ? (int)$_GET['end'] : $nowMs;
$interval = isset($_GET['interval']) && $_GET['interval'] === 'hour' ? 'hour' : 'day';

$dateFormat = $interval === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

$stmt = mysqli_prepare($conn, "
    SELECT
        DATE_FORMAT(FROM_UNIXTIME(timestamp_epoch / 1000), ?) AS period,
        COUNT(*) AS total_events,
        COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
        COUNT(DISTINCT anonymous_id) AS unique_visitors,
        COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders
    FROM events
    WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
    GROUP BY period
    ORDER BY period ASC
");

mysqli_stmt_bind_param($stmt, 'sii', $dateFormat, $startMs, $endMs);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$data = array_map(function ($row) {
    return [
        'period' => (string)($row['period'] ?? ''),
        'total_events' => (int)($row['total_events'] ?? 0),
        'page_views' => (int)($row['page_views'] ?? 0),
        'unique_visitors' => (int)($row['unique_visitors'] ?? 0),
        'orders' => (int)($row['orders'] ?? 0),
    ];
}, $rows);

send_json(['status' => 'success', 'data' => $data]);
