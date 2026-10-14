import React, { useState, useEffect, useCallback } from 'react';
import {
  fetchDashboard,
  OverviewData,
  TimeseriesPoint,
  PopularPage,
  EventBreakdownItem,
  FunnelStep,
  RecentEvent,
} from './api/analyticsApi';
import { DateRangePicker, DateRange } from './components/DateRangePicker';
import { MetricCards } from './components/MetricCards';
import { TrafficChart } from './components/TrafficChart';
import { FunnelChart } from './components/FunnelChart';
import { PopularPagesTable } from './components/PopularPagesTable';
import { EventBreakdownChart } from './components/EventBreakdownChart';
import { LiveEventFeed } from './components/LiveEventFeed';

interface DashboardAppProps {
  onSwitchToRestaurant: () => void;
}

export const DashboardApp: React.FC<DashboardAppProps> = ({ onSwitchToRestaurant }) => {
  const [range, setRange] = useState<DateRange>(() => {
    const now = Date.now();
    return {
      preset: '7d',
      startMs: now - 7 * 24 * 60 * 60 * 1000,
      endMs: now,
    };
  });

  const [interval, setInterval] = useState<'day' | 'hour'>('day');
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [filterSource, setFilterSource] = useState<'all' | 'browser' | 'server'>('all');

  // Analytics states
  const [overview, setOverview] = useState<OverviewData | null>(null);
  const [timeseries, setTimeseries] = useState<TimeseriesPoint[]>([]);
  const [pages, setPages] = useState<PopularPage[]>([]);
  const [breakdown, setBreakdown] = useState<EventBreakdownItem[]>([]);
  const [funnel, setFunnel] = useState<FunnelStep[]>([]);
  const [recentEvents, setRecentEvents] = useState<RecentEvent[]>([]);
  const [lastUpdated, setLastUpdated] = useState<string>('');

  const loadData = useCallback(async () => {
    setIsLoading(true);
    setErrorMessage(null);
    try {
      const data = await fetchDashboard(range.startMs, range.endMs, interval);

      setOverview(data.overview);
      setTimeseries(data.timeseries || []);
      setPages(data.popular_pages || []);
      setBreakdown(data.event_breakdown || []);
      setFunnel(data.funnel || []);
      setRecentEvents(data.recent_events || []);
      setLastUpdated(new Date().toLocaleTimeString());
    } catch (err) {
      console.error('Failed to load dashboard data:', err);
      setErrorMessage(err instanceof Error ? err.message : 'Failed to connect to analytics API.');
    } finally {
      setIsLoading(false);
    }
  }, [range.startMs, range.endMs, interval]);

  // Initial load and on range change
  useEffect(() => {
    loadData();
  }, [loadData]);

  // Auto-refresh interval (12 seconds)
  useEffect(() => {
    if (!autoRefresh) return;
    const timer = window.setInterval(() => {
      loadData();
    }, 12000);
    return () => window.clearInterval(timer);
  }, [autoRefresh, loadData]);

  return (
    <div className="min-h-screen bg-slate-950 flex flex-col">
      {/* Dashboard Top Navigation */}
      <header className="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30">
        <div className="container-custom py-4 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-500/20 text-white">
              📊
            </div>
            <div>
              <h1 className="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                Pulse Analytics
                <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                  Dashboard
                </span>
              </h1>
              <p className="text-xs text-slate-400">
                End-to-End Telemetry • Laravel 10 API • MySQL • Last synced: {lastUpdated || 'syncing...'}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={onSwitchToRestaurant}
              className="btn-primary text-xs"
              title="Return to Restaurant Demo"
            >
              🍽️ Open Restaurant Demo
            </button>
          </div>
        </div>
      </header>

      {/* Main Dashboard Grid */}
      <main className="container-custom py-8 flex-1 space-y-6">
        {errorMessage && (
          <div className="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span>⚠️</span>
              <span>{errorMessage}</span>
            </div>
            <button
              onClick={loadData}
              className="px-3 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 font-semibold transition-colors"
            >
              Retry
            </button>
          </div>
        )}

        {/* Date Filter & Controls */}
        <DateRangePicker
          range={range}
          onChange={setRange}
          autoRefresh={autoRefresh}
          onToggleAutoRefresh={setAutoRefresh}
          onRefresh={loadData}
          isLoading={isLoading}
        />

        {/* KPI Metric Cards */}
        <MetricCards overview={overview} />

        {/* Traffic Chart */}
        <TrafficChart
          data={timeseries}
          interval={interval}
          onIntervalChange={setInterval}
        />

        {/* Funnel Chart */}
        <FunnelChart steps={funnel} />

        {/* Two Column Grid: Popular Pages & Event Breakdown */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <PopularPagesTable pages={pages} />
          <EventBreakdownChart items={breakdown} />
        </div>

        {/* Live Real-Time Event Stream */}
        <LiveEventFeed
          events={recentEvents}
          filterSource={filterSource}
          onFilterChange={setFilterSource}
        />
      </main>
    </div>
  );
};
