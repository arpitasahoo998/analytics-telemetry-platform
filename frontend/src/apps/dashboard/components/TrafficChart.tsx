import React, { useState } from 'react';
import { TimeseriesPoint } from '../api/analyticsApi';

interface TrafficChartProps {
  data: TimeseriesPoint[];
  interval: 'day' | 'hour';
  onIntervalChange: (val: 'day' | 'hour') => void;
}

export const TrafficChart: React.FC<TrafficChartProps> = ({
  data,
  interval,
  onIntervalChange,
}) => {
  const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);

  if (data.length === 0) {
    return (
      <div className="card p-6 text-center py-16">
        <p className="text-3xl mb-2">📈</p>
        <p className="text-sm font-semibold text-slate-300">No traffic data recorded in this time range.</p>
        <p className="text-xs text-slate-500 mt-1">Interact with the restaurant demo app to generate events.</p>
      </div>
    );
  }

  // Calculate dimensions and max scales for SVG rendering
  const width = 800;
  const height = 240;
  const padding = { top: 20, right: 30, bottom: 40, left: 50 };
  const chartWidth = width - padding.left - padding.right;
  const chartHeight = height - padding.top - padding.bottom;

  const maxVal = Math.max(
    ...data.map((d) =>
      Math.max(
        Number(d.total_events || 0),
        Number(d.page_views || 0),
        Number(d.unique_visitors || 0),
        Number(d.orders || 0),
        1
      )
    ),
    1
  );

  const getX = (index: number) => {
    if (data.length === 1) return padding.left + chartWidth / 2;
    return padding.left + (index / (data.length - 1)) * chartWidth;
  };

  const getY = (val: number) => {
    return padding.top + chartHeight - (val / maxVal) * chartHeight;
  };

  // Build SVG path strings
  const buildLinePath = (getter: (d: TimeseriesPoint) => number) => {
    if (data.length === 1) {
      const x = getX(0);
      const y = getY(getter(data[0]));
      return `M ${x - 20} ${y} L ${x + 20} ${y}`;
    }
    return data
      .map((d, i) => `${i === 0 ? 'M' : 'L'} ${getX(i).toFixed(1)} ${getY(getter(d)).toFixed(1)}`)
      .join(' ');
  };

  const buildAreaPath = (getter: (d: TimeseriesPoint) => number) => {
    if (data.length === 1) return '';
    const line = data
      .map((d, i) => `${i === 0 ? 'M' : 'L'} ${getX(i).toFixed(1)} ${getY(getter(d)).toFixed(1)}`)
      .join(' ');
    const lastX = getX(data.length - 1);
    const firstX = getX(0);
    const bottomY = padding.top + chartHeight;
    return `${line} L ${lastX} ${bottomY} L ${firstX} ${bottomY} Z`;
  };

  const totalEventsPath = buildLinePath((d) => d.total_events);
  const totalEventsArea = buildAreaPath((d) => d.total_events);
  const pageViewsPath = buildLinePath((d) => d.page_views);
  const visitorsPath = buildLinePath((d) => d.unique_visitors);

  const hoveredPoint = hoveredIndex !== null ? data[hoveredIndex] : null;

  return (
    <div className="card p-6 relative">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>📈</span> Traffic & Visitors Over Time
          </h3>
          <p className="text-xs text-slate-400">Time-series breakdown of client pageviews, unique visitors, and backend events</p>
        </div>

        <div className="flex items-center gap-4">
          {/* Legend */}
          <div className="flex items-center gap-3 text-xs">
            <span className="flex items-center gap-1.5 text-indigo-400">
              <span className="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Total Events
            </span>
            <span className="flex items-center gap-1.5 text-sky-400">
              <span className="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Page Views
            </span>
            <span className="flex items-center gap-1.5 text-emerald-400">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Unique Visitors
            </span>
          </div>

          {/* Interval Toggle */}
          <div className="bg-slate-900 p-0.5 rounded-lg border border-slate-700/80 flex text-xs">
            <button
              onClick={() => onIntervalChange('day')}
              className={`px-2.5 py-1 rounded-md font-medium transition-all ${
                interval === 'day' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'
              }`}
            >
              Day
            </button>
            <button
              onClick={() => onIntervalChange('hour')}
              className={`px-2.5 py-1 rounded-md font-medium transition-all ${
                interval === 'hour' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'
              }`}
            >
              Hour
            </button>
          </div>
        </div>
      </div>

      {/* SVG Chart */}
      <div className="relative w-full overflow-x-auto">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="w-full h-64 overflow-visible select-none"
        >
          <defs>
            <linearGradient id="eventGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="#6366f1" stopOpacity="0.3" />
              <stop offset="100%" stopColor="#6366f1" stopOpacity="0.0" />
            </linearGradient>
          </defs>

          {/* Grid lines */}
          {[0, 0.25, 0.5, 0.75, 1].map((ratio, i) => {
            const y = padding.top + chartHeight * (1 - ratio);
            const val = Math.round(maxVal * ratio);
            return (
              <g key={i}>
                <line
                  x1={padding.left}
                  y1={y}
                  x2={width - padding.right}
                  y2={y}
                  stroke="#334155"
                  strokeDasharray="4 4"
                  strokeWidth="1"
                />
                <text
                  x={padding.left - 10}
                  y={y + 4}
                  fill="#64748b"
                  fontSize="10"
                  textAnchor="end"
                >
                  {val}
                </text>
              </g>
            );
          })}

          {/* Gradient Area */}
          {totalEventsArea && <path d={totalEventsArea} fill="url(#eventGradient)" />}

          {/* Lines */}
          <path
            d={totalEventsPath}
            fill="none"
            stroke="#6366f1"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d={pageViewsPath}
            fill="none"
            stroke="#38bdf8"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
          <path
            d={visitorsPath}
            fill="none"
            stroke="#34d399"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          />

          {/* Data Points & Hover Targets */}
          {data.map((d, i) => {
            const cx = getX(i);
            const cyEvents = getY(d.total_events);
            const isHovered = hoveredIndex === i;

            return (
              <g key={i}>
                {/* Vertical guide line on hover */}
                {isHovered && (
                  <line
                    x1={cx}
                    y1={padding.top}
                    x2={cx}
                    y2={padding.top + chartHeight}
                    stroke="#94a3b8"
                    strokeWidth="1"
                    strokeDasharray="2 2"
                  />
                )}

                {/* Point on total events */}
                <circle
                  cx={cx}
                  cy={cyEvents}
                  r={isHovered ? 6 : 3.5}
                  fill="#6366f1"
                  stroke="#1e293b"
                  strokeWidth="2"
                />

                {/* Point on unique visitors */}
                <circle
                  cx={cx}
                  cy={getY(d.unique_visitors)}
                  r={isHovered ? 5 : 3}
                  fill="#34d399"
                  stroke="#1e293b"
                  strokeWidth="2"
                />

                {/* Invisible hover trigger column */}
                <rect
                  x={cx - (chartWidth / (data.length * 2 || 1))}
                  y={padding.top}
                  width={chartWidth / (data.length || 1)}
                  height={chartHeight}
                  fill="transparent"
                  className="cursor-pointer"
                  onMouseEnter={() => setHoveredIndex(i)}
                  onMouseLeave={() => setHoveredIndex(null)}
                />

                {/* X Axis label */}
                <text
                  x={cx}
                  y={height - 12}
                  fill="#64748b"
                  fontSize="10"
                  textAnchor="middle"
                >
                  {interval === 'hour'
                    ? d.period.split(' ')[1] || d.period
                    : d.period.slice(5)}
                </text>
              </g>
            );
          })}
        </svg>

        {/* Hover Tooltip Popup */}
        {hoveredPoint && hoveredIndex !== null && (
          <div
            className="absolute z-20 pointer-events-none p-3 rounded-xl bg-slate-900 border border-slate-700 shadow-xl text-xs space-y-1 transform -translate-x-1/2 -translate-y-full animate-fade-in"
            style={{
              left: `${(getX(hoveredIndex) / width) * 100}%`,
              top: `${getY(hoveredPoint.total_events) - 10}px`,
            }}
          >
            <div className="font-bold text-white border-b border-slate-800 pb-1 mb-1">
              {hoveredPoint.period}
            </div>
            <div className="text-indigo-400 flex justify-between gap-4">
              <span>Total Events:</span>
              <span className="font-bold">{hoveredPoint.total_events}</span>
            </div>
            <div className="text-sky-400 flex justify-between gap-4">
              <span>Page Views:</span>
              <span className="font-bold">{hoveredPoint.page_views}</span>
            </div>
            <div className="text-emerald-400 flex justify-between gap-4">
              <span>Unique Visitors:</span>
              <span className="font-bold">{hoveredPoint.unique_visitors}</span>
            </div>
            <div className="text-amber-400 flex justify-between gap-4">
              <span>Orders Placed:</span>
              <span className="font-bold">{hoveredPoint.orders}</span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
