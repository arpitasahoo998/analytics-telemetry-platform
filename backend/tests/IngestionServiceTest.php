<?php

declare(strict_types=1);

namespace Analytics\Tests;

use PDO;
use Analytics\Database\Connection;
use Analytics\Repositories\EventRepository;
use Analytics\Services\IngestionService;

class IngestionServiceTest
{
    private PDO $db;
    private EventRepository $repo;
    private IngestionService $service;

    public function setUp(): void
    {
        // Use fresh in-memory SQLite database for isolated testing
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Connection::runMigrations($this->db);

        $this->repo = new EventRepository($this->db);
        $this->service = new IngestionService($this->repo);
    }

    public function testSingleEventValid(): void
    {
        $payload = [
            'event_id' => 'evt_test_101',
            'event_name' => 'page_view',
            'anonymous_id' => 'anon_123',
            'properties' => ['path' => '/menu'],
        ];

        $result = $this->service->processPayload($payload);

        assert($result['success'] === true, 'Expected processPayload to succeed');
        assert($result['accepted'] === 1, 'Expected 1 event accepted');
        assert($result['inserted'] === 1, 'Expected 1 event inserted');

        $count = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE event_id = 'evt_test_101'")->fetchColumn();
        assert($count === 1, 'Expected event to exist in database');
    }

    public function testBatchEvents(): void
    {
        $payload = [
            'events' => [
                [
                    'event_id' => 'evt_batch_1',
                    'event_name' => 'view_item',
                    'anonymous_id' => 'anon_1',
                    'properties' => ['item_id' => 10],
                ],
                [
                    'event_id' => 'evt_batch_2',
                    'event_name' => 'add_to_cart',
                    'anonymous_id' => 'anon_1',
                    'properties' => ['item_id' => 10, 'qty' => 1],
                ],
            ]
        ];

        $result = $this->service->processPayload($payload);

        assert($result['success'] === true, 'Expected batch to succeed');
        assert($result['accepted'] === 2, 'Expected 2 events accepted');
        assert($result['inserted'] === 2, 'Expected 2 events inserted');
    }

    public function testIdempotencyPreventsDuplicates(): void
    {
        $payload = [
            'event_id' => 'evt_idempotent_test',
            'event_name' => 'order_created',
            'anonymous_id' => 'anon_99',
            'properties' => ['amount' => 50],
        ];

        // First insert
        $res1 = $this->service->processPayload($payload);
        assert($res1['inserted'] === 1, 'First insert should write 1 row');

        // Duplicate insert with exact same event_id
        $res2 = $this->service->processPayload($payload);
        assert($res2['inserted'] === 0, 'Duplicate event_id must be ignored without error');

        $total = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE event_id = 'evt_idempotent_test'")->fetchColumn();
        assert($total === 1, 'Database must contain exactly 1 event record');
    }

    public function testRejectsMissingEventName(): void
    {
        $payload = [
            'anonymous_id' => 'anon_123',
            // Missing event_name
        ];

        $result = $this->service->processPayload($payload);
        assert($result['accepted'] === 0, 'Should not accept event without event_name');
        assert(!empty($result['errors']), 'Should report validation error');
    }

    public function testRejectsMissingAnonymousId(): void
    {
        $payload = [
            'event_name' => 'page_view',
            // Missing anonymous_id
        ];

        $result = $this->service->processPayload($payload);
        assert($result['accepted'] === 0, 'Should not accept event without anonymous_id');
        assert(!empty($result['errors']), 'Should report validation error');
    }
}
