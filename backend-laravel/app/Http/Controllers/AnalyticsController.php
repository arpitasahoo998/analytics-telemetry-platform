<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);

        $eventStats = DB::table('events')
            ->selectRaw("
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(DISTINCT CASE WHEN session_id IS NOT NULL AND session_id != '' THEN session_id END) AS total_sessions,
                COUNT(CASE WHEN source = 'server' THEN 1 END) AS server_events,
                COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders_created
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->first();

        $orderStats = DB::table('orders')
            ->selectRaw("
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COALESCE(AVG(total_amount), 0) AS avg_order_value
            ")
            ->whereRaw("UNIX_TIMESTAMP(created_at) * 1000 BETWEEN ? AND ?", [$startMs, $endMs])
            ->first();

        $uniqueVisitors = (int)($eventStats->unique_visitors ?? 0);
        $ordersCreated = (int)($eventStats->orders_created ?? 0);
        $conversionRate = $uniqueVisitors > 0
            ? round(($ordersCreated / $uniqueVisitors) * 100, 2)
            : 0.0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_events' => (int)($eventStats->total_events ?? 0),
                'page_views' => (int)($eventStats->page_views ?? 0),
                'unique_visitors' => $uniqueVisitors,
                'total_sessions' => (int)($eventStats->total_sessions ?? 0),
                'server_events' => (int)($eventStats->server_events ?? 0),
                'orders_created' => $ordersCreated,
                'total_revenue' => round((float)($orderStats->total_revenue ?? 0), 2),
                'avg_order_value' => round((float)($orderStats->avg_order_value ?? 0), 2),
                'conversion_rate_percent' => $conversionRate,
                'time_window' => [
                    'start_epoch_ms' => $startMs,
                    'end_epoch_ms' => $endMs,
                ],
            ]
        ]);
    }

    public function timeseries(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);
        $interval = $request->query('interval', 'day');
        $format = $interval === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';

        $rows = DB::table('events')
            ->selectRaw("
                DATE_FORMAT(FROM_UNIXTIME(timestamp_epoch / 1000), '{$format}') AS period,
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'period' => (string)$row->period,
                'total_events' => (int)$row->total_events,
                'page_views' => (int)$row->page_views,
                'unique_visitors' => (int)$row->unique_visitors,
                'orders' => (int)$row->orders,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function popularPages(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);
        $limit = min((int)$request->query('limit', 10), 50);

        $rows = DB::table('events')
            ->selectRaw("
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.path')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.path')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.name')), 'null'),
                    '/'
                ) AS path,
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.title')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.title')), 'null'),
                    'Page'
                ) AS title,
                COUNT(*) AS views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors
            ")
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('path', 'title')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'path' => (string)$row->path,
                'title' => (string)$row->title,
                'views' => (int)$row->views,
                'unique_visitors' => (int)$row->unique_visitors,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function eventBreakdown(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);

        $rows = DB::table('events')
            ->selectRaw("
                event_name,
                source,
                COUNT(*) AS count,
                COUNT(DISTINCT anonymous_id) AS unique_users
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('event_name', 'source')
            ->orderByDesc('count')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'event_name' => (string)$row->event_name,
                'source' => (string)$row->source,
                'count' => (int)$row->count,
                'unique_users' => (int)$row->unique_users,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function funnel(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);

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

        foreach ($steps as $idx => $step) {
            $row = DB::table('events')
                ->selectRaw("COUNT(DISTINCT anonymous_id) AS visitors, COUNT(*) AS total_hits")
                ->where('event_name', $step['event'])
                ->whereBetween('timestamp_epoch', [$startMs, $endMs])
                ->first();

            $visitors = (int)($row->visitors ?? 0);
            $totalHits = (int)($row->total_hits ?? 0);

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

        return response()->json(['status' => 'success', 'data' => $results]);
    }

    public function recentEvents(Request $request): JsonResponse
    {
        $limit = min((int)$request->query('limit', 50), 100);
        $source = $request->query('source');
        $eventName = $request->query('event_name');

        $query = DB::table('events');

        if ($source && in_array($source, ['browser', 'server'], true)) {
            $query->where('source', $source);
        }
        if ($eventName) {
            $query->where('event_name', $eventName);
        }

        $rows = $query->orderByDesc('timestamp_epoch')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'event_id' => $row->event_id,
                'event_name' => $row->event_name,
                'timestamp' => $row->timestamp,
                'timestamp_epoch' => $row->timestamp_epoch,
                'source' => $row->source,
                'anonymous_id' => $row->anonymous_id,
                'session_id' => $row->session_id,
                'user_id' => $row->user_id,
                'properties' => json_decode($row->properties ?? '{}', true) ?: [],
                'context' => json_decode($row->context ?? '{}', true) ?: [],
                'created_at' => $row->created_at,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        [$startMs, $endMs] = $this->parseTimeRange($request);
        $interval = $request->query('interval', 'day');

        // 1. Overview metrics
        $eventStats = DB::table('events')
            ->selectRaw("
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(DISTINCT CASE WHEN session_id IS NOT NULL AND session_id != '' THEN session_id END) AS total_sessions,
                COUNT(CASE WHEN source = 'server' THEN 1 END) AS server_events,
                COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders_created
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->first();

        $orderStats = DB::table('orders')
            ->selectRaw("
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COALESCE(AVG(total_amount), 0) AS avg_order_value
            ")
            ->whereRaw("UNIX_TIMESTAMP(created_at) * 1000 BETWEEN ? AND ?", [$startMs, $endMs])
            ->first();

        $uniqueVisitors = (int)($eventStats->unique_visitors ?? 0);
        $ordersCreated = (int)($eventStats->orders_created ?? 0);
        $conversionRate = $uniqueVisitors > 0
            ? round(($ordersCreated / $uniqueVisitors) * 100, 2)
            : 0.0;

        $overview = [
            'total_events' => (int)($eventStats->total_events ?? 0),
            'page_views' => (int)($eventStats->page_views ?? 0),
            'unique_visitors' => $uniqueVisitors,
            'total_sessions' => (int)($eventStats->total_sessions ?? 0),
            'server_events' => (int)($eventStats->server_events ?? 0),
            'orders_created' => $ordersCreated,
            'total_revenue' => round((float)($orderStats->total_revenue ?? 0), 2),
            'avg_order_value' => round((float)($orderStats->avg_order_value ?? 0), 2),
            'conversion_rate_percent' => $conversionRate,
            'time_window' => [
                'start_epoch_ms' => $startMs,
                'end_epoch_ms' => $endMs,
            ],
        ];

        // 2. Timeseries
        $format = $interval === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';
        $timeseriesRows = DB::table('events')
            ->selectRaw("
                DATE_FORMAT(FROM_UNIXTIME(timestamp_epoch / 1000), '{$format}') AS period,
                COUNT(*) AS total_events,
                COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors,
                COUNT(CASE WHEN event_name = 'order_created' THEN 1 END) AS orders
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $timeseries = $timeseriesRows->map(fn($row) => [
            'period' => (string)$row->period,
            'total_events' => (int)$row->total_events,
            'page_views' => (int)$row->page_views,
            'unique_visitors' => (int)$row->unique_visitors,
            'orders' => (int)$row->orders,
        ]);

        // 3. Popular Pages
        $pagesRows = DB::table('events')
            ->selectRaw("
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.path')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.path')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.name')), 'null'),
                    '/'
                ) AS path,
                COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(context, '$.title')), 'null'),
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.title')), 'null'),
                    'Page'
                ) AS title,
                COUNT(*) AS views,
                COUNT(DISTINCT anonymous_id) AS unique_visitors
            ")
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('path', 'title')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $popularPages = $pagesRows->map(fn($row) => [
            'path' => (string)$row->path,
            'title' => (string)$row->title,
            'views' => (int)$row->views,
            'unique_visitors' => (int)$row->unique_visitors,
        ]);

        // 4. Event Breakdown
        $breakdownRows = DB::table('events')
            ->selectRaw("
                event_name,
                source,
                COUNT(*) AS count,
                COUNT(DISTINCT anonymous_id) AS unique_users
            ")
            ->whereBetween('timestamp_epoch', [$startMs, $endMs])
            ->groupBy('event_name', 'source')
            ->orderByDesc('count')
            ->get();

        $eventBreakdown = $breakdownRows->map(fn($row) => [
            'event_name' => (string)$row->event_name,
            'source' => (string)$row->source,
            'count' => (int)$row->count,
            'unique_users' => (int)$row->unique_users,
        ]);

        // 5. Funnel
        $steps = [
            ['name' => 'Visit Menu / Page', 'event' => 'page_view'],
            ['name' => 'View Dish Details', 'event' => 'view_item'],
            ['name' => 'Add Dish to Cart', 'event' => 'add_to_cart'],
            ['name' => 'Initiate Checkout', 'event' => 'checkout_started'],
            ['name' => 'Order Completed', 'event' => 'order_created'],
        ];

        $funnel = [];
        $firstCount = 0;
        $prevCount = 0;

        foreach ($steps as $idx => $step) {
            $row = DB::table('events')
                ->selectRaw("COUNT(DISTINCT anonymous_id) AS visitors, COUNT(*) AS total_hits")
                ->where('event_name', $step['event'])
                ->whereBetween('timestamp_epoch', [$startMs, $endMs])
                ->first();

            $visitors = (int)($row->visitors ?? 0);
            $totalHits = (int)($row->total_hits ?? 0);

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

            $funnel[] = [
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

        // 6. Recent Events
        $recentRows = DB::table('events')
            ->orderByDesc('timestamp_epoch')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $recentEvents = $recentRows->map(fn($row) => [
            'id' => $row->id,
            'event_id' => $row->event_id,
            'event_name' => $row->event_name,
            'timestamp' => $row->timestamp,
            'timestamp_epoch' => $row->timestamp_epoch,
            'source' => $row->source,
            'anonymous_id' => $row->anonymous_id,
            'session_id' => $row->session_id,
            'user_id' => $row->user_id,
            'properties' => json_decode($row->properties ?? '{}', true) ?: [],
            'context' => json_decode($row->context ?? '{}', true) ?: [],
            'created_at' => $row->created_at,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => $overview,
                'timeseries' => $timeseries,
                'popular_pages' => $popularPages,
                'event_breakdown' => $eventBreakdown,
                'funnel' => $funnel,
                'recent_events' => $recentEvents,
            ]
        ])->header('Connection', 'close');
    }

    private function parseTimeRange(Request $request): array
    {
        $nowMs = (int)(microtime(true) * 1000);
        $sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

        $startMs = $request->query('start') && is_numeric($request->query('start'))
            ? (int)$request->query('start')
            : $sevenDaysAgoMs;

        $endMs = $request->query('end') && is_numeric($request->query('end'))
            ? (int)$request->query('end')
            : $nowMs;

        if ($startMs > $endMs) {
            return [$endMs, $startMs];
        }

        return [$startMs, $endMs];
    }
}
