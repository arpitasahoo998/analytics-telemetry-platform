<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed sample event for queries
        Event::create([
            'event_id' => (string) Str::uuid(),
            'event_name' => 'page_view',
            'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
            'timestamp_epoch' => (int)(microtime(true) * 1000),
            'source' => 'browser',
            'anonymous_id' => 'anon_test_' . uniqid(),
            'session_id' => 'sess_test_' . uniqid(),
            'properties' => ['path' => '/menu/indian', 'title' => 'Indian Specialties'],
            'context' => ['path' => '/menu/indian', 'title' => 'Indian Specialties'],
        ]);
    }

    public function test_overview_endpoint_returns_metrics()
    {
        $response = $this->getJson('/api/v1/analytics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_events',
                    'page_views',
                    'unique_visitors',
                    'total_sessions',
                    'orders_created',
                    'total_revenue',
                    'avg_order_value',
                    'conversion_rate_percent',
                    'server_events',
                ]
            ]);
    }

    public function test_timeseries_endpoint_returns_data()
    {
        $response = $this->getJson('/api/v1/analytics/timeseries?interval=day');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'period',
                        'total_events',
                        'page_views',
                        'unique_visitors',
                        'orders'
                    ]
                ]
            ]);
    }

    public function test_popular_pages_endpoint_returns_page_rankings()
    {
        $response = $this->getJson('/api/v1/analytics/popular-pages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'path',
                        'title',
                        'views',
                        'unique_visitors'
                    ]
                ]
            ]);
    }

    public function test_funnel_endpoint_returns_funnel_stages()
    {
        $response = $this->getJson('/api/v1/analytics/funnel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'step',
                        'name',
                        'event_name',
                        'visitors',
                        'total_hits',
                        'step_conversion_pct',
                        'overall_conversion_pct',
                        'dropoff_pct',
                    ]
                ]
            ]);
    }

    public function test_dashboard_endpoint_returns_consolidated_data()
    {
        $response = $this->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'overview',
                    'timeseries',
                    'popular_pages',
                    'event_breakdown',
                    'funnel',
                    'recent_events',
                ]
            ]);
    }
}
