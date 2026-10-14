<?php

declare(strict_types=1);

namespace Analytics\Repositories;

use mysqli;
use Analytics\Database\Connection;

class AnalyticsRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? Connection::get();
    }

    /**
     * Get high-level KPI overview for the given time window.
     */
    public function getOverview(int $startEpochMs, int $endEpochMs): array
    {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = \'page_view\' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(DISTINCT CASE WHEN session_id IS NOT NULL AND session_id != \'\' THEN session_id END) AS total_sessions,
                COUNT(CASE WHEN source = \'server\' THEN 1 END) AS server_events,
                COUNT(CASE WHEN event_name = \'order_created\' THEN 1 END) AS orders_created
            FROM events
            WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
        ');
        $stmt->bind_param('ii', $startEpochMs, $endEpochMs);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $totalEvents = (int)($row['total_events'] ?? 0);
        $pageViews = (int)($row['page_views'] ?? 0);
        $uniqueVisitors = (int)($row['unique_visitors'] ?? 0);
        $totalSessions = (int)($row['total_sessions'] ?? 0);
        $serverEvents = (int)($row['server_events'] ?? 0);
        $ordersCreated = (int)($row['orders_created'] ?? 0);

        // Fetch total order revenue from orders table within time window
        $orderStmt = $this->db->prepare('
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COALESCE(AVG(total_amount), 0) AS avg_order_value
            FROM orders
            WHERE UNIX_TIMESTAMP(created_at) * 1000 >= ?
              AND UNIX_TIMESTAMP(created_at) * 1000 <= ?
        ');
        $orderStmt->bind_param('ii', $startEpochMs, $endEpochMs);
        $orderStmt->execute();
        $orderRow = $orderStmt->get_result()->fetch_assoc() ?: [];
        $orderStmt->close();

        $totalRevenue = round((float)($orderRow['total_revenue'] ?? 0), 2);
        $avgOrderValue = round((float)($orderRow['avg_order_value'] ?? 0), 2);

        $conversionRate = $uniqueVisitors > 0
            ? round(($ordersCreated / $uniqueVisitors) * 100, 2)
            : 0.0;

        return [
            'total_events' => $totalEvents,
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'total_sessions' => $totalSessions,
            'server_events' => $serverEvents,
            'orders_created' => $ordersCreated,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'conversion_rate_percent' => $conversionRate,
            'time_window' => [
                'start_epoch_ms' => $startEpochMs,
                'end_epoch_ms' => $endEpochMs,
            ],
        ];
    }

    /**
     * Get timeseries data aggregated by day or hour.
     */
    public function getTimeseries(int $startEpochMs, int $endEpochMs, string $interval = 'day'): array
    {
        $dateFormat = $interval === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        $stmt = $this->db->prepare('
            SELECT
                DATE_FORMAT(FROM_UNIXTIME(timestamp_epoch / 1000), ?) AS period,
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = \'page_view\' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(CASE WHEN event_name = \'order_created\' THEN 1 END) AS orders
            FROM events
            WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
            GROUP BY period
            ORDER BY period ASC
        ');
        $stmt->bind_param('sii', $dateFormat, $startEpochMs, $endEpochMs);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(function ($row) {
            return [
                'period' => (string)($row['period'] ?? ''),
                'total_events' => (int)($row['total_events'] ?? 0),
                'page_views' => (int)($row['page_views'] ?? 0),
                'unique_visitors' => (int)($row['unique_visitors'] ?? 0),
                'orders' => (int)($row['orders'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Get most popular pages ranked by views and unique visitors.
     */
    public function getPopularPages(int $startEpochMs, int $endEpochMs, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        $stmt = $this->db->prepare('
            SELECT
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, \'$.path\')), \'null\'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.path\')), \'null\'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.name\')), \'null\'),
                    \'/\'
                ) AS path,
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, \'$.title\')), \'null\'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, \'$.title\')), \'null\'),
                    \'Page\'
                ) AS title,
                COUNT(*) AS views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors
            FROM events
            WHERE event_name = \'page_view\'
              AND timestamp_epoch >= ?
              AND timestamp_epoch <= ?
            GROUP BY path, title
            ORDER BY views DESC
            LIMIT ?
        ');

        $stmt->bind_param('iii', $startEpochMs, $endEpochMs, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(function ($row) {
            return [
                'path' => (string)($row['path'] ?? '/'),
                'title' => (string)($row['title'] ?? 'Page'),
                'views' => (int)($row['views'] ?? 0),
                'unique_visitors' => (int)($row['unique_visitors'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Get distribution and counts by event name and source.
     */
    public function getEventBreakdown(int $startEpochMs, int $endEpochMs): array
    {
        $stmt = $this->db->prepare('
            SELECT
                event_name,
                source,
                COUNT(*) AS count,
                COUNT(DISTINCT anonymous_id) AS unique_users
            FROM events
            WHERE timestamp_epoch >= ? AND timestamp_epoch <= ?
            GROUP BY event_name, source
            ORDER BY count DESC
        ');
        $stmt->bind_param('ii', $startEpochMs, $endEpochMs);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_map(function ($row) {
            return [
                'event_name' => (string)$row['event_name'],
                'source' => (string)$row['source'],
                'count' => (int)$row['count'],
                'unique_users' => (int)$row['unique_users'],
            ];
        }, $rows);
    }

    /**
     * Conversion funnel: page_view -> view_item -> add_to_cart -> checkout_started -> order_created
     */
    public function getConversionFunnel(int $startEpochMs, int $endEpochMs): array
    {
        $steps = [
            ['name' => 'Visit Menu / Page', 'event' => 'page_view'],
            ['name' => 'View Dish Details', 'event' => 'view_item'],
            ['name' => 'Add Dish to Cart', 'event' => 'add_to_cart'],
            ['name' => 'Initiate Checkout', 'event' => 'checkout_started'],
            ['name' => 'Order Completed', 'event' => 'order_created'],
        ];

        $results = [];
        $firstCount = 0;
        $prevCount = 0;

        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT anonymous_id) AS visitors, COUNT(*) AS total_hits
            FROM events
            WHERE event_name = ?
              AND timestamp_epoch >= ?
              AND timestamp_epoch <= ?
        ');

        foreach ($steps as $idx => $step) {
            $stmt->bind_param('sii', $step['event'], $startEpochMs, $endEpochMs);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: [];
            $visitors = (int)($row['visitors'] ?? 0);
            $totalHits = (int)($row['total_hits'] ?? 0);

            if ($idx === 0) {
                $firstCount = $visitors;
                $prevCount = $visitors;
                $stepConversion = 100.0;
                $overallConversion = 100.0;
            } else {
                $stepConversion = $prevCount > 0 ? round(($visitors / $prevCount) * 100, 1) : 0.0;
                $overallConversion = $firstCount > 0 ? round(($visitors / $firstCount) * 100, 1) : 0.0;
                $prevCount = $visitors;
            }

            $results[] = [
                'step' => $idx + 1,
                'name' => $step['name'],
                'event_name' => $step['event'],
                'visitors' => $visitors,
                'total_hits' => $totalHits,
                'step_conversion_pct' => $stepConversion,
                'overall_conversion_pct' => $overallConversion,
                'dropoff_pct' => round(100.0 - $stepConversion, 1),
            ];
        }

        $stmt->close();
        return $results;
    }
}
