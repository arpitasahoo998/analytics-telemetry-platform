<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$nowMs = (int)(microtime(true) * 1000);
$sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

$startMs = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : $sevenDaysAgoMs;
$endMs = isset($_GET['end']) && is_numeric($_GET['end']) ? (int)$_GET['end'] : $nowMs;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10;

$stmt = mysqli_prepare($conn, "
    SELECT
        COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.path')), 'null'),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.path')), 'null'),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.name')), 'null'),
            '/'
        ) AS path,
        COALESCE(
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.title')), 'null'),
            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.title')), 'null'),
            'Page'
        ) AS title,
        COUNT(*) AS views,
        COUNT(DISTINCT anonymous_id) AS unique_visitors
    FROM events
    WHERE event_name = 'page_view'
      AND timestamp_epoch >= ?
      AND timestamp_epoch <= ?
    GROUP BY path, title
    ORDER BY views DESC
    LIMIT ?
");

mysqli_stmt_bind_param($stmt, 'iii', $startMs, $endMs, $limit);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$data = array_map(function ($row) {
    return [
        'path' => (string)($row['path'] ?? '/'),
        'title' => (string)($row['title'] ?? 'Page'),
        'views' => (int)($row['views'] ?? 0),
        'unique_visitors' => (int)($row['unique_visitors'] ?? 0),
    ];
}, $rows);

send_json(['status' => 'success', 'data' => $data]);
