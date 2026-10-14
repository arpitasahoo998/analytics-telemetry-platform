<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class EventIngestionTest extends TestCase
{
    public function test_single_event_can_be_ingested()
    {
        $payload = [
            'event_id' => (string) Str::uuid(),
            'event_name' => 'button_clicked',
            'session_id' => 'sess_' . uniqid(),
            'anonymous_id' => 'anon_' . uniqid(),
            'source' => 'browser',
            'properties' => [
                'button_name' => 'Explore Menu',
                'cuisine' => 'indian'
            ]
        ];

        $response = $this->postJson('/api/v1/events', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'accepted' => 1
                ]
            ]);

        $this->assertDatabaseHas('events', [
            'event_id' => $payload['event_id'],
            'event_name' => 'button_clicked',
            'source' => 'browser'
        ]);
    }

    public function test_batch_events_can_be_ingested()
    {
        $payload = [
            'events' => [
                [
                    'event_id' => (string) Str::uuid(),
                    'event_name' => 'page_view',
                    'session_id' => 'sess_1',
                    'anonymous_id' => 'anon_1',
                    'source' => 'browser',
                    'properties' => ['path' => '/menu'],
                ],
                [
                    'event_id' => (string) Str::uuid(),
                    'event_name' => 'add_to_cart',
                    'session_id' => 'sess_1',
                    'anonymous_id' => 'anon_1',
                    'source' => 'browser',
                    'properties' => ['item' => 'Butter Chicken', 'price' => 380]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/events', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'accepted' => 2
                ]
            ]);
    }

    public function test_duplicate_event_is_ignored_idempotently()
    {
        $uuid = (string) Str::uuid();
        $payload = [
            'event_id' => $uuid,
            'event_name' => 'dish_viewed',
            'session_id' => 'sess_dup',
            'anonymous_id' => 'anon_dup',
            'source' => 'browser',
        ];

        // First ingest
        $res1 = $this->postJson('/api/v1/events', $payload);
        $res1->assertStatus(201);

        // Second ingest with exact same event_id
        $res2 = $this->postJson('/api/v1/events', $payload);
        $res2->assertStatus(201);

        // Database should only have 1 row with this event_id
        $this->assertEquals(1, \App\Models\Event::where('event_id', $uuid)->count());
    }

    public function test_missing_event_name_fails_validation()
    {
        $payload = [
            'event_id' => (string) Str::uuid(),
            'session_id' => 'sess_123',
            // Missing event_name & anonymous_id
        ];

        $response = $this->postJson('/api/v1/events', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error'
            ]);
    }
}
