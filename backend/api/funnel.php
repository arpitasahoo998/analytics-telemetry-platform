<?php

require_once dirname(__DIR__) . '/db.php';
handle_cors();

$conn = get_db_connection();

$nowMs = (int)(microtime(true) * 1000);
$sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

$startMs = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : $sevenDaysAgoMs;
$endMs = isset($_GET['end']) && is_numeric($_GET['end']) ? (int)$_GET['end'] : $nowMs;

$steps = [
    ['name' => 'Visit Menu / Page', 'event' => 'page_view'],
    ['name' => 'View Dish Details', 'event' => 'view_item'],
    ['name' => 'Add Dish to Cart', 'event' => 'add_to_cart'],
    ['name' => 'Initiate Checkout', 'event' => 'checkout_started'],
    ['name' => 'Order Completed', 'event' => 'order_created'],
];

$results = [];
$firstCount = 0;
$prevCount = 0;

$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT anonymous_id) AS visitors, COUNT(*) AS total_hits
    FROM events
    WHERE event_name = ?
      AND timestamp_epoch >= ?
      AND timestamp_epoch <= ?
");

foreach ($steps as $idx => $step) {
    mysqli_stmt_bind_param($stmt, 'sii', $step['event'], $startMs, $endMs);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res) ?: [];
    $visitors = (int)($row['visitors'] ?? 0);
    $totalHits = (int)($row['total_hits'] ?? 0);

    if ($idx === 0) {
        $firstCount = $visitors;
        $prevCount = $visitors;
        $stepConversion = 100.0;
        $overallConversion = 100.0;
    } else {
        $stepConversion = $prevCount > 0 ? round(($visitors / $prevCount) * 100, 1) : 0.0;
        $overallConversion = $firstCount > 0 ? round(($visitors / $firstCount) * 100, 1) : 0.0;
        $prevCount = $visitors;
    }

    $results[] = [
        'step' => $idx + 1,
        'name' => $step['name'],
        'event_name' => $step['event'],
        'visitors' => $visitors,
        'total_hits' => $totalHits,
        'step_conversion_pct' => $stepConversion,
        'overall_conversion_pct' => $overallConversion,
        'dropoff_pct' => round(100.0 - $stepConversion, 1),
    ];
}

mysqli_stmt_close($stmt);

send_json(['status' => 'success', 'data' => $results]);
