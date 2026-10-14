<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    public function test_order_creation_persists_order_and_triggers_server_telemetry()
    {
        $payload = [
            'customer_name' => 'Aarav Sharma',
            'customer_email' => 'aarav.sharma@example.com',
            'customer_phone' => '+91 98765 43210',
            'table_or_delivery' => 'Table 4',
            'items' => [
                [
                    'id' => 'ind-1',
                    'name' => 'Butter Chicken',
                    'price' => 380,
                    'quantity' => 2,
                    'cuisine' => 'indian'
                ],
                [
                    'id' => 'ita-1',
                    'name' => 'Margherita Pizza DOC',
                    'price' => 360,
                    'quantity' => 1,
                    'cuisine' => 'italian'
                ]
            ],
            'total_amount' => 1176.00,
            'currency' => 'INR',
            'session_id' => 'sess_test_order_' . uniqid(),
            'anonymous_id' => 'anon_test_order_' . uniqid(),
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'order_id',
                    'customer_name',
                    'total_amount',
                    'currency',
                    'items_count',
                    'server_event_tracked'
                ]
            ]);

        $orderId = $response->json('data.order_id');

        // Check order exists in orders table
        $this->assertDatabaseHas('orders', [
            'order_id' => $orderId,
            'customer_name' => 'Aarav Sharma',
            'total_amount' => 1176.00,
        ]);

        // Check that ServerAnalytics tracked the 'order_created' event in MySQL
        $serverEvent = Event::where('event_name', 'order_created')
            ->whereJsonContains('properties->order_id', $orderId)
            ->first();

        $this->assertNotNull($serverEvent);
        $this->assertEquals('server', $serverEvent->source);
        $this->assertEquals(1176.00, $serverEvent->properties['total_amount']);
        $this->assertEquals(2, $serverEvent->properties['items_count']);
    }
}
