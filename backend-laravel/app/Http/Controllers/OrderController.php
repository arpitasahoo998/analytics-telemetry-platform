<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ServerAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::orderByDesc('id');
        
        if ($email = $request->query('email')) {
            $query->where('customer_email', $email);
        }
        if ($anonId = $request->query('anonymous_id')) {
            $query->where('anonymous_id', $anonId);
        }

        $orders = $query->limit(50)->get()->map(function ($order) {
            $items = $order->items;
            if (is_string($items)) {
                $decoded = json_decode($items, true);
                $order->items = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($items)) {
                $order->items = [];
            }
            return $order;
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ])->header('Connection', 'close');
    }

    public function show(string $orderId): JsonResponse
    {
        $order = Order::where('order_id', $orderId)->orWhere('id', $orderId)->first();
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404)->header('Connection', 'close');
        }

        $items = $order->items;
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $order->items = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($items)) {
            $order->items = [];
        }

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ])->header('Connection', 'close');
    }

    public function updateStatus(Request $request, string $orderId): JsonResponse
    {
        $payload = $request->json()->all();
        $newStatus = trim((string)($payload['status'] ?? 'delivered'));
        $allowed = ['placed', 'preparing', 'out_for_delivery', 'delivered', 'completed'];

        if (!in_array($newStatus, $allowed, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status. Allowed: ' . implode(', ', $allowed),
            ], 422)->header('Connection', 'close');
        }

        $order = Order::where('order_id', $orderId)->orWhere('id', $orderId)->first();
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404)->header('Connection', 'close');
        }

        $order->status = $newStatus;
        $order->save();

        if ($newStatus === 'delivered' || $newStatus === 'completed') {
            ServerAnalytics::track(
                eventName: 'order_delivered',
                properties: [
                    'order_id' => $order->order_id,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => $newStatus,
                ],
                userId: null,
                anonymousId: $order->anonymous_id,
                sessionId: $order->session_id,
                context: [
                    'fulfillment_type' => str_contains(strtolower($order->table_or_delivery), 'table') ? 'dine_in' : 'delivery',
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Order {$order->order_id} marked as {$newStatus}",
            'data' => $order,
        ])->header('Connection', 'close');
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Empty request body'], 400);
        }

        $customerName = trim((string)($payload['customer_name'] ?? ''));
        $customerEmail = trim((string)($payload['customer_email'] ?? ''));
        $tableOrDelivery = trim((string)($payload['table_or_delivery'] ?? 'Dine-in'));
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $totalAmount = (float)($payload['total_amount'] ?? 0.0);
        $currency = (string)($payload['currency'] ?? 'INR');

        $anonymousId = (string)($payload['anonymous_id'] ?? $request->header('X-Anonymous-Id') ?? 'anon_' . bin2hex(random_bytes(6)));
        $sessionId = (string)($payload['session_id'] ?? $request->header('X-Session-Id') ?? '');
        $userId = !empty($payload['user_id']) ? (string)$payload['user_id'] : null;

        if ($customerName === '' || empty($items) || $totalAmount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'customer_name, items, and total_amount > 0 are required'
            ], 422);
        }

        $orderId = 'ORD-' . strtoupper(substr(uniqid(), -6));

        // 1. Create order in MySQL via Eloquent (defaulting to preparing)
        $order = Order::create([
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'table_or_delivery' => $tableOrDelivery,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'items' => $items,
            'status' => 'preparing',
            'anonymous_id' => $anonymousId,
            'session_id' => $sessionId ?: null,
        ]);

        // 2. Track business-critical order creation via Server Tracking Service
        $tracked = ServerAnalytics::track(
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
                        'name' => $item['name'] ?? 'Dish',
                        'price' => $item['price'] ?? 0,
                        'quantity' => $item['quantity'] ?? 1,
                    ];
                }, $items),
            ],
            userId: $userId,
            anonymousId: $anonymousId,
            sessionId: $sessionId ?: null,
            context: [
                'checkout_flow' => 'web_checkout',
                'fulfillment_type' => str_contains(strtolower($tableOrDelivery), 'table') ? 'dine_in' : 'delivery',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Order created in Laravel backend and server event tracked',
            'data' => [
                'id' => $order->id,
                'order_id' => $orderId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'table_or_delivery' => $tableOrDelivery,
                'total_amount' => $totalAmount,
                'currency' => $currency,
                'items' => $items,
                'items_count' => count($items),
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
                'server_event_tracked' => $tracked,
            ]
        ], 201)->header('Connection', 'close');
    }
}

