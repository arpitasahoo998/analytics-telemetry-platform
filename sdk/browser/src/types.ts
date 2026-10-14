/**
 * Analytics Browser SDK Type Definitions
 */

export interface AnalyticsConfig {
  endpoint: string;
  appId?: string;
  batchSize?: number;
  batchIntervalMs?: number;
  maxQueueSize?: number;
  sessionTimeoutMs?: number;
  debug?: boolean;
  autoPageview?: boolean;
}

export interface UserIdentity {
  anonymousId: string;
  sessionId: string;
  userId?: string | null;
  traits?: Record<string, unknown>;
}

export interface EventContext {
  url?: string;
  path?: string;
  referrer?: string;
  title?: string;
  userAgent?: string;
  screenResolution?: string;
  viewport?: string;
  locale?: string;
  timezone?: string;
  sdkName: string;
  sdkVersion: string;
  appId?: string;
  [key: string]: unknown;
}

export interface AnalyticsEvent {
  event_id: string;
  event_name: string;
  timestamp: string; // ISO-8601 UTC
  source: 'browser' | 'server';
  anonymous_id: string;
  session_id?: string | null;
  user_id?: string | null;
  properties: Record<string, unknown>;
  context: EventContext;
}

export interface IngestionResponse {
  status: 'success' | 'error';
  data?: {
    received: number;
    accepted: number;
    inserted: number;
    errors: Array<{ index: number; error: string }>;
  };
  message?: string;
}
