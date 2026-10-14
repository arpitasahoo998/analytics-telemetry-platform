<?php

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/server_sdk.php';

handle_cors();

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = mysqli_query($conn, '
        SELECT id, order_id, customer_name, customer_email, table_or_delivery, total_amount, currency, items, status, created_at
        FROM orders
        ORDER BY id DESC
        LIMIT 20
    ');
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $data = array_map(function ($r) {
        $r['items'] = json_decode($r['items'] ?? '[]', true);
        return $r;
    }, $rows);
    send_json(['status' => 'success', 'data' => $data]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true);

    if (empty($body)) {
        send_json(['status' => 'error', 'message' => 'Empty request body'], 400);
    }

    $customerName = trim((string)($body['customer_name'] ?? ''));
    $customerEmail = trim((string)($body['customer_email'] ?? ''));
    $tableOrDelivery = trim((string)($body['table_or_delivery'] ?? 'Table 4'));
    $items = is_array($body['items'] ?? null) ? $body['items'] : [];
    $totalAmount = (float)($body['total_amount'] ?? 0.0);
    $currency = (string)($body['currency'] ?? 'USD');

    $anonymousId = (string)($body['anonymous_id'] ?? $_SERVER['HTTP_X_ANONYMOUS_ID'] ?? 'anon_' . bin2hex(random_bytes(6)));
    $sessionId = (string)($body['session_id'] ?? $_SERVER['HTTP_X_SESSION_ID'] ?? '');
    $userId = !empty($body['user_id']) ? (string)$body['user_id'] : null;

    if ($customerName === '' || empty($items) || $totalAmount <= 0) {
        send_json(['status' => 'error', 'message' => 'customer_name, items, and total_amount > 0 are required'], 422);
    }

    $orderId = 'ORD-' . strtoupper(substr(uniqid(), -6));
    $itemsJson = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $status = 'completed';

    // 1. Insert order into MySQL orders table
    $stmt = mysqli_prepare($conn, "
        INSERT INTO orders (
            order_id, customer_name, customer_email, table_or_delivery,
            total_amount, currency, items, status, anonymous_id, session_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        send_json(['status' => 'error', 'message' => 'Failed to prepare order query: ' . mysqli_error($conn)], 500);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssdsssss',
        $orderId,
        $customerName,
        $customerEmail,
        $tableOrDelivery,
        $totalAmount,
        $currency,
        $itemsJson,
        $status,
        $anonymousId,
        $sessionId
    );

    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        send_json(['status' => 'error', 'message' => 'Failed to save order'], 500);
    }

    // 2. Dispatch Server-Side Business Event via Core PHP Server SDK helper!
    $tracked = track_server_event(
        event_name: 'order_created',
        properties: [
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'table_or_delivery' => $tableOrDelivery,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'items_count' => count($items),
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'Dish',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }, $items),
        ],
        user_id: $userId,
        anonymous_id: $anonymousId,
        session_id: $sessionId ?: null,
        context: [
            'checkout_flow' => 'web_checkout',
            'fulfillment_type' => str_contains(strtolower($tableOrDelivery), 'table') ? 'dine_in' : 'delivery',
        ]
    );

    send_json([
        'status' => 'success',
        'message' => 'Order processed and server business event tracked',
        'data' => [
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'items_count' => count($items),
            'server_event_tracked' => $tracked,
        ]
    ], 201);
}

send_json(['status' => 'error', 'message' => 'Method not allowed'], 405);
