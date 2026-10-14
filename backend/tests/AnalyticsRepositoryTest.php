<?php

declare(strict_types=1);

namespace Analytics\Tests;

use PDO;
use Analytics\Database\Connection;
use Analytics\Repositories\EventRepository;
use Analytics\Repositories\AnalyticsRepository;

class AnalyticsRepositoryTest
{
    private PDO $db;
    private EventRepository $eventRepo;
    private AnalyticsRepository $analyticsRepo;

    public function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Connection::runMigrations($this->db);

        $this->eventRepo = new EventRepository($this->db);
        $this->analyticsRepo = new AnalyticsRepository($this->db);

        $this->seedTestData();
    }

    private function seedTestData(): void
    {
        $now = time();
        $t1 = date('Y-m-d\TH:i:s.000\Z', $now - 3600); // 1 hour ago
        $t2 = date('Y-m-d\TH:i:s.000\Z', $now - 1800); // 30 mins ago

        $events = [
            [
                'event_id' => 'evt_1',
                'event_name' => 'page_view',
                'timestamp' => $t1,
                'source' => 'browser',
                'anonymous_id' => 'user_alpha',
                'session_id' => 'sess_1',
                'context' => ['path' => '/menu', 'title' => 'Menu Page'],
            ],
            [
                'event_id' => 'evt_2',
                'event_name' => 'view_item',
                'timestamp' => $t1,
                'source' => 'browser',
                'anonymous_id' => 'user_alpha',
                'session_id' => 'sess_1',
                'properties' => ['item' => 'Pizza Margherita'],
            ],
            [
                'event_id' => 'evt_3',
                'event_name' => 'add_to_cart',
                'timestamp' => $t2,
                'source' => 'browser',
                'anonymous_id' => 'user_alpha',
                'session_id' => 'sess_1',
                'properties' => ['item' => 'Pizza Margherita', 'price' => 16.50],
            ],
            [
                'event_id' => 'evt_4',
                'event_name' => 'order_created',
                'timestamp' => $t2,
                'source' => 'server',
                'anonymous_id' => 'user_alpha',
                'session_id' => 'sess_1',
                'properties' => ['order_id' => 'ORD-12345', 'total' => 16.50],
            ],
            [
                'event_id' => 'evt_5',
                'event_name' => 'page_view',
                'timestamp' => $t2,
                'source' => 'browser',
                'anonymous_id' => 'user_beta',
                'session_id' => 'sess_2',
                'context' => ['path' => '/about', 'title' => 'About Us'],
            ],
        ];

        $this->eventRepo->insertBatch($events);

        // Seed corresponding order in orders table
        $this->db->prepare("
            INSERT INTO orders (order_id, customer_name, customer_email, total_amount, items, created_at)
            VALUES ('ORD-12345', 'Alice', 'alice@test.com', 16.50, '[]', datetime('now'))
        ")->execute();
    }

    public function testOverviewMetrics(): void
    {
        $startMs = (time() - 86400) * 1000;
        $endMs = (time() + 86400) * 1000;

        $overview = $this->analyticsRepo->getOverview($startMs, $endMs);

        assert($overview['total_events'] === 5, 'Expected 5 total events');
        assert($overview['page_views'] === 2, 'Expected 2 page views');
        assert($overview['unique_visitors'] === 2, 'Expected 2 unique visitors (user_alpha, user_beta)');
        assert($overview['total_sessions'] === 2, 'Expected 2 sessions');
        assert($overview['orders_created'] === 1, 'Expected 1 order created');
        assert($overview['server_events'] === 1, 'Expected 1 server event');
        assert($overview['total_revenue'] === 16.50, 'Expected revenue of 16.50');
        assert($overview['conversion_rate_percent'] === 50.0, 'Expected conversion rate of 50% (1 order / 2 unique visitors)');
    }

    public function testPopularPages(): void
    {
        $startMs = (time() - 86400) * 1000;
        $endMs = (time() + 86400) * 1000;

        $pages = $this->analyticsRepo->getPopularPages($startMs, $endMs);

        assert(count($pages) === 2, 'Expected 2 distinct pages');
        $paths = array_column($pages, 'path');
        assert(in_array('/menu', $paths), 'Expected /menu in popular pages');
        assert(in_array('/about', $paths), 'Expected /about in popular pages');
    }

    public function testConversionFunnel(): void
    {
        $startMs = (time() - 86400) * 1000;
        $endMs = (time() + 86400) * 1000;

        $funnel = $this->analyticsRepo->getConversionFunnel($startMs, $endMs);

        assert(count($funnel) === 5, 'Expected 5 funnel steps');
        assert($funnel[0]['visitors'] === 2, 'Step 1 (page_view) should have 2 visitors');
        assert($funnel[1]['visitors'] === 1, 'Step 2 (view_item) should have 1 visitor');
        assert($funnel[2]['visitors'] === 1, 'Step 3 (add_to_cart) should have 1 visitor');
        assert($funnel[4]['visitors'] === 1, 'Step 5 (order_created) should have 1 visitor');
    }
}
