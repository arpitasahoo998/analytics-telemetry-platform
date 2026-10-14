<?php

declare(strict_types=1);

namespace Analytics\Tests;

use Analytics\Sdk\AnalyticsClient;
use Analytics\Sdk\Transport\MemoryTransport;
use Analytics\Sdk\Transport\DirectIngestionTransport;
use Analytics\Services\IngestionService;
use Analytics\Repositories\EventRepository;
use Analytics\Database\Connection;
use PDO;

class ServerSdkTest
{
    private MemoryTransport $transport;
    private AnalyticsClient $client;

    public function setUp(): void
    {
        $this->transport = new MemoryTransport();
        $this->client = new AnalyticsClient([
            'transport' => $this->transport,
            'default_context' => [
                'environment' => 'testing',
            ],
        ]);
    }

    public function testTrackBuildsStandardEventSchema(): void
    {
        $success = $this->client->track(
            eventName: 'order_created',
            properties: [
                'order_id' => 'ORD-888',
                'amount' => 39.99,
            ],
            userId: 'user_42',
            anonymousId: 'anon_777',
            context: ['tag' => 'vip'],
            sessionId: 'sess_999'
        );

        assert($success === true, 'Track should return true');
        assert($this->transport->count() === 1, 'Transport should receive 1 event');

        $sent = $this->transport->getSentPayloads()[0];

        assert($sent['event_name'] === 'order_created', 'Expected event_name order_created');
        assert($sent['source'] === 'server', 'Expected source server');
        assert($sent['user_id'] === 'user_42', 'Expected user_id user_42');
        assert($sent['anonymous_id'] === 'anon_777', 'Expected anonymous_id anon_777');
        assert($sent['session_id'] === 'sess_999', 'Expected session_id sess_999');
        assert($sent['properties']['order_id'] === 'ORD-888', 'Expected order_id in properties');
        assert($sent['context']['environment'] === 'testing', 'Expected context environment testing');
        assert($sent['context']['tag'] === 'vip', 'Expected context tag vip');
        assert(!empty($sent['event_id']), 'Expected generated UUID event_id');
        assert(!empty($sent['timestamp']), 'Expected ISO timestamp');
    }

    public function testIdentifyHelper(): void
    {
        $this->client->identify('user_50', ['plan' => 'pro']);

        assert($this->transport->count() === 1, 'Identify should send 1 event');
        $sent = $this->transport->getSentPayloads()[0];
        assert($sent['event_name'] === 'identify');
        assert($sent['user_id'] === 'user_50');
        assert($sent['properties']['traits']['plan'] === 'pro');
    }

    public function testDirectIngestionTransportIntegration(): void
    {
        $db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Connection::runMigrations($db);

        $repo = new EventRepository($db);
        $service = new IngestionService($repo);
        $directTransport = new DirectIngestionTransport($service);

        $directClient = new AnalyticsClient(['transport' => $directTransport]);

        $res = $directClient->track('payment_received', ['amount' => 100], 'user_1', 'anon_1');
        assert($res === true, 'Direct transport track should succeed');

        $count = (int)$db->query("SELECT COUNT(*) FROM events WHERE event_name = 'payment_received'")->fetchColumn();
        assert($count === 1, 'Event should be directly stored in database');
    }
}
