/**
 * Analytics Browser SDK Entry Point
 */

import { AnalyticsBrowserClient } from './client';
import { AnalyticsConfig } from './types';

export * from './types';
export { IdentityManager } from './identity';
export { EventQueue } from './queue';
export { Transport } from './transport';
export { AnalyticsBrowserClient } from './client';

let defaultInstance: AnalyticsBrowserClient | null = null;

/**
 * Initialize default global singleton analytics instance.
 */
export function init(config: AnalyticsConfig): AnalyticsBrowserClient {
  defaultInstance = new AnalyticsBrowserClient(config);
  if (typeof window !== 'undefined') {
    (window as unknown as { analytics: AnalyticsBrowserClient }).analytics = defaultInstance;
  }
  return defaultInstance;
}

/**
 * Track custom event using default instance.
 */
export function track(eventName: string, properties?: Record<string, unknown>): void {
  if (!defaultInstance) {
    console.warn('[Analytics SDK] Call init() before calling track()');
    return;
  }
  defaultInstance.track(eventName, properties);
}

/**
 * Track page view using default instance.
 */
export function page(name?: string, properties?: Record<string, unknown>): void {
  if (!defaultInstance) {
    console.warn('[Analytics SDK] Call init() before calling page()');
    return;
  }
  defaultInstance.page(name, properties);
}

/**
 * Identify user using default instance.
 */
export function identify(userId: string, traits?: Record<string, unknown>): void {
  if (!defaultInstance) {
    console.warn('[Analytics SDK] Call init() before calling identify()');
    return;
  }
  defaultInstance.identify(userId, traits);
}

/**
 * Reset identity using default instance.
 */
export function reset(): void {
  defaultInstance?.reset();
}

/**
 * Flush events using default instance.
 */
export function flush(): Promise<boolean> {
  if (!defaultInstance) {
    return Promise.resolve(true);
  }
  return defaultInstance.flush();
}

/**
 * Factory function for creating custom instances.
 */
export function createAnalytics(config: AnalyticsConfig): AnalyticsBrowserClient {
  return new AnalyticsBrowserClient(config);
}
