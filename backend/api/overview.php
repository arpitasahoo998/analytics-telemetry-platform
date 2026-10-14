<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$nowMs = (int)(microtime(true) * 1000);
$sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

$startMs = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : $sevenDaysAgoMs;
$endMs = isset($_GET['end']) && is_numeric($_GET['end']) ? (int)$_GET['end'] : $nowMs;

if ($startMs > $endMs) {
    $temp = $startMs;
    $startMs = $endMs;
    $endMs = $temp;
}

// 1. Query events table
$stmt = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total_events,
        COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
        COUNT(DISTINCT anonymous_id) AS unique_visitors,
        COUNT(DISTINCT CASE WHEN session_id IS NOT NULL AND session_id != '' THEN session_id END) AS total_sessions,
        COUNT(CASE WHEN source = 'server' THEN 1 END) AS server_events,
        COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders_created
    FROM events
    WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
");

mysqli_stmt_bind_param($stmt, 'ii', $startMs, $endMs);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($stmt);

$totalEvents = (int)($row['total_events'] ?? 0);
$pageViews = (int)($row['page_views'] ?? 0);
$uniqueVisitors = (int)($row['unique_visitors'] ?? 0);
$totalSessions = (int)($row['total_sessions'] ?? 0);
$serverEvents = (int)($row['server_events'] ?? 0);
$ordersCreated = (int)($row['orders_created'] ?? 0);

// 2. Query orders table for total revenue
$orderStmt = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_revenue,
        COALESCE(AVG(total_amount), 0) AS avg_order_value
    FROM orders
    WHERE UNIX_TIMESTAMP(created_at) * 1000 >= ?
      AND UNIX_TIMESTAMP(created_at) * 1000 <= ?
");

mysqli_stmt_bind_param($orderStmt, 'ii', $startMs, $endMs);
mysqli_stmt_execute($orderStmt);
$orderRes = mysqli_stmt_get_result($orderStmt);
$orderRow = mysqli_fetch_assoc($orderRes) ?: [];
mysqli_stmt_close($orderStmt);

$totalRevenue = round((float)($orderRow['total_revenue'] ?? 0), 2);
$avgOrderValue = round((float)($orderRow['avg_order_value'] ?? 0), 2);

$conversionRate = $uniqueVisitors > 0
    ? round(($ordersCreated / $uniqueVisitors) * 100, 2)
    : 0.0;

send_json([
    'status' => 'success',
    'data' => [
        'total_events' => $totalEvents,
        'page_views' => $pageViews,
        'unique_visitors' => $uniqueVisitors,
        'total_sessions' => $totalSessions,
        'server_events' => $serverEvents,
        'orders_created' => $ordersCreated,
        'total_revenue' => $totalRevenue,
        'avg_order_value' => $avgOrderValue,
        'conversion_rate_percent' => $conversionRate,
        'time_window' => [
            'start_epoch_ms' => $startMs,
            'end_epoch_ms' => $endMs,
        ],
    ]
]);
