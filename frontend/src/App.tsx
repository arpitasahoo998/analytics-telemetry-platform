import React, { useState, useEffect } from 'react';
import { init, AnalyticsBrowserClient } from '@analytics/browser-sdk';
import { RestaurantApp } from './apps/restaurant/RestaurantApp';
import { DashboardApp } from './apps/dashboard/DashboardApp';

// Initialize Browser SDK
const analyticsClient: AnalyticsBrowserClient = init({
  endpoint: '/api/v1/events',
  appId: 'restaurant-demo-app',
  batchSize: 4,
  batchIntervalMs: 2000,
  debug: true,
});

export const App: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'restaurant' | 'dashboard'>('restaurant');
  const [backendStatus, setBackendStatus] = useState<'checking' | 'online' | 'offline'>('checking');

  // Verify backend health
  useEffect(() => {
    const checkHealth = async () => {
      try {
        const res = await fetch('/api/health');
        if (res.ok) {
          setBackendStatus('online');
        } else {
          setBackendStatus('offline');
        }
      } catch {
        setBackendStatus('offline');
      }
    };
    checkHealth();
    const interval = window.setInterval(checkHealth, 10000);
    return () => window.clearInterval(interval);
  }, []);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
      {/* Top Application Switcher Bar */}
      <div className="bg-slate-900 border-b border-slate-800 px-4 py-2 flex items-center justify-between text-xs z-40">
        <div className="flex items-center gap-2">
          <span className="font-bold tracking-wider text-slate-400 uppercase text-[10px]">
            Analytics Platform
          </span>
          <span className="text-slate-600">•</span>
          <div className="flex items-center gap-1.5">
            <span
              className={`w-2 h-2 rounded-full ${
                backendStatus === 'online'
                  ? 'bg-emerald-400 animate-pulse'
                  : backendStatus === 'checking'
                  ? 'bg-amber-400'
                  : 'bg-rose-500'
              }`}
            ></span>
            <span className="text-slate-400 font-mono text-[11px]">
              System: {backendStatus}
            </span>
          </div>
        </div>

        {/* View Switcher Pill */}
        <div className="bg-slate-950 p-1 rounded-xl border border-slate-800 flex items-center gap-1 shadow-inner">
          <button
            onClick={() => setActiveTab('restaurant')}
            className={`px-3 py-1 rounded-lg font-semibold transition-all flex items-center gap-1.5 ${
              activeTab === 'restaurant'
                ? 'bg-indigo-600 text-white shadow'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            <span>🍽️</span>
            <span>Restaurant Demo</span>
          </button>
          <button
            onClick={() => setActiveTab('dashboard')}
            className={`px-3 py-1 rounded-lg font-semibold transition-all flex items-center gap-1.5 ${
              activeTab === 'dashboard'
                ? 'bg-indigo-600 text-white shadow'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            <span>📊</span>
            <span>Analytics Dashboard</span>
          </button>
        </div>
      </div>

      {/* Main View Router */}
      <div className="flex-1">
        {activeTab === 'restaurant' ? (
          <RestaurantApp
            analytics={analyticsClient}
            onSwitchToDashboard={() => setActiveTab('dashboard')}
          />
        ) : (
          <DashboardApp
            onSwitchToRestaurant={() => setActiveTab('restaurant')}
          />
        )}
      </div>
    </div>
  );
};
