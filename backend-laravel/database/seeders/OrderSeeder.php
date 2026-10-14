<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\ServerAnalytics;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'order_id' => 'ORD-B71A92',
                'customer_name' => 'Aarav Sharma',
                'customer_email' => 'aarav.sharma@example.com',
                'table_or_delivery' => 'Table 4 (Indoor AC)',
                'total_amount' => 1176.00,
                'currency' => 'INR',
                'status' => 'delivered',
                'items' => [
                    ['id' => 'dish-ind-1', 'name' => 'Butter Chicken & Garlic Naan', 'price' => 380, 'quantity' => 2, 'isVeg' => false],
                    ['id' => 'dish-ita-1', 'name' => 'Pizza Margherita Verace DOP', 'price' => 360, 'quantity' => 1, 'isVeg' => true],
                ],
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(4),
            ],
            [
                'order_id' => 'ORD-C42E81',
                'customer_name' => 'Priya Patel',
                'customer_email' => 'priya.patel@example.com',
                'table_or_delivery' => 'Table 8 (Rooftop Garden)',
                'total_amount' => 746.00,
                'currency' => 'INR',
                'status' => 'delivered',
                'items' => [
                    ['id' => 'dish-mex-1', 'name' => 'Birria Quesatacos con Consomé', 'price' => 390, 'quantity' => 1, 'isVeg' => false],
                    ['id' => 'dish-chi-1', 'name' => 'Dim Sum Steamed Dumplings', 'price' => 320, 'quantity' => 1, 'isVeg' => true],
                ],
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(2),
            ],
            [
                'order_id' => 'ORD-D83F15',
                'customer_name' => 'Vikram Malhotra',
                'customer_email' => 'vikram.m@example.com',
                'table_or_delivery' => 'Express Home Delivery',
                'total_amount' => 1008.00,
                'currency' => 'INR',
                'status' => 'out_for_delivery',
                'items' => [
                    ['id' => 'dish-ind-3', 'name' => 'Hyderabadi Dum Biryani', 'price' => 420, 'quantity' => 1, 'isVeg' => false],
                    ['id' => 'dish-ita-2', 'name' => 'Truffle Tagliolini al Parmigiano', 'price' => 480, 'quantity' => 1, 'isVeg' => true],
                    ['id' => 'dish-chi-4', 'name' => 'Crispy Vegetable Spring Rolls', 'price' => 210, 'quantity' => 1, 'isVeg' => true],
                ],
                'created_at' => now()->subMinutes(45),
                'updated_at' => now()->subMinutes(20),
            ],
            [
                'order_id' => 'ORD-E94A30',
                'customer_name' => 'Ananya Roy',
                'customer_email' => 'ananya.roy@example.com',
                'table_or_delivery' => 'Table 2 (Indoor AC)',
                'total_amount' => 651.00,
                'currency' => 'INR',
                'status' => 'preparing',
                'items' => [
                    ['id' => 'dish-ind-2', 'name' => 'Paneer Tikka Masala Handi', 'price' => 340, 'quantity' => 1, 'isVeg' => true],
                    ['id' => 'dish-chi-2', 'name' => 'Schezwan Chili Hakka Noodles', 'price' => 280, 'quantity' => 1, 'isVeg' => true],
                ],
                'created_at' => now()->subMinutes(12),
                'updated_at' => now()->subMinutes(12),
            ],
        ];

        foreach ($orders as $orderData) {
            $order = Order::updateOrCreate(
                ['order_id' => $orderData['order_id']],
                [
                    'customer_name' => $orderData['customer_name'],
                    'customer_email' => $orderData['customer_email'],
                    'table_or_delivery' => $orderData['table_or_delivery'],
                    'total_amount' => $orderData['total_amount'],
                    'currency' => $orderData['currency'],
                    'status' => $orderData['status'],
                    'items' => $orderData['items'],
                    'anonymous_id' => 'anon_' . substr(md5($orderData['customer_email']), 0, 10),
                    'session_id' => 'sess_' . substr(md5($orderData['customer_name']), 0, 10),
                    'created_at' => $orderData['created_at'],
                    'updated_at' => $orderData['updated_at'],
                ]
            );

            // Also emit server telemetry event for analytics consistency
            ServerAnalytics::track(
                eventName: 'order_created',
                properties: [
                    'order_id' => $order->order_id,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'table_or_delivery' => $order->table_or_delivery,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'items_count' => count($order->items),
                    'items' => $order->items,
                ],
                userId: null,
                anonymousId: $order->anonymous_id,
                sessionId: $order->session_id,
                context: [
                    'fulfillment_type' => str_contains(strtolower($order->table_or_delivery), 'table') ? 'dine_in' : 'delivery',
                ]
            );

            if ($order->status === 'delivered') {
                ServerAnalytics::track(
                    eventName: 'order_delivered',
                    properties: [
                        'order_id' => $order->order_id,
                        'customer_name' => $order->customer_name,
                        'customer_email' => $order->customer_email,
                        'total_amount' => $order->total_amount,
                        'currency' => $order->currency,
                        'status' => 'delivered',
                    ],
                    userId: null,
                    anonymousId: $order->anonymous_id,
                    sessionId: $order->session_id,
                );
            }
        }
    }
}
