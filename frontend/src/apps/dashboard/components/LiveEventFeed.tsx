import React, { useState } from 'react';
import { RecentEvent } from '../api/analyticsApi';

interface LiveEventFeedProps {
  events: RecentEvent[];
  filterSource: 'all' | 'browser' | 'server';
  onFilterChange: (val: 'all' | 'browser' | 'server') => void;
}

export const LiveEventFeed: React.FC<LiveEventFeedProps> = ({
  events = [],
  filterSource,
  onFilterChange,
}) => {
  const [selectedEvent, setSelectedEvent] = useState<RecentEvent | null>(null);

  const safeEvents = events || [];
  const filtered = filterSource === 'all'
    ? safeEvents
    : safeEvents.filter((e) => e.source === filterSource);

  return (
    <div className="card p-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>⚡</span> Real-Time Event Stream & Inspector
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </h3>
          <p className="text-xs text-slate-400">
            Live stream of ingested telemetry. Click any event to inspect full metadata and payload properties.
          </p>
        </div>

        {/* Source Filter */}
        <div className="flex items-center gap-1.5 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs">
          {(['all', 'browser', 'server'] as const).map((source) => (
            <button
              key={source}
              onClick={() => onFilterChange(source)}
              className={`px-3 py-1 rounded-lg font-medium capitalize transition-all ${
                filterSource === source
                  ? 'bg-indigo-600 text-white'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              {source}
            </button>
          ))}
        </div>
      </div>

      {filtered.length === 0 ? (
        <div className="text-center py-12 text-slate-500 text-xs">
          No live events found matching the selected filter.
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead>
              <tr className="border-b border-slate-800 text-slate-400">
                <th className="pb-3 font-semibold">Timestamp (UTC)</th>
                <th className="pb-3 font-semibold">Event Name</th>
                <th className="pb-3 font-semibold">Origin</th>
                <th className="pb-3 font-semibold">Session / Visitor</th>
                <th className="pb-3 font-semibold">Properties Preview</th>
                <th className="pb-3 font-semibold text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 font-mono">
              {filtered.slice(0, 25).map((evt) => {
                const isServer = evt.source === 'server';
                const propKeys = Object.keys(evt.properties || {});
                const preview = propKeys.length > 0
                  ? propKeys.slice(0, 3).map((k) => `${k}: ${JSON.stringify(evt.properties[k])}`).join(', ')
                  : '—';

                return (
                  <tr
                    key={evt.id}
                    onClick={() => setSelectedEvent(evt)}
                    className="hover:bg-slate-800/50 cursor-pointer transition-colors"
                  >
                    <td className="py-2.5 text-slate-400 whitespace-nowrap">
                      {evt.timestamp ? evt.timestamp.replace('T', ' ').slice(11, 23) : '—'}
                    </td>
                    <td className="py-2.5 font-bold text-white whitespace-nowrap">
                      {evt.event_name}
                    </td>
                    <td className="py-2.5 whitespace-nowrap">
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                          isServer ? 'badge-server' : 'badge-browser'
                        }`}
                      >
                        {isServer ? 'Server' : 'Browser'}
                      </span>
                    </td>
                    <td className="py-2.5 text-slate-400 text-[11px] whitespace-nowrap">
                      <div>
                        <span className="text-slate-500">anon: </span>
                        {evt.anonymous_id?.slice(0, 14)}...
                      </div>
                      {evt.session_id && (
                        <div>
                          <span className="text-slate-500">sess: </span>
                          {evt.session_id.slice(0, 14)}...
                        </div>
                      )}
                    </td>
                    <td className="py-2.5 text-slate-400 max-w-xs truncate text-[11px]">
                      {preview}
                    </td>
                    <td className="py-2.5 text-right whitespace-nowrap">
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          setSelectedEvent(evt);
                        }}
                        className="text-indigo-400 hover:text-indigo-300 text-xs px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 font-sans"
                      >
                        Inspect
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* JSON Inspection Modal */}
      {selectedEvent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-fade-in font-sans">
          <div className="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 overflow-hidden max-h-[85vh] flex flex-col justify-between">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
              <div className="flex items-center gap-2">
                <span className="text-base font-bold text-white">{selectedEvent.event_name}</span>
                <span
                  className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                    selectedEvent.source === 'server' ? 'badge-server' : 'badge-browser'
                  }`}
                >
                  {selectedEvent.source.toUpperCase()}
                </span>
                <span className="text-xs text-slate-500 font-mono">ID: {selectedEvent.event_id}</span>
              </div>
              <button
                onClick={() => setSelectedEvent(null)}
                className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 text-lg"
              >
                ✕
              </button>
            </div>

            <div className="flex-1 overflow-y-auto space-y-3 pr-2">
              <div>
                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Properties
                </h4>
                <pre className="p-3 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 text-xs overflow-x-auto font-mono">
                  {JSON.stringify(selectedEvent.properties, null, 2)}
                </pre>
              </div>

              <div>
                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Context Metadata
                </h4>
                <pre className="p-3 rounded-xl bg-slate-950 border border-slate-800 text-sky-400 text-xs overflow-x-auto font-mono">
                  {JSON.stringify(selectedEvent.context, null, 2)}
                </pre>
              </div>

              <div>
                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                  Identity Stitched
                </h4>
                <div className="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1 font-mono text-slate-300">
                  <div><strong className="text-slate-500">anonymous_id:</strong> {selectedEvent.anonymous_id}</div>
                  <div><strong className="text-slate-500">session_id:</strong> {selectedEvent.session_id || 'null'}</div>
                  <div><strong className="text-slate-500">user_id:</strong> {selectedEvent.user_id || 'null'}</div>
                  <div><strong className="text-slate-500">timestamp:</strong> {selectedEvent.timestamp}</div>
                </div>
              </div>
            </div>

            <div className="pt-4 border-t border-slate-800 flex justify-end">
              <button
                onClick={() => setSelectedEvent(null)}
                className="btn-secondary text-xs"
              >
                Close Inspector
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
