import React from 'react';
import { CartItem } from '../api/orderApi';
import { VegBadge } from './VegBadge';

interface CartDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  items: CartItem[];
  onUpdateQuantity: (id: string, delta: number) => void;
  onRemoveItem: (id: string) => void;
  onProceedToCheckout: () => void;
}

export const CartDrawer: React.FC<CartDrawerProps> = ({
  isOpen,
  onClose,
  items,
  onUpdateQuantity,
  onRemoveItem,
  onProceedToCheckout,
}) => {
  if (!isOpen) return null;

  const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const gst = Math.round(subtotal * 0.05); // 5% GST
  const total = subtotal + gst;

  return (
    <div className="fixed inset-0 z-50 flex justify-end bg-black/70 backdrop-blur-sm animate-fade-in">
      <div className="w-full max-w-md bg-slate-900 border-l border-slate-800 h-full flex flex-col justify-between shadow-2xl p-6">
        {/* Drawer Header */}
        <div className="flex items-center justify-between border-b border-slate-800 pb-4">
          <div className="flex items-center gap-2">
            <span className="text-xl">🛒</span>
            <h2 className="text-lg font-bold text-white">Your Order Cart</h2>
          </div>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 text-lg transition-colors"
          >
            ✕
          </button>
        </div>

        {/* Items List */}
        <div className="flex-1 overflow-y-auto py-4 space-y-3">
          {items.length === 0 ? (
            <div className="text-center py-16 text-slate-400">
              <p className="text-4xl mb-3">🍲</p>
              <p className="text-sm font-semibold text-white">Your cart is empty.</p>
              <p className="text-xs text-slate-500 mt-1">Explore our Indian, Mexican, Italian, and Chinese specialties!</p>
            </div>
          ) : (
            items.map((item) => (
              <div
                key={item.id}
                className="flex items-center justify-between p-3.5 rounded-xl bg-slate-800/80 border border-slate-700/60"
              >
                <div className="flex-1 mr-3">
                  <div className="flex items-center gap-2">
                    {item.isVeg !== undefined && <VegBadge isVeg={item.isVeg} size="xs" />}
                    <h4 className="font-semibold text-white text-sm">{item.name}</h4>
                  </div>
                  <p className="text-xs text-emerald-400 font-bold mt-0.5">
                    ₹{item.price} each
                  </p>
                </div>

                <div className="flex items-center gap-2.5">
                  <div className="flex items-center border border-slate-700 rounded-lg bg-slate-900 overflow-hidden">
                    <button
                      onClick={() => onUpdateQuantity(item.id, -1)}
                      className="px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                      -
                    </button>
                    <span className="px-2.5 text-xs font-bold text-white">{item.quantity}</span>
                    <button
                      onClick={() => onUpdateQuantity(item.id, 1)}
                      className="px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                      +
                    </button>
                  </div>
                  <button
                    onClick={() => onRemoveItem(item.id)}
                    className="text-rose-400 hover:text-rose-300 text-xs p-1"
                    title="Remove item"
                  >
                    🗑️
                  </button>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Pricing Summary & Checkout CTA */}
        {items.length > 0 && (
          <div className="border-t border-slate-800 pt-4 space-y-3">
            <div className="space-y-1.5 text-xs text-slate-400">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span className="text-white font-medium">₹{subtotal.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span>GST (5%)</span>
                <span className="text-white font-medium">₹{gst.toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-base font-bold text-white pt-2 border-t border-slate-800">
                <span>Total Amount</span>
                <span className="text-emerald-400">₹{total.toLocaleString()}</span>
              </div>
            </div>

            <button
              onClick={onProceedToCheckout}
              className="btn-primary w-full py-3 text-sm justify-center font-bold shadow-lg"
            >
              Proceed to Checkout (₹{total.toLocaleString()})
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
