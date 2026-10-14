/**
 * Transport layer: Handles resilient batch delivery via Fetch API with exponential backoff,
 * with navigator.sendBeacon fallback for page unload/visibility change.
 */

import { AnalyticsEvent, IngestionResponse } from './types';

export class Transport {
  private debug: boolean;

  constructor(debug: boolean = false) {
    this.debug = debug;
  }

  /**
   * Send batch of events using Fetch API with exponential backoff retry.
   */
  public async sendFetch(
    events: AnalyticsEvent[],
    endpoint: string,
    maxRetries: number = 3
  ): Promise<boolean> {
    if (events.length === 0) {
      return true;
    }

    const payload = JSON.stringify({ events });
    let attempt = 0;
    let delayMs = 500;

    while (attempt < maxRetries) {
      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: payload,
          keepalive: true, // Keep connection open during navigation
        });

        if (response.ok) {
          const result: IngestionResponse = await response.json();
          if (this.debug) {
            console.log(`[Analytics SDK] Ingested ${events.length} events successfully`, result);
          }
          return true;
        }

        // Server responded with 4xx client error (do not retry non-transient error)
        if (response.status >= 400 && response.status < 500 && response.status !== 429) {
          if (this.debug) {
            console.warn(`[Analytics SDK] Server rejected events with status ${response.status}`);
          }
          return false;
        }

        // Transient 5xx or 429 rate limit error - backoff and retry
        attempt++;
        if (this.debug) {
          console.warn(`[Analytics SDK] HTTP ${response.status}, retrying attempt ${attempt}/${maxRetries} in ${delayMs}ms...`);
        }
        await this.sleep(delayMs);
        delayMs *= 2;
      } catch (err) {
        attempt++;
        if (this.debug) {
          console.warn(`[Analytics SDK] Network error on attempt ${attempt}/${maxRetries}:`, err);
        }
        if (attempt >= maxRetries) {
          return false;
        }
        await this.sleep(delayMs);
        delayMs *= 2;
      }
    }

    return false;
  }

  /**
   * Send batch using navigator.sendBeacon (ideal for unload and visibility change).
   */
  public sendBeacon(events: AnalyticsEvent[], endpoint: string): boolean {
    if (events.length === 0) {
      return true;
    }

    const payload = JSON.stringify({ events });

    if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
      try {
        const blob = new Blob([payload], { type: 'application/json' });
        const queued = navigator.sendBeacon(endpoint, blob);
        if (queued && this.debug) {
          console.log(`[Analytics SDK] Dispatched ${events.length} events via navigator.sendBeacon`);
        }
        return queued;
      } catch (err) {
        if (this.debug) {
          console.warn('[Analytics SDK] navigator.sendBeacon failed:', err);
        }
      }
    }

    // Fallback: fire-and-forget fetch with keepalive
    if (typeof fetch === 'function') {
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payload,
        keepalive: true,
      }).catch(() => {});
      return true;
    }

    return false;
  }

  private sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
