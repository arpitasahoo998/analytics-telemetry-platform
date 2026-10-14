import { describe, expect, it } from 'bun:test';
import { EventQueue } from '../src/queue';
import { Transport } from '../src/transport';
import { AnalyticsEvent } from '../src/types';

class MockTransport extends Transport {
  public sentBatches: AnalyticsEvent[][] = [];

  constructor() {
    super(false);
  }

  public override async sendFetch(events: AnalyticsEvent[]): Promise<boolean> {
    this.sentBatches.push(events);
    return true;
  }
}

function makeSampleEvent(name: string): AnalyticsEvent {
  return {
    event_id: 'evt_' + Math.random(),
    event_name: name,
    timestamp: new Date().toISOString(),
    source: 'browser',
    anonymous_id: 'anon_1',
    properties: {},
    context: { sdkName: 'test', sdkVersion: '1.0' },
  };
}

describe('EventQueue', () => {
  it('buffers events and flushes when batch size is reached', async () => {
    const transport = new MockTransport();
    const queue = new EventQueue({
      endpoint: 'http://localhost/events',
      batchSize: 3,
      batchIntervalMs: 10000,
      transport,
    });

    queue.enqueue(makeSampleEvent('evt_1'));
    queue.enqueue(makeSampleEvent('evt_2'));
    expect(queue.getQueueLength()).toBe(2);
    expect(transport.sentBatches.length).toBe(0);

    // 3rd event reaches batch size 3 -> triggers flush
    queue.enqueue(makeSampleEvent('evt_3'));
    await new Promise((r) => setTimeout(r, 20));

    expect(transport.sentBatches.length).toBe(1);
    expect(transport.sentBatches[0].length).toBe(3);
    expect(queue.getQueueLength()).toBe(0);

    queue.destroy();
  });

  it('evicts oldest events when maxQueueSize is exceeded', () => {
    const transport = new MockTransport();
    const queue = new EventQueue({
      endpoint: 'http://localhost/events',
      batchSize: 100,
      maxQueueSize: 3,
      transport,
    });

    queue.enqueue(makeSampleEvent('first'));
    queue.enqueue(makeSampleEvent('second'));
    queue.enqueue(makeSampleEvent('third'));
    expect(queue.getQueueLength()).toBe(3);

    // 4th event should drop 'first'
    queue.enqueue(makeSampleEvent('fourth'));
    expect(queue.getQueueLength()).toBe(3);

    queue.destroy();
  });
});
