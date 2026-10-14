<?php

declare(strict_types=1);

namespace Analytics;

use Analytics\Http\Request;
use Analytics\Http\Response;
use Analytics\Controllers\IngestionController;
use Analytics\Controllers\AnalyticsController;
use Analytics\Controllers\OrderController;

class Router
{
    private IngestionController $ingestionController;
    private AnalyticsController $analyticsController;
    private OrderController $orderController;

    public function __construct(
        ?IngestionController $ingestionController = null,
        ?AnalyticsController $analyticsController = null,
        ?OrderController $orderController = null
    ) {
        $this->ingestionController = $ingestionController ?? new IngestionController();
        $this->analyticsController = $analyticsController ?? new AnalyticsController();
        $this->orderController = $orderController ?? new OrderController();
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = rtrim($request->getUri(), '/');
        if ($uri === '') {
            $uri = '/';
        }

        // Handle CORS Preflight OPTIONS requests
        if ($method === 'OPTIONS') {
            return Response::corsPreflight();
        }

        // Health check
        if ($uri === '/api/health' && $method === 'GET') {
            return Response::json([
                'status' => 'ok',
                'service' => 'analytics-api',
                'version' => '1.0.0',
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }

        // Event Ingestion
        if ($uri === '/api/v1/events' && $method === 'POST') {
            return $this->ingestionController->handle($request);
        }

        // Analytics Endpoints
        if ($uri === '/api/v1/analytics/overview' && $method === 'GET') {
            return $this->analyticsController->overview($request);
        }

        if ($uri === '/api/v1/analytics/timeseries' && $method === 'GET') {
            return $this->analyticsController->timeseries($request);
        }

        if ($uri === '/api/v1/analytics/popular-pages' && $method === 'GET') {
            return $this->analyticsController->popularPages($request);
        }

        if ($uri === '/api/v1/analytics/event-breakdown' && $method === 'GET') {
            return $this->analyticsController->eventBreakdown($request);
        }

        if ($uri === '/api/v1/analytics/funnel' && $method === 'GET') {
            return $this->analyticsController->funnel($request);
        }

        if ($uri === '/api/v1/analytics/recent-events' && $method === 'GET') {
            return $this->analyticsController->recentEvents($request);
        }

        // Restaurant Orders
        if ($uri === '/api/orders' && $method === 'POST') {
            return $this->orderController->create($request);
        }

        if ($uri === '/api/orders' && $method === 'GET') {
            return $this->orderController->list($request);
        }

        return Response::error("Route not found: {$method} {$uri}", 404);
    }
}
