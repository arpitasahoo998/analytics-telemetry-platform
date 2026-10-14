import React, { useState } from 'react';
import { OrderRecord, updateOrderStatus } from '../api/orderApi';

interface OrderConfirmationProps {
  order: OrderRecord;
  onContinueShopping: () => void;
  onOpenDashboard: () => void;
  onOpenHistory: () => void;
  onOpenInvoice: (order: OrderRecord) => void;
}

export const OrderConfirmation: React.FC<OrderConfirmationProps> = ({
  order: initialOrder,
  onContinueShopping,
  onOpenDashboard,
  onOpenHistory,
  onOpenInvoice,
}) => {
  const [order, setOrder] = useState<OrderRecord>(initialOrder);
  const [isUpdating, setIsUpdating] = useState(false);

  const isDelivered = order.status === 'delivered' || order.status === 'completed';

  const handleSimulateDelivery = async () => {
    setIsUpdating(true);
    try {
      const updated = await updateOrderStatus(order.order_id, 'delivered');
      const finalOrder: OrderRecord = { ...order, status: updated.status };
      setOrder(finalOrder);
      // Immediately open the generated tax invoice
      onOpenInvoice(finalOrder);
    } catch (err: any) {
      alert(`Could not update order status: ${err.message}`);
    } finally {
      setIsUpdating(false);
    }
  };

  return (
    <div className="card max-w-xl mx-auto p-6 sm:p-8 text-center animate-fade-in border-emerald-500/30 shadow-2xl">
      <div className="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mb-4 border border-emerald-500/40">
        ✓
      </div>

      <h2 className="text-2xl font-bold text-white mb-2">Order Confirmed!</h2>
      <p className="text-slate-400 text-sm mb-6">
        Thank you, <strong className="text-white">{order.customer_name}</strong>. Your meal is being prepared by our master chefs.
      </p>

      {/* Order Summary Details */}
      <div className="bg-slate-800/80 rounded-2xl p-5 mb-6 text-left border border-slate-700/60 space-y-3">
        <div className="flex items-center justify-between border-b border-slate-700/60 pb-2">
          <span className="text-xs text-slate-400">Order Reference:</span>
          <span className="font-mono text-xs font-bold text-indigo-400">{order.order_id}</span>
        </div>
        <div className="flex items-center justify-between border-b border-slate-700/60 pb-2">
          <span className="text-xs text-slate-400">Total Billed:</span>
          <span className="font-bold text-emerald-400">₹{Math.round(order.total_amount).toLocaleString()} ({order.currency})</span>
        </div>
        <div className="flex items-center justify-between border-b border-slate-700/60 pb-2">
          <span className="text-xs text-slate-400">Items Ordered:</span>
          <span className="text-xs text-white font-medium">
            {order.items_count || (Array.isArray(order.items) ? order.items.length : 1)} dishes
          </span>
        </div>

        {Array.isArray(order.items) && order.items.length > 0 && (
          <div className="pt-1 pb-1 border-b border-slate-700/60 space-y-1">
            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Dish Breakdown:</span>
            {order.items.map((it, idx) => (
              <div key={idx} className="flex items-center justify-between text-xs text-slate-200">
                <span className="truncate pr-2">• {it.name} <span className="text-slate-400 text-[11px]">×{it.quantity || 1}</span></span>
                <span className="font-mono text-slate-300 shrink-0">₹{((it.price || 0) * (it.quantity || 1)).toLocaleString()}</span>
              </div>
            ))}
          </div>
        )}

        <div className="flex items-center justify-between">
          <span className="text-xs text-slate-400">Fulfillment Status:</span>
          {isDelivered ? (
            <span className="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              Delivered ✓
            </span>
          ) : (
            <span className="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
              <span className="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
              Kitchen Preparing 🍳
            </span>
          )}
        </div>
      </div>

      {/* Live Delivery & Invoice Banner */}
      <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 mb-6 text-left">
        <div className="flex items-center justify-between mb-3">
          <span className="text-xs font-bold text-slate-300 uppercase tracking-wider">
            Live Delivery & Invoice Progress
          </span>
          <span className="text-[11px] text-slate-400">
            {isDelivered ? 'Delivered & Invoice Ready' : 'Real-time Tracking'}
          </span>
        </div>

        {/* 3-Step Tracker */}
        <div className="grid grid-cols-3 gap-2 text-center text-[10px] mb-4">
          <div className="p-2 rounded bg-indigo-950/60 border border-indigo-500/40 text-indigo-300 font-semibold">
            1. Kitchen Preparing 🍳
          </div>
          <div
            className={`p-2 rounded border ${
              isDelivered
                ? 'bg-amber-950/60 border-amber-500/40 text-amber-300 font-semibold'
                : 'bg-slate-950 border-slate-800 text-slate-500'
            }`}
          >
            2. Out for Delivery 🚚
          </div>
          <div
            className={`p-2 rounded border ${
              isDelivered
                ? 'bg-emerald-950/60 border-emerald-500/40 text-emerald-300 font-bold'
                : 'bg-slate-950 border-slate-800 text-slate-500'
            }`}
          >
            3. Delivered ✓
          </div>
        </div>

        {/* Invoice Action Button */}
        {isDelivered ? (
          <button
            onClick={() => onOpenInvoice(order)}
            className="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all"
          >
            <span>🧾</span>
            <span>View & Print Official Tax Invoice (GST)</span>
          </button>
        ) : (
          <div className="space-y-2">
            <button
              disabled={isUpdating}
              onClick={handleSimulateDelivery}
              className="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md transition-all"
            >
              <span>🚚</span>
              <span>{isUpdating ? 'Updating...' : 'Mark as Delivered & Generate Invoice'}</span>
            </button>
            <button
              onClick={() => onOpenInvoice(order)}
              className="w-full py-2 px-3 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors border border-slate-700/60"
            >
              <span>🧾</span>
              <span>Preview Tax Invoice Now</span>
            </button>
          </div>
        )}
      </div>

      {/* Navigation Actions */}
      <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          onClick={onOpenHistory}
          className="btn-secondary w-full sm:w-auto text-xs px-5 py-2.5 border-slate-700 hover:border-slate-600"
        >
          📜 Order History
        </button>
        <button
          onClick={onOpenDashboard}
          className="btn-primary w-full sm:w-auto text-xs px-5 py-2.5"
        >
          📊 View in Analytics
        </button>
        <button
          onClick={onContinueShopping}
          className="btn-secondary w-full sm:w-auto text-xs px-5 py-2.5"
        >
          Back to Menu
        </button>
      </div>
    </div>
  );
};
