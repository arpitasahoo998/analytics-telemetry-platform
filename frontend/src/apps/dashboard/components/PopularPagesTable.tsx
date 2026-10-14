import React from 'react';
import { PopularPage } from '../api/analyticsApi';

interface PopularPagesTableProps {
  pages: PopularPage[];
}

export const PopularPagesTable: React.FC<PopularPagesTableProps> = ({ pages = [] }) => {
  const safePages = pages || [];
  const totalViews = safePages.reduce((sum, p) => sum + (p.views || 0), 0);

  return (
    <div className="card p-6 flex flex-col justify-between">
      <div>
        <div className="mb-4">
          <h3 className="text-base font-bold text-white flex items-center gap-2">
            <span>📄</span> Most Popular Pages
          </h3>
          <p className="text-xs text-slate-400">Top visited paths, views, and unique visitor ratios</p>
        </div>

        {safePages.length === 0 ? (
          <div className="text-center py-10 text-slate-500 text-xs">
            No page visits logged in this time range.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-slate-800 text-slate-400">
                  <th className="pb-3 font-semibold">Page / Route</th>
                  <th className="pb-3 font-semibold text-right">Views</th>
                  <th className="pb-3 font-semibold text-right">Unique Visitors</th>
                  <th className="pb-3 font-semibold text-right">Traffic Share</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60">
                {safePages.map((page, idx) => {
                  const sharePct = totalViews > 0 ? Math.round((page.views / totalViews) * 100) : 0;
                  return (
                    <tr key={idx} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3 pr-4">
                        <div className="font-mono text-indigo-300 font-semibold">{page.path}</div>
                        <div className="text-[11px] text-slate-400 mt-0.5">{page.title}</div>
                      </td>
                      <td className="py-3 text-right font-bold text-white">
                        {page.views.toLocaleString()}
                      </td>
                      <td className="py-3 text-right text-slate-300">
                        {page.unique_visitors.toLocaleString()}
                      </td>
                      <td className="py-3 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <span className="text-slate-400">{sharePct}%</span>
                          <div className="w-16 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div
                              className="h-full bg-indigo-500 rounded-full"
                              style={{ width: `${sharePct}%` }}
                            ></div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};
