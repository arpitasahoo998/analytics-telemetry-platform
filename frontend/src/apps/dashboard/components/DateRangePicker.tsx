import React from 'react';

export interface DateRange {
  preset: 'today' | '7d' | '30d' | 'all' | 'custom';
  startMs: number;
  endMs: number;
}

interface DateRangePickerProps {
  range: DateRange;
  onChange: (range: DateRange) => void;
  autoRefresh: boolean;
  onToggleAutoRefresh: (val: boolean) => void;
  onRefresh: () => void;
  isLoading: boolean;
}

export const DateRangePicker: React.FC<DateRangePickerProps> = ({
  range,
  onChange,
  autoRefresh,
  onToggleAutoRefresh,
  onRefresh,
  isLoading,
}) => {
  const handlePreset = (preset: 'today' | '7d' | '30d' | 'all') => {
    const now = Date.now();
    let start = now;

    if (preset === 'today') {
      const todayStart = new Date();
      todayStart.setHours(0, 0, 0, 0);
      start = todayStart.getTime();
    } else if (preset === '7d') {
      start = now - 7 * 24 * 60 * 60 * 1000;
    } else if (preset === '30d') {
      start = now - 30 * 24 * 60 * 60 * 1000;
    } else if (preset === 'all') {
      start = now - 365 * 24 * 60 * 60 * 1000;
    }

    onChange({
      preset,
      startMs: start,
      endMs: now,
    });
  };

  const handleCustomDateChange = (field: 'start' | 'end', val: string) => {
    if (!val) return;
    const epoch = new Date(val).getTime();
    if (isNaN(epoch)) return;

    onChange({
      preset: 'custom',
      startMs: field === 'start' ? epoch : range.startMs,
      endMs: field === 'end' ? epoch : range.endMs,
    });
  };

  const toInputDate = (epochMs: number) => {
    return new Date(epochMs).toISOString().split('T')[0];
  };

  return (
    <div className="flex flex-wrap items-center justify-between gap-4 p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60">
      {/* Preset Buttons */}
      <div className="flex items-center gap-1.5 flex-wrap">
        <span className="text-xs font-semibold text-slate-400 mr-1">Time Range:</span>
        {(['today', '7d', '30d', 'all'] as const).map((p) => {
          const labels: Record<string, string> = {
            today: 'Today',
            '7d': 'Last 7 Days',
            '30d': 'Last 30 Days',
            all: 'All Time',
          };
          return (
            <button
              key={p}
              onClick={() => handlePreset(p)}
              className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all ${
                range.preset === p
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'bg-slate-900/60 text-slate-300 hover:text-white hover:bg-slate-700/60'
              }`}
            >
              {labels[p]}
            </button>
          );
        })}
      </div>

      {/* Custom Date Pickers */}
      <div className="flex items-center gap-2 text-xs">
        <span className="text-slate-400">From:</span>
        <input
          type="date"
          value={toInputDate(range.startMs)}
          onChange={(e) => handleCustomDateChange('start', e.target.value)}
          className="bg-slate-900 px-2.5 py-1.5 rounded-lg border border-slate-700 text-slate-200 text-xs focus:outline-none focus:border-indigo-500"
        />
        <span className="text-slate-400">To:</span>
        <input
          type="date"
          value={toInputDate(range.endMs)}
          onChange={(e) => handleCustomDateChange('end', e.target.value)}
          className="bg-slate-900 px-2.5 py-1.5 rounded-lg border border-slate-700 text-slate-200 text-xs focus:outline-none focus:border-indigo-500"
        />
      </div>

      {/* Auto-Refresh & Manual Refresh */}
      <div className="flex items-center gap-3">
        <label className="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
          <input
            type="checkbox"
            checked={autoRefresh}
            onChange={(e) => onToggleAutoRefresh(e.target.checked)}
            className="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-0"
          />
          <span>Auto-refresh (5s)</span>
          {autoRefresh && (
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          )}
        </label>

        <button
          onClick={onRefresh}
          disabled={isLoading}
          className="btn-secondary text-xs px-3 py-1.5"
          title="Refresh Data"
        >
          <span className={isLoading ? 'animate-spin inline-block' : ''}>🔄</span>
          <span>{isLoading ? 'Updating...' : 'Refresh'}</span>
        </button>
      </div>
    </div>
  );
};
