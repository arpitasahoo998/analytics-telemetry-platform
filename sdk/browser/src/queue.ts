/**
 * Event Queue: In-memory buffering, batching triggers, and automatic lifecycle flushing.
 */

import { AnalyticsEvent } from './types';
import { Transport } from './transport';

export class EventQueue {
  private queue: AnalyticsEvent[] = [];
  private endpoint: string;
  private batchSize: number;
  private batchIntervalMs: number;
  private maxQueueSize: number;
  private transport: Transport;
  private timerId: ReturnType<typeof setInterval> | null = null;
  private isFlushing: boolean = false;
  private debug: boolean;

  constructor(options: {
    endpoint: string;
    batchSize?: number;
    batchIntervalMs?: number;
    maxQueueSize?: number;
    debug?: boolean;
    transport?: Transport;
  }) {
    this.endpoint = options.endpoint;
    this.batchSize = options.batchSize ?? 10;
    this.batchIntervalMs = options.batchIntervalMs ?? 2000;
    this.maxQueueSize = options.maxQueueSize ?? 100;
    this.debug = options.debug ?? false;
    this.transport = options.transport ?? new Transport(this.debug);

    this.startTimer();
    this.attachLifecycleListeners();
  }

  public enqueue(event: AnalyticsEvent): void {
    if (this.queue.length >= this.maxQueueSize) {
      // FIFO eviction: drop oldest event to avoid memory leaks
      const dropped = this.queue.shift();
      if (this.debug) {
        console.warn('[Analytics SDK] Queue full, dropped oldest event:', dropped?.event_name);
      }
    }

    this.queue.push(event);

    if (this.queue.length >= this.batchSize) {
      this.flush();
    }
  }

  public async flush(): Promise<boolean> {
    if (this.isFlushing || this.queue.length === 0) {
      return true;
    }

    this.isFlushing = true;
    // Take a snapshot batch
    const batch = this.queue.splice(0, this.batchSize);

    try {
      const success = await this.transport.sendFetch(batch, this.endpoint);
      if (!success) {
        // Prepend back to queue for future retry if capacity allows
        if (this.queue.length + batch.length <= this.maxQueueSize) {
          this.queue.unshift(...batch);
        }
        return false;
      }
      return true;
    } catch {
      return false;
    } finally {
      this.isFlushing = false;

      // If more items remain in queue, flush next batch
      if (this.queue.length >= this.batchSize) {
        setTimeout(() => this.flush(), 50);
      }
    }
  }

  /**
   * Emergency flush on tab unload / hide using sendBeacon.
   */
  public flushSyncBeacon(): void {
    if (this.queue.length === 0) {
      return;
    }

    const eventsToSend = [...this.queue];
    this.queue = [];
    this.transport.sendBeacon(eventsToSend, this.endpoint);
  }

  public getQueueLength(): number {
    return this.queue.length;
  }

  public clear(): void {
    this.queue = [];
  }

  public destroy(): void {
    if (this.timerId !== null) {
      clearInterval(this.timerId);
      this.timerId = null;
    }
  }

  private startTimer(): void {
    if (typeof window === 'undefined' && typeof setInterval === 'undefined') {
      return;
    }
    this.timerId = setInterval(() => {
      if (this.queue.length > 0) {
        this.flush();
      }
    }, this.batchIntervalMs);
  }

  private attachLifecycleListeners(): void {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
      return;
    }

    // Modern browsers: visibilitychange is preferred
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        this.flushSyncBeacon();
      }
    });

    // Fallbacks for older browsers
    window.addEventListener('pagehide', () => {
      this.flushSyncBeacon();
    });

    window.addEventListener('beforeunload', () => {
      this.flushSyncBeacon();
    });
  }
}
