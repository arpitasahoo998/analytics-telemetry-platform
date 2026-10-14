import React from 'react';
import { EventBreakdownItem } from '../api/analyticsApi';

interface EventBreakdownChartProps {
  items: EventBreakdownItem[];
}

export const EventBreakdownChart: React.FC<EventBreakdownChartProps> = ({ items = [] }) => {
  const safeItems = items || [];
  const totalEvents = safeItems.reduce((sum, item) => sum + (item.count || 0), 0);

  return (
    <div className="card p-6 flex flex-col justify-between">
      <div>
        <div className="mb-4">
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>📊</span> Event Trends & Source Breakdown
          </h3>
          <p className="text-xs text-slate-400">Captured event volumes categorized by browser vs server origin</p>
        </div>

        {safeItems.length === 0 ? (
          <div className="text-center py-10 text-slate-500 text-xs">
            No events logged in this time range.
          </div>
        ) : (
          <div className="space-y-3">
            {safeItems.map((item, idx) => {
              const pct = totalEvents > 0 ? Math.round((item.count / totalEvents) * 100) : 0;
              const isServer = item.source === 'server';

              return (
                <div key={idx} className="space-y-1">
                  <div className="flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2">
                      <span className="font-mono font-semibold text-white">
                        {item.event_name}
                      </span>
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                          isServer ? 'badge-server' : 'badge-browser'
                        }`}
                      >
                        {isServer ? 'Server' : 'Browser'}
                      </span>
                    </div>

                    <div className="flex items-center gap-3">
                      <span className="text-slate-400 text-[11px]">
                        {item.unique_users} users
                      </span>
                      <span className="font-bold text-white">
                        {item.count.toLocaleString()} ({pct}%)
                      </span>
                    </div>
                  </div>

                  <div className="h-2 w-full bg-slate-900 rounded-full overflow-hidden border border-slate-800">
                    <div
                      className={`h-full rounded-full transition-all duration-300 ${
                        isServer ? 'bg-emerald-500' : 'bg-indigo-500'
                      }`}
                      style={{ width: `${Math.max(pct, 2)}%` }}
                    ></div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};
