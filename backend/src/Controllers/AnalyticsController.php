<?php

declare(strict_types=1);

namespace Analytics\Controllers;

use Analytics\Http\Request;
use Analytics\Http\Response;
use Analytics\Repositories\AnalyticsRepository;
use Analytics\Repositories\EventRepository;

class AnalyticsController
{
    private AnalyticsRepository $analyticsRepo;
    private EventRepository $eventRepo;

    public function __construct(?AnalyticsRepository $analyticsRepo = null, ?EventRepository $eventRepo = null)
    {
        $this->analyticsRepo = $analyticsRepo ?? new AnalyticsRepository();
        $this->eventRepo = $eventRepo ?? new EventRepository();
    }

    public function overview(Request $request): Response
    {
        [$start, $end] = $this->parseTimeRange($request);
        $data = $this->analyticsRepo->getOverview($start, $end);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    public function timeseries(Request $request): Response
    {
        [$start, $end] = $this->parseTimeRange($request);
        $interval = $request->getQuery('interval', 'day');
        if (!in_array($interval, ['day', 'hour'], true)) {
            $interval = 'day';
        }

        $data = $this->analyticsRepo->getTimeseries($start, $end, $interval);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    public function popularPages(Request $request): Response
    {
        [$start, $end] = $this->parseTimeRange($request);
        $limit = (int)$request->getQuery('limit', 10);
        $data = $this->analyticsRepo->getPopularPages($start, $end, $limit);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    public function eventBreakdown(Request $request): Response
    {
        [$start, $end] = $this->parseTimeRange($request);
        $data = $this->analyticsRepo->getEventBreakdown($start, $end);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    public function funnel(Request $request): Response
    {
        [$start, $end] = $this->parseTimeRange($request);
        $data = $this->analyticsRepo->getConversionFunnel($start, $end);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    public function recentEvents(Request $request): Response
    {
        $limit = (int)$request->getQuery('limit', 50);
        $source = $request->getQuery('source');
        $eventName = $request->getQuery('event_name');

        $data = $this->eventRepo->getRecentEvents($limit, $source, $eventName);
        return Response::json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Parse start and end query parameters to epoch milliseconds.
     * Default: last 7 days.
     */
    private function parseTimeRange(Request $request): array
    {
        $nowMs = (int)(microtime(true) * 1000);
        $sevenDaysAgoMs = $nowMs - (7 * 24 * 60 * 60 * 1000);

        $start = $request->getQuery('start');
        $end = $request->getQuery('end');

        $startEpoch = $start !== null ? $this->toEpochMs($start, $sevenDaysAgoMs) : $sevenDaysAgoMs;
        $endEpoch = $end !== null ? $this->toEpochMs($end, $nowMs) : $nowMs;

        if ($startEpoch > $endEpoch) {
            $temp = $startEpoch;
            $startEpoch = $endEpoch;
            $endEpoch = $temp;
        }

        return [$startEpoch, $endEpoch];
    }

    private function toEpochMs(string|int $value, int $fallback): int
    {
        if (is_numeric($value)) {
            $num = (int)$value;
            return $num > 100000000000 ? $num : $num * 1000;
        }

        $time = strtotime((string)$value);
        return $time !== false ? $time * 1000 : $fallback;
    }
}
