import { describe, expect, it } from 'bun:test';
import { IdentityManager } from '../src/identity';

describe('IdentityManager', () => {
  it('generates persistent anonymous ID and session ID', () => {
    const identity = new IdentityManager();
    const anonId = identity.getAnonymousId();
    const sessId = identity.getSessionId();

    expect(anonId).toStartWith('anon_');
    expect(sessId).toStartWith('sess_');
    expect(identity.getUserId()).toBeNull();
  });

  it('identifies authenticated user with traits', () => {
    const identity = new IdentityManager();
    identity.identify('user_42', { plan: 'premium', role: 'admin' });

    expect(identity.getUserId()).toBe('user_42');
    expect(identity.getTraits()).toEqual({ plan: 'premium', role: 'admin' });
  });

  it('resets identity on logout and generates fresh IDs', () => {
    const identity = new IdentityManager();
    const oldAnon = identity.getAnonymousId();
    identity.identify('user_42');

    identity.reset();

    expect(identity.getUserId()).toBeNull();
    expect(identity.getTraits()).toEqual({});
    expect(identity.getAnonymousId()).not.toBe(oldAnon);
  });

  it('creates new session when inactivity timeout exceeds threshold', async () => {
    // 50ms session timeout for test
    const identity = new IdentityManager(50);
    const session1 = identity.getSessionId();

    // Wait 70ms to exceed inactivity timeout
    await new Promise((resolve) => setTimeout(resolve, 70));

    const session2 = identity.getSessionId();
    expect(session2).not.toBe(session1);
    expect(session2).toStartWith('sess_');
  });
});
