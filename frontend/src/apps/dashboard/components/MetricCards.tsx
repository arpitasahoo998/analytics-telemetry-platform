import React from 'react';
import { OverviewData } from '../api/analyticsApi';

interface MetricCardsProps {
  overview: OverviewData | null;
}

export const MetricCards: React.FC<MetricCardsProps> = ({ overview }) => {
  const cards = [
    {
      label: 'Total Page Views',
      value: overview ? overview.page_views.toLocaleString() : '—',
      icon: '👁️',
      subtext: overview ? `${overview.total_events.toLocaleString()} total captured events` : '',
      accent: 'border-indigo-500/30 text-indigo-400',
    },
    {
      label: 'Unique Visitors',
      value: overview ? overview.unique_visitors.toLocaleString() : '—',
      icon: '👤',
      subtext: overview ? `${overview.total_sessions} active sessions` : '',
      accent: 'border-sky-500/30 text-sky-400',
    },
    {
      label: 'Total Sessions',
      value: overview ? overview.total_sessions.toLocaleString() : '—',
      icon: '⏱️',
      subtext: '30-min sliding timeout model',
      accent: 'border-purple-500/30 text-purple-400',
    },
    {
      label: 'Server Orders Created',
      value: overview ? overview.orders_created.toLocaleString() : '—',
      icon: '📦',
      badge: 'Server Verified',
      subtext: overview ? `${overview.server_events} server-side business events` : '',
      accent: 'border-emerald-500/30 text-emerald-400',
    },
    {
      label: 'Gross Order Revenue',
      value: overview ? `₹${Number(overview.total_revenue ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '—',
      icon: '💰',
      subtext: overview ? `Avg order: ₹${Number(overview.avg_order_value ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '',
      accent: 'border-amber-500/30 text-amber-400',
    },
    {
      label: 'Visitor Conversion Rate',
      value: overview ? `${Number(overview.conversion_rate_percent ?? 0).toFixed(1)}%` : '—',
      icon: '🎯',
      subtext: 'Visitors converted to orders',
      accent: 'border-rose-500/30 text-rose-400',
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      {cards.map((card, idx) => (
        <div
          key={idx}
          className={`card p-4 relative overflow-hidden flex flex-col justify-between border ${card.accent} hover:translate-y-[-2px] transition-all`}
        >
          <div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xl">{card.icon}</span>
              {card.badge && (
                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                  {card.badge}
                </span>
              )}
            </div>
            <p className="text-xs font-medium text-slate-400">{card.label}</p>
            <h3 className="text-2xl font-extrabold text-white mt-1">{card.value}</h3>
          </div>

          <div className="mt-3 pt-2 border-t border-slate-800 text-[11px] text-slate-400 truncate">
            {card.subtext}
          </div>
        </div>
      ))}
    </div>
  );
};
