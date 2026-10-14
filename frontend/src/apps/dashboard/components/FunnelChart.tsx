import React from 'react';
import { FunnelStep } from '../api/analyticsApi';

interface FunnelChartProps {
  steps: FunnelStep[];
}

export const FunnelChart: React.FC<FunnelChartProps> = ({ steps = [] }) => {
  const safeSteps = steps || [];
  const maxVisitors = Math.max(...safeSteps.map((s) => Number(s.visitors || 0)), 1);

  if (safeSteps.length === 0) {
    return (
      <div className="card p-6 text-center py-12">
        <p className="text-3xl mb-2">🎯</p>
        <p className="text-sm font-semibold text-slate-300">Conversion Funnel Pending</p>
        <p className="text-xs text-slate-500 mt-1">Visit the restaurant demo and place orders to populate the conversion funnel.</p>
      </div>
    );
  }

  return (
    <div className="card p-6">
      <div className="mb-6">
        <h3 className="text-base font-bold text-white flex items-center gap-2">
          <span>🎯</span> Restaurant Conversion Funnel
        </h3>
        <p className="text-xs text-slate-400">
          User journey progression from initial menu discovery to verified order creation
        </p>
      </div>

      <div className="space-y-4">
        {safeSteps.map((step, idx) => {
          const visitors = Number(step.visitors || 0);
          const widthPct = Math.max(Math.round((visitors / maxVisitors) * 100), 4);
          const isFinal = idx === safeSteps.length - 1;

          return (
            <div key={step.step} className="space-y-1.5">
              <div className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <span className="w-5 h-5 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-[11px] text-slate-300">
                    {step.step}
                  </span>
                  <span className="font-semibold text-white">{step.name}</span>
                  <code className="text-[10px] text-slate-500 bg-slate-900 px-1.5 py-0.5 rounded">
                    {step.event_name}
                  </code>
                </div>

                <div className="flex items-center gap-3">
                  <span className="text-slate-400">
                    <strong className="text-white">{step.visitors.toLocaleString()}</strong> unique visitors
                  </span>
                  {idx > 0 && (
                    <span
                      className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${
                        step.step_conversion_pct >= 50
                          ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                          : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                      }`}
                    >
                      {step.step_conversion_pct}% retention
                    </span>
                  )}
                </div>
              </div>

              {/* Progress Bar Container */}
              <div className="h-6 w-full bg-slate-900 rounded-xl overflow-hidden p-1 border border-slate-800 flex items-center">
                <div
                  className={`h-full rounded-lg transition-all duration-500 flex items-center px-2 text-[10px] font-bold text-white ${
                    isFinal
                      ? 'bg-gradient-to-r from-emerald-500 to-teal-400'
                      : 'bg-gradient-to-r from-indigo-500 to-purple-500'
                  }`}
                  style={{ width: `${widthPct}%` }}
                >
                  {widthPct > 12 && `${step.visitors} visitors (${step.overall_conversion_pct}%)`}
                </div>
              </div>

              {/* Drop-off notice */}
              {idx > 0 && step.dropoff_pct > 0 && (
                <div className="text-[10px] text-slate-500 pl-7 flex items-center gap-1">
                  <span>↓ {step.dropoff_pct}% drop-off from previous step</span>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};
