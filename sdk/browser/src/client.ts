/**
 * Analytics Browser Client: Main SDK entrypoint for frontend applications.
 */

import { AnalyticsConfig, AnalyticsEvent, EventContext, UserIdentity } from './types';
import { IdentityManager } from './identity';
import { EventQueue } from './queue';
import { Transport } from './transport';

const SDK_NAME = 'analytics-browser-sdk';
const SDK_VERSION = '1.0.0';

export class AnalyticsBrowserClient {
  private config: AnalyticsConfig;
  private identity: IdentityManager;
  private queue: EventQueue;
  private initialized: boolean = false;

  constructor(config: AnalyticsConfig) {
    this.config = {
      batchSize: 10,
      batchIntervalMs: 2000,
      maxQueueSize: 100,
      sessionTimeoutMs: 30 * 60 * 1000,
      debug: false,
      autoPageview: false,
      ...config,
    };

    this.identity = new IdentityManager(this.config.sessionTimeoutMs);
    this.queue = new EventQueue({
      endpoint: this.config.endpoint,
      batchSize: this.config.batchSize,
      batchIntervalMs: this.config.batchIntervalMs,
      maxQueueSize: this.config.maxQueueSize,
      debug: this.config.debug,
      transport: new Transport(this.config.debug),
    });

    this.initialized = true;

    if (this.config.autoPageview && typeof window !== 'undefined') {
      this.setupAutoPageviews();
    }
  }

  /**
   * Track a custom interaction event.
   */
  public track(
    eventName: string,
    properties: Record<string, unknown> = {},
    contextOverrides: Partial<EventContext> = {}
  ): void {
    this.identity.touchSession();

    const event: AnalyticsEvent = {
      event_id: this.identity.generateUuid(),
      event_name: eventName,
      timestamp: new Date().toISOString(),
      source: 'browser',
      anonymous_id: this.identity.getAnonymousId(),
      session_id: this.identity.getSessionId(),
      user_id: this.identity.getUserId(),
      properties,
      context: this.buildContext(contextOverrides),
    };

    if (this.config.debug) {
      console.log(`[Analytics SDK] Enqueued "${eventName}":`, event);
    }

    this.queue.enqueue(event);
  }

  /**
   * Track a page view event.
   */
  public page(name?: string, properties: Record<string, unknown> = {}): void {
    const pageProps = {
      name: name || (typeof document !== 'undefined' ? document.title : 'Page'),
      path: typeof window !== 'undefined' ? window.location.pathname : '/',
      url: typeof window !== 'undefined' ? window.location.href : '',
      ...properties,
    };

    this.track('page_view', pageProps);
  }

  /**
   * Identify an authenticated user and stitch session history.
   */
  public identify(userId: string, traits: Record<string, unknown> = {}): void {
    this.identity.identify(userId, traits);
    this.track('identify', { userId, traits });
  }

  /**
   * Reset visitor identity on logout.
   */
  public reset(): void {
    this.identity.reset();
  }

  /**
   * Flush pending events immediately.
   */
  public async flush(): Promise<boolean> {
    return this.queue.flush();
  }

  /**
   * Get current identity snapshot (anonymousId, sessionId, userId).
   */
  public getIdentity(): UserIdentity {
    return this.identity.getSnapshot();
  }

  private buildContext(overrides: Partial<EventContext> = {}): EventContext {
    const context: EventContext = {
      sdkName: SDK_NAME,
      sdkVersion: SDK_VERSION,
      appId: this.config.appId,
    };

    if (typeof window !== 'undefined') {
      context.url = window.location.href;
      context.path = window.location.pathname;
      context.referrer = document.referrer || '';
      context.title = document.title || '';
      context.userAgent = navigator.userAgent;
      context.locale = navigator.language;

      if (window.screen) {
        context.screenResolution = `${window.screen.width}x${window.screen.height}`;
      }
      context.viewport = `${window.innerWidth}x${window.innerHeight}`;

      try {
        context.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      } catch {
        // Fallback
      }
    }

    return { ...context, ...overrides };
  }

  private setupAutoPageviews(): void {
    // Initial pageview
    this.page();

    // Listen to history changes for SPAs
    window.addEventListener('popstate', () => this.page());
    window.addEventListener('hashchange', () => this.page());

    // Intercept pushState & replaceState
    const originalPushState = window.history.pushState;
    window.history.pushState = (...args: Parameters<typeof originalPushState>) => {
      originalPushState.apply(window.history, args);
      this.page();
    };
  }
}
