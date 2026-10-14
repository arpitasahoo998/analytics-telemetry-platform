<?php

// Core PHP Automated Test Suite using procedural mysqli
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../init_db.php';
require_once __DIR__ . '/../server_sdk.php';

echo "\n======================================================\n";
echo "   Running Analytics Platform Core PHP Test Suite\n";
echo "======================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test(bool $condition, string $testName) {
    global $passed, $failed;
    echo "  - {$testName}... ";
    if ($condition) {
        echo "[PASS]\n";
        $passed++;
    } else {
        echo "[FAIL]\n";
        $failed++;
    }
}

// 1. Test database initialization
init_database();
$conn = get_db_connection();
assert_test($conn !== null && mysqli_ping($conn), 'Database connection active');

// 2. Test server tracking SDK
$testEventId = 'test_' . bin2hex(random_bytes(6));
$trackResult = track_server_event(
    event_name: 'test_order_event',
    properties: ['amount' => 49.99, 'currency' => 'USD'],
    user_id: 'usr_test_101',
    anonymous_id: 'anon_test_101',
    session_id: 'sess_test_101',
    context: ['test_run' => true]
);
assert_test($trackResult === true, 'Server SDK track_server_event returns true');

// Verify recorded in MySQL events table
$res = mysqli_query($conn, "SELECT COUNT(*) FROM events WHERE event_name = 'test_order_event'");
$count = (int)mysqli_fetch_row($res)[0];
assert_test($count >= 1, 'Server event persisted in MySQL events table');

// 3. Test user identify helper
$identResult = identify_server_user('usr_test_101', ['plan' => 'vip'], 'anon_test_101');
assert_test($identResult === true, 'Server SDK identify_server_user returns true');

// 4. Test Ingestion idempotency (duplicate event_id ignored)
$idempotentId = 'idempotent_' . bin2hex(random_bytes(4));
$stmt = mysqli_prepare($conn, "
    INSERT IGNORE INTO events (
        event_id, event_name, timestamp, timestamp_epoch, source,
        anonymous_id, session_id, user_id, properties, context
    ) VALUES (?, 'dup_test', '2026-10-13T00:00:00Z', 1700000000000, 'browser', 'anon_dup', null, null, '{}', '{}')
");
mysqli_stmt_bind_param($stmt, 's', $idempotentId);
mysqli_stmt_execute($stmt);
$aff1 = mysqli_stmt_affected_rows($stmt);

// Duplicate insert
mysqli_stmt_execute($stmt);
$aff2 = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

assert_test($aff1 === 1 && $aff2 === 0, 'Idempotency: duplicate event_id is ignored by MySQL');

// 5. Test Overview metrics query
$start = (time() - 86400 * 30) * 1000;
$end = (time() + 86400) * 1000;
$stmt = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total_events,
        COUNT(DISTINCT anonymous_id) AS unique_visitors
    FROM events
    WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
");
mysqli_stmt_bind_param($stmt, 'ii', $start, $end);
mysqli_stmt_execute($stmt);
$overviewRes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

assert_test((int)$overviewRes['total_events'] > 0, 'Overview aggregates total events');
assert_test((int)$overviewRes['unique_visitors'] > 0, 'Overview aggregates unique visitors');

// 6. Test Funnel step query
$funnelStmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT anonymous_id) AS visitors
    FROM events
    WHERE event_name = 'page_view' AND timestamp_epoch >= ? AND timestamp_epoch <= ?
");
mysqli_stmt_bind_param($funnelStmt, 'ii', $start, $end);
mysqli_stmt_execute($funnelStmt);
$funnelRes = mysqli_fetch_assoc(mysqli_stmt_get_result($funnelStmt));
mysqli_stmt_close($funnelStmt);
assert_test((int)$funnelRes['visitors'] > 0, 'Funnel query calculates distinct visitors');

echo "\n------------------------------------------------------\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo "======================================================\n\n";

exit($failed === 0 ? 0 : 1);
