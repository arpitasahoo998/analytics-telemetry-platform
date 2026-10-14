export interface OverviewData {
  total_events: number;
  page_views: number;
  unique_visitors: number;
  total_sessions: number;
  server_events: number;
  orders_created: number;
  total_revenue: number;
  avg_order_value: number;
  conversion_rate_percent: number;
  time_window: {
    start_epoch_ms: number;
    end_epoch_ms: number;
  };
}

export interface TimeseriesPoint {
  period: string;
  total_events: number;
  page_views: number;
  unique_visitors: number;
  orders: number;
}

export interface PopularPage {
  path: string;
  title: string;
  views: number;
  unique_visitors: number;
}

export interface EventBreakdownItem {
  event_name: string;
  source: 'browser' | 'server';
  count: number;
  unique_users: number;
}

export interface FunnelStep {
  step: number;
  name: string;
  event_name: string;
  visitors: number;
  total_hits: number;
  step_conversion_pct: number;
  overall_conversion_pct: number;
  dropoff_pct: number;
}

export interface RecentEvent {
  id: number;
  event_id: string;
  event_name: string;
  timestamp: string;
  timestamp_epoch: number;
  source: 'browser' | 'server';
  anonymous_id: string;
  session_id?: string | null;
  user_id?: string | null;
  properties: Record<string, unknown>;
  context: Record<string, unknown>;
  created_at: string;
}

export interface DashboardResponse {
  overview: OverviewData;
  timeseries: TimeseriesPoint[];
  popular_pages: PopularPage[];
  event_breakdown: EventBreakdownItem[];
  funnel: FunnelStep[];
  recent_events: RecentEvent[];
}

const API_BASE = '/api/v1/analytics';

export async function fetchDashboard(
  startMs?: number,
  endMs?: number,
  interval: 'day' | 'hour' = 'day'
): Promise<DashboardResponse> {
  const params = new URLSearchParams({ interval });
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 12000);

  try {
    const res = await fetch(`${API_BASE}/dashboard?${params.toString()}`, {
      signal: controller.signal,
    });
    clearTimeout(timeoutId);
    if (!res.ok) throw new Error(`Dashboard fetch failed: ${res.statusText}`);
    const json = await res.json();
    return json.data;
  } catch (e) {
    clearTimeout(timeoutId);
    throw e;
  }
}

export async function fetchOverview(startMs?: number, endMs?: number): Promise<OverviewData> {
  const params = new URLSearchParams();
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const res = await fetch(`${API_BASE}/overview?${params.toString()}`);
  if (!res.ok) throw new Error(`Overview fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}

export async function fetchTimeseries(
  startMs?: number,
  endMs?: number,
  interval: 'day' | 'hour' = 'day'
): Promise<TimeseriesPoint[]> {
  const params = new URLSearchParams({ interval });
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const res = await fetch(`${API_BASE}/timeseries?${params.toString()}`);
  if (!res.ok) throw new Error(`Timeseries fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}

export async function fetchPopularPages(
  startMs?: number,
  endMs?: number,
  limit: number = 10
): Promise<PopularPage[]> {
  const params = new URLSearchParams({ limit: limit.toString() });
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const res = await fetch(`${API_BASE}/popular-pages?${params.toString()}`);
  if (!res.ok) throw new Error(`Popular pages fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}

export async function fetchEventBreakdown(
  startMs?: number,
  endMs?: number
): Promise<EventBreakdownItem[]> {
  const params = new URLSearchParams();
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const res = await fetch(`${API_BASE}/event-breakdown?${params.toString()}`);
  if (!res.ok) throw new Error(`Event breakdown fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}

export async function fetchFunnel(startMs?: number, endMs?: number): Promise<FunnelStep[]> {
  const params = new URLSearchParams();
  if (startMs) params.append('start', startMs.toString());
  if (endMs) params.append('end', endMs.toString());

  const res = await fetch(`${API_BASE}/funnel?${params.toString()}`);
  if (!res.ok) throw new Error(`Funnel fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}

export async function fetchRecentEvents(
  limit: number = 50,
  source?: string,
  eventName?: string
): Promise<RecentEvent[]> {
  const params = new URLSearchParams({ limit: limit.toString() });
  if (source) params.append('source', source);
  if (eventName) params.append('event_name', eventName);

  const res = await fetch(`${API_BASE}/recent-events?${params.toString()}`);
  if (!res.ok) throw new Error(`Recent events fetch failed: ${res.statusText}`);
  const json = await res.json();
  return json.data;
}
