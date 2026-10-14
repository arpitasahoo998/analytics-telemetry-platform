import React, { useState, useEffect } from 'react';
import { OrderRecord, fetchOrders, updateOrderStatus, getCachedOrders } from '../api/orderApi';

interface OrderHistoryModalProps {
  initialOrders?: OrderRecord[];
  onClose: () => void;
  onOpenInvoice: (order: OrderRecord) => void;
}

export const OrderHistoryModal: React.FC<OrderHistoryModalProps> = ({
  initialOrders,
  onClose,
  onOpenInvoice,
}) => {
  const [orders, setOrders] = useState<OrderRecord[]>(() => {
    if (initialOrders && initialOrders.length > 0) return initialOrders;
    return getCachedOrders();
  });
  const [loading, setLoading] = useState(() => {
    if (initialOrders && initialOrders.length > 0) return false;
    return getCachedOrders().length === 0;
  });
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'active' | 'delivered'>('all');
  const [updatingId, setUpdatingId] = useState<string | null>(null);

  const loadOrders = async () => {
    try {
      const data = await fetchOrders();
      if (data && data.length > 0) {
        setOrders(data);
      }
    } catch (err: any) {
      if (orders.length === 0) {
        setError(err.message || 'Unable to reach order server');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadOrders();
  }, []);

  const handleAdvanceStatus = async (order: OrderRecord, newStatus: 'out_for_delivery' | 'delivered') => {
    setUpdatingId(order.order_id);
    try {
      const updated = await updateOrderStatus(order.order_id, newStatus);
      const updatedList = orders.map((o) =>
        o.order_id === order.order_id ? { ...o, status: updated.status } : o
      );
      setOrders(updatedList);
      if (newStatus === 'delivered') {
        onOpenInvoice({ ...order, status: 'delivered' });
      }
    } catch (err: any) {
      alert(`Could not update order status: ${err.message}`);
    } finally {
      setUpdatingId(null);
    }
  };

  const filteredOrders = orders.filter((o) => {
    if (filter === 'delivered') return o.status === 'delivered' || o.status === 'completed';
    if (filter === 'active') return o.status !== 'delivered' && o.status !== 'completed';
    return true;
  });

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'delivered':
      case 'completed':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            Delivered
          </span>
        );
      case 'out_for_delivery':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
            <span className="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            Out for Delivery
          </span>
        );
      case 'preparing':
      default:
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
            <span className="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-ping"></span>
            In Kitchen (Preparing)
          </span>
        );
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto animate-fade-in">
      <div className="relative w-full max-w-3xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden my-8 flex flex-col max-h-[90vh]">
        {/* Header */}
        <div className="px-6 py-4 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center text-lg">
              📜
            </div>
            <div>
              <h2 className="text-base font-bold text-white flex items-center gap-2">
                Order History & Invoices
                <span className="text-xs px-2 py-0.5 rounded-full bg-indigo-600/30 text-indigo-300 font-mono font-bold border border-indigo-500/30">
                  {orders.length}
                </span>
              </h2>
              <p className="text-xs text-slate-400">
                Track live order fulfillment and view official tax invoices
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={loadOrders}
              className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs transition-colors flex items-center gap-1"
              title="Refresh Orders"
            >
              <span>🔄</span>
              <span className="hidden sm:inline text-xs">Refresh</span>
            </button>
            <button
              onClick={onClose}
              className="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center text-sm font-bold transition-colors"
            >
              ✕
            </button>
          </div>
        </div>

        {/* Filter Pills */}
        <div className="px-6 py-3 border-b border-slate-800/80 bg-slate-900/50 flex items-center gap-2 overflow-x-auto">
          <button
            onClick={() => setFilter('all')}
            className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap ${
              filter === 'all'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-slate-800/80 text-slate-400 hover:text-white'
            }`}
          >
            All Orders ({orders.length})
          </button>
          <button
            onClick={() => setFilter('active')}
            className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap ${
              filter === 'active'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-slate-800/80 text-slate-400 hover:text-white'
            }`}
          >
            In Kitchen / In Transit ({orders.filter((o) => o.status !== 'delivered' && o.status !== 'completed').length})
          </button>
          <button
            onClick={() => setFilter('delivered')}
            className={`px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap ${
              filter === 'delivered'
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-slate-800/80 text-slate-400 hover:text-white'
            }`}
          >
            Delivered ({orders.filter((o) => o.status === 'delivered' || o.status === 'completed').length})
          </button>
        </div>

        {/* Orders List */}
        <div className="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4">
          {loading && orders.length === 0 ? (
            <div className="py-12 text-center text-slate-400 text-sm">
              <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
              Loading order history...
            </div>
          ) : error && orders.length === 0 ? (
            <div className="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs text-center">
              {error}
            </div>
          ) : filteredOrders.length === 0 ? (
            <div className="py-12 text-center text-slate-400 text-sm">
              <span className="text-3xl block mb-2">🍽️</span>
              <p className="font-semibold text-slate-300">No orders found in this view</p>
              <p className="text-xs text-slate-500 mt-1">Place an order from the menu to track it here!</p>
            </div>
          ) : (
            filteredOrders.map((order) => {
              const isDelivered = order.status === 'delivered' || order.status === 'completed';
              const items = Array.isArray(order.items) ? order.items : [];

              return (
                <div
                  key={order.order_id || order.id}
                  className="bg-slate-950/80 border border-slate-800 rounded-xl p-4 sm:p-5 transition-all hover:border-slate-700 space-y-4"
                >
                  {/* Order Top Bar */}
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-800/80">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono font-bold text-sm text-indigo-400">
                          {order.order_id}
                        </span>
                        {getStatusBadge(order.status)}
                      </div>
                      <p className="text-xs text-slate-400 mt-0.5">
                        {order.customer_name} • {order.table_or_delivery} •{' '}
                        {order.created_at ? new Date(order.created_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) : 'Recent'}
                      </p>
                    </div>

                    <div className="flex items-center gap-3">
                      <div className="text-right">
                        <span className="text-[11px] text-slate-400 block">Total Amount</span>
                        <span className="font-bold text-emerald-400 text-base font-mono">
                          ₹{Math.round(order.total_amount).toLocaleString()}
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Order Status Stepper */}
                  <div className="grid grid-cols-3 gap-2 text-center text-[11px]">
                    <div
                      className={`p-2 rounded-lg border ${
                        order.status === 'placed' || order.status === 'preparing' || order.status === 'out_for_delivery' || isDelivered
                          ? 'bg-indigo-950/40 border-indigo-500/40 text-indigo-300 font-semibold'
                          : 'bg-slate-900 border-slate-800 text-slate-500'
                      }`}
                    >
                      <span>1. Kitchen Preparing 🍳</span>
                    </div>
                    <div
                      className={`p-2 rounded-lg border ${
                        order.status === 'out_for_delivery' || isDelivered
                          ? 'bg-amber-950/40 border-amber-500/40 text-amber-300 font-semibold'
                          : 'bg-slate-900 border-slate-800 text-slate-500'
                      }`}
                    >
                      <span>2. Out for Delivery 🚚</span>
                    </div>
                    <div
                      className={`p-2 rounded-lg border ${
                        isDelivered
                          ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-300 font-bold'
                          : 'bg-slate-900 border-slate-800 text-slate-500'
                      }`}
                    >
                      <span>3. Delivered ✓</span>
                    </div>
                  </div>

                  {/* Items List */}
                  <div className="bg-slate-900/60 rounded-lg p-3 text-xs text-slate-300 space-y-1">
                    <span className="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block mb-1">
                      Dishes Ordered ({items.reduce((s, it) => s + (it.quantity || 1), 0)}):
                    </span>
                    {items.length > 0 ? (
                      <div className="flex flex-wrap gap-2">
                        {items.map((it, idx) => (
                          <span
                            key={idx}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-800 text-slate-200 text-xs border border-slate-700/60"
                          >
                            <span className="font-medium">{it.name}</span>
                            <span className="text-indigo-400 font-mono font-bold">×{it.quantity || 1}</span>
                            <span className="text-slate-400 text-[11px] font-mono">
                              (₹{((it.price || 0) * (it.quantity || 1)).toLocaleString()})
                            </span>
                          </span>
                        ))}
                      </div>
                    ) : (
                      <p className="text-slate-500 italic text-[11px]">Direct dining order (₹{Math.round(order.total_amount).toLocaleString()})</p>
                    )}
                  </div>

                  {/* Action Controls */}
                  <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div className="flex items-center gap-2">
                      {!isDelivered && (
                        <>
                          {order.status !== 'out_for_delivery' && (
                            <button
                              disabled={updatingId === order.order_id}
                              onClick={() => handleAdvanceStatus(order, 'out_for_delivery')}
                              className="px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-xs font-semibold transition-colors flex items-center gap-1.5"
                            >
                              <span>🚚</span>
                              <span>Mark Out for Delivery</span>
                            </button>
                          )}
                          <button
                            disabled={updatingId === order.order_id}
                            onClick={() => handleAdvanceStatus(order, 'delivered')}
                            className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm"
                          >
                            <span>✓</span>
                            <span>Mark Delivered & Generate Invoice</span>
                          </button>
                        </>
                      )}
                    </div>

                    <button
                      onClick={() => onOpenInvoice({ ...order, items })}
                      className="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition-all ml-auto"
                    >
                      <span>🧾</span>
                      <span>View / Print Tax Invoice</span>
                    </button>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Modal Bottom */}
        <div className="px-6 py-3 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between text-xs text-slate-400">
          <span>Invoices include full 5% GST breakdown and printable PDF receipt.</span>
          <button
            onClick={onClose}
            className="px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
