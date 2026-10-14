/**
 * Identity Manager: Handles anonymous visitor IDs, sliding sessions, and user identification.
 */

import { UserIdentity } from './types';

const STORAGE_KEY_ANON = '_analytics_anon_id';
const STORAGE_KEY_SESSION = '_analytics_sess_id';
const STORAGE_KEY_SESSION_TS = '_analytics_sess_ts';
const STORAGE_KEY_USER = '_analytics_user_id';
const STORAGE_KEY_TRAITS = '_analytics_user_traits';

const DEFAULT_SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes

export class IdentityManager {
  private sessionTimeoutMs: number;
  private anonymousId: string;
  private sessionId: string;
  private userId: string | null = null;
  private traits: Record<string, unknown> = {};

  constructor(sessionTimeoutMs: number = DEFAULT_SESSION_TIMEOUT_MS) {
    this.sessionTimeoutMs = sessionTimeoutMs;
    this.anonymousId = this.loadOrGenerateAnonymousId();
    this.sessionId = this.loadOrGenerateSessionId();
    this.loadUserIdentity();
  }

  public getAnonymousId(): string {
    return this.anonymousId;
  }

  public getSessionId(): string {
    this.refreshSessionIfNeeded();
    return this.sessionId;
  }

  public getUserId(): string | null {
    return this.userId;
  }

  public getTraits(): Record<string, unknown> {
    return { ...this.traits };
  }

  public getSnapshot(): UserIdentity {
    return {
      anonymousId: this.anonymousId,
      sessionId: this.getSessionId(),
      userId: this.userId,
      traits: this.getTraits(),
    };
  }

  /**
   * Associate an authenticated user with the current anonymous profile (Identity Stitching).
   */
  public identify(userId: string, traits: Record<string, unknown> = {}): void {
    this.userId = userId;
    this.traits = { ...this.traits, ...traits };

    this.saveToStorage(STORAGE_KEY_USER, userId);
    this.saveToStorage(STORAGE_KEY_TRAITS, JSON.stringify(this.traits));
    this.touchSession();
  }

  /**
   * Clear user identification (e.g. on logout) and reset session & anonymous identity.
   */
  public reset(): void {
    this.userId = null;
    this.traits = {};
    this.removeFromStorage(STORAGE_KEY_USER);
    this.removeFromStorage(STORAGE_KEY_TRAITS);

    this.anonymousId = 'anon_' + this.generateUuid();
    this.saveToStorage(STORAGE_KEY_ANON, this.anonymousId);

    this.sessionId = 'sess_' + this.generateUuid();
    this.saveToStorage(STORAGE_KEY_SESSION, this.sessionId);
    this.saveToStorage(STORAGE_KEY_SESSION_TS, Date.now().toString());
  }

  /**
   * Touch activity timestamp on active interactions.
   */
  public touchSession(): void {
    this.refreshSessionIfNeeded();
    this.saveToStorage(STORAGE_KEY_SESSION_TS, Date.now().toString());
  }

  private loadOrGenerateAnonymousId(): string {
    const existing = this.readFromStorage(STORAGE_KEY_ANON);
    if (existing) {
      return existing;
    }
    const newId = 'anon_' + this.generateUuid();
    this.saveToStorage(STORAGE_KEY_ANON, newId);
    return newId;
  }

  private loadOrGenerateSessionId(): string {
    const existing = this.readFromStorage(STORAGE_KEY_SESSION);
    const lastActive = parseInt(this.readFromStorage(STORAGE_KEY_SESSION_TS) || '0', 10);
    const now = Date.now();

    if (existing && lastActive > 0 && now - lastActive < this.sessionTimeoutMs) {
      this.saveToStorage(STORAGE_KEY_SESSION_TS, now.toString());
      return existing;
    }

    const newSession = 'sess_' + this.generateUuid();
    this.saveToStorage(STORAGE_KEY_SESSION, newSession);
    this.saveToStorage(STORAGE_KEY_SESSION_TS, now.toString());
    return newSession;
  }

  private refreshSessionIfNeeded(): void {
    const lastActive = parseInt(this.readFromStorage(STORAGE_KEY_SESSION_TS) || '0', 10);
    const now = Date.now();

    if (lastActive > 0 && now - lastActive >= this.sessionTimeoutMs) {
      this.sessionId = 'sess_' + this.generateUuid();
      this.saveToStorage(STORAGE_KEY_SESSION, this.sessionId);
    }

    this.saveToStorage(STORAGE_KEY_SESSION_TS, now.toString());
  }

  private loadUserIdentity(): void {
    this.userId = this.readFromStorage(STORAGE_KEY_USER);
    const traitsRaw = this.readFromStorage(STORAGE_KEY_TRAITS);
    if (traitsRaw) {
      try {
        this.traits = JSON.parse(traitsRaw);
      } catch {
        this.traits = {};
      }
    }
  }

  public generateUuid(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      try {
        return crypto.randomUUID();
      } catch {
        // Fallback if randomUUID fails in restricted context
      }
    }

    // RFC4122 version 4 compliant fallback
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      const v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  private inMemoryStorage: Map<string, string> = new Map();

  private readFromStorage(key: string): string | null {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        return window.localStorage.getItem(key);
      }
    } catch {
      // Storage blocked (private browsing mode)
    }
    return this.inMemoryStorage.get(key) ?? null;
  }

  private saveToStorage(key: string, value: string): void {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem(key, value);
        return;
      }
    } catch {
      // Storage unavailable
    }
    this.inMemoryStorage.set(key, value);
  }

  private removeFromStorage(key: string): void {
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.removeItem(key);
        return;
      }
    } catch {
      // Storage unavailable
    }
    this.inMemoryStorage.delete(key);
  }
}
