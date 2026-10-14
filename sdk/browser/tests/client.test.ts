import { describe, expect, it } from 'bun:test';
import { AnalyticsBrowserClient } from '../src/client';

describe('AnalyticsBrowserClient', () => {
  it('initializes and provides identity snapshot', () => {
    const client = new AnalyticsBrowserClient({
      endpoint: 'http://localhost:8000/api/v1/events',
      batchSize: 10,
    });

    const identity = client.getIdentity();
    expect(identity.anonymousId).toStartWith('anon_');
    expect(identity.sessionId).toStartWith('sess_');
    expect(identity.userId).toBeNull();
  });

  it('identifies user and updates snapshot', () => {
    const client = new AnalyticsBrowserClient({
      endpoint: 'http://localhost:8000/api/v1/events',
    });

    client.identify('usr_99', { email: 'test@example.com' });
    const identity = client.getIdentity();

    expect(identity.userId).toBe('usr_99');
    expect(identity.traits).toEqual({ email: 'test@example.com' });
  });

  it('resets identity cleanly', () => {
    const client = new AnalyticsBrowserClient({
      endpoint: 'http://localhost:8000/api/v1/events',
    });

    client.identify('usr_99');
    client.reset();

    const identity = client.getIdentity();
    expect(identity.userId).toBeNull();
    expect(identity.traits).toEqual({});
  });
});
