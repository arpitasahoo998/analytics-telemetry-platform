<?php

declare(strict_types=1);

namespace Analytics\Controllers;

use Analytics\Http\Request;
use Analytics\Http\Response;
use Analytics\Database\Connection;
use Analytics\Sdk\AnalyticsClient;
use Analytics\Sdk\Transport\DirectIngestionTransport;
use Analytics\Services\IngestionService;
use mysqli;

class OrderController
{
    private mysqli $db;
    private AnalyticsClient $analytics;

    public function __construct(?mysqli $db = null, ?AnalyticsClient $analytics = null)
    {
        $this->db = $db ?? Connection::get();
        // Use DirectIngestionTransport in local mode to avoid single-thread php -S deadlock
        $this->analytics = $analytics ?? new AnalyticsClient([
            'transport' => new DirectIngestionTransport(new IngestionService()),
            'debug' => true,
        ]);
    }

    public function create(Request $request): Response
    {
        $body = $request->getBody();

        if (empty($body)) {
            return Response::error('Empty request payload', 400);
        }

        $customerName = trim((string)($body['customer_name'] ?? ''));
        $customerEmail = trim((string)($body['customer_email'] ?? ''));
        $tableOrDelivery = trim((string)($body['table_or_delivery'] ?? 'Table 4'));
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $totalAmount = (float)($body['total_amount'] ?? 0.0);
        $currency = (string)($body['currency'] ?? 'USD');

        // Client context passed from browser
        $anonymousId = (string)($body['anonymous_id'] ?? $request->getHeader('x-anonymous-id', 'anon_' . bin2hex(random_bytes(6))));
        $sessionId = (string)($body['session_id'] ?? $request->getHeader('x-session-id', ''));
        $userId = !empty($body['user_id']) ? (string)$body['user_id'] : null;

        if ($customerName === '' || empty($items) || $totalAmount <= 0) {
            return Response::error('Invalid order: customer_name, items, and total_amount > 0 are required', 422);
        }

        $orderId = 'ORD-' . strtoupper(substr(uniqid(), -6));

        try {
            // 1. Persist business order
            $stmt = $this->db->prepare('
                INSERT INTO orders (
                    order_id,
                    customer_name,
                    customer_email,
                    table_or_delivery,
                    total_amount,
                    currency,
                    items,
                    status,
                    anonymous_id,
                    session_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $itemsJson = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $status = 'completed';

            $stmt->bind_param(
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

            $stmt->execute();
            $stmt->close();

            // 2. Track business event from the server using the Server Analytics SDK
            $tracked = $this->analytics->track(
                eventName: 'order_created',
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
                            'name' => $item['name'] ?? 'Unknown Dish',
                            'price' => $item['price'] ?? 0,
                            'quantity' => $item['quantity'] ?? 1,
                        ];
                    }, $items),
                ],
                userId: $userId,
                anonymousId: $anonymousId,
                context: [
                    'checkout_flow' => 'web_checkout',
                    'fulfillment_type' => str_contains(strtolower($tableOrDelivery), 'table') ? 'dine_in' : 'delivery',
                ],
                sessionId: $sessionId ?: null
            );

            return Response::json([
                'status' => 'success',
                'message' => 'Order created and server event tracked successfully',
                'data' => [
                    'order_id' => $orderId,
                    'customer_name' => $customerName,
                    'total_amount' => $totalAmount,
                    'currency' => $currency,
                    'items_count' => count($items),
                    'server_event_tracked' => $tracked,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return Response::error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    public function list(Request $request): Response
    {
        $result = $this->db->query('
            SELECT id, order_id, customer_name, customer_email, table_or_delivery, total_amount, currency, items, status, created_at
            FROM orders
            ORDER BY id DESC
            LIMIT 20
        ');
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        return Response::json([
            'status' => 'success',
            'data' => array_map(function ($o) {
                $o['items'] = json_decode($o['items'] ?? '[]', true);
                return $o;
            }, $orders),
        ]);
    }
}
