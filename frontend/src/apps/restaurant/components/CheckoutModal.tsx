import React, { useState } from 'react';
import { CartItem, PlaceOrderPayload, OrderConfirmationData, submitOrder } from '../api/orderApi';
import { UserIdentity } from '@analytics/browser-sdk';
import { VegBadge } from './VegBadge';

interface CheckoutModalProps {
  isOpen: boolean;
  onClose: () => void;
  items: CartItem[];
  identity: UserIdentity;
  onOrderSuccess: (order: OrderConfirmationData) => void;
}

export const CheckoutModal: React.FC<CheckoutModalProps> = ({
  isOpen,
  onClose,
  items,
  identity,
  onOrderSuccess,
}) => {
  if (!isOpen) return null;

  const [customerName, setCustomerName] = useState(() => localStorage.getItem('bistro_customer_name') || '');
  const [customerEmail, setCustomerEmail] = useState(() => localStorage.getItem('bistro_customer_email') || '');
  const [tableOrDelivery, setTableOrDelivery] = useState(() => localStorage.getItem('bistro_table_or_delivery') || 'Table 4 (Indoor AC)');
  const [paymentMethod, setPaymentMethod] = useState('upi');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const gst = Math.round(subtotal * 0.05);
  const totalAmount = subtotal + gst;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!customerName.trim() || !customerEmail.trim() || items.length === 0) {
      setErrorMessage('Please provide your name, contact email, and at least 1 dish.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);

    // Dynamically persist user details for future orders
    try {
      localStorage.setItem('bistro_customer_name', customerName.trim());
      localStorage.setItem('bistro_customer_email', customerEmail.trim());
      localStorage.setItem('bistro_table_or_delivery', tableOrDelivery);
    } catch {
      // Ignore quota
    }

    const payload: PlaceOrderPayload = {
      customerName: customerName.trim(),
      customerEmail: customerEmail.trim(),
      tableOrDelivery,
      items,
      totalAmount,
      currency: 'INR',
      identity,
    };

    try {
      const confirmation = await submitOrder(payload);
      setIsSubmitting(false);
      onOrderSuccess(confirmation);
    } catch (err: unknown) {
      setIsSubmitting(false);
      setErrorMessage(err instanceof Error ? err.message : 'An error occurred during order submission.');
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
      <div className="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 overflow-hidden">
        <div className="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
          <div>
            <h3 className="text-lg font-bold text-white flex items-center gap-2">
              <span>💳</span> Finalize Your Dining Order
            </h3>
            <p className="text-xs text-slate-400">Confirm your dining selection and contact details</p>
          </div>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors"
          >
            ✕
          </button>
        </div>

        {errorMessage && (
          <div className="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs">
            {errorMessage}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Order Items Preview with VegBadge */}
          <div className="p-3 rounded-xl bg-slate-800/60 border border-slate-700/60 max-h-28 overflow-y-auto space-y-1.5">
            <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center justify-between">
              <span>Your Selected Dishes ({items.reduce((s, i) => s + i.quantity, 0)})</span>
              <span>Subtotal: ₹{subtotal}</span>
            </div>
            {items.map((it) => (
              <div key={it.id} className="flex items-center justify-between text-xs text-slate-300">
                <div className="flex items-center gap-1.5 truncate mr-2">
                  {it.isVeg !== undefined && <VegBadge isVeg={it.isVeg} size="xs" />}
                  <span className="truncate text-white font-medium">{it.name} <span className="text-slate-400 text-[11px]">×{it.quantity}</span></span>
                </div>
                <span className="font-semibold text-emerald-400 shrink-0 text-xs">₹{it.price * it.quantity}</span>
              </div>
            ))}
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Guest Name *
            </label>
            <input
              type="text"
              required
              placeholder="e.g. Rahul Sharma / Guest Name"
              value={customerName}
              onChange={(e) => setCustomerName(e.target.value)}
              className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">
              Contact Email *
            </label>
            <input
              type="email"
              required
              placeholder="e.g. rahul.sharma@example.com"
              value={customerEmail}
              onChange={(e) => setCustomerEmail(e.target.value)}
              className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Dining / Delivery Option
              </label>
              <select
                value={tableOrDelivery}
                onChange={(e) => setTableOrDelivery(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="Table 4 (Indoor AC)">Table 4 (Indoor AC)</option>
                <option value="Table 8 (Rooftop Garden)">Table 8 (Rooftop)</option>
                <option value="Express Home Delivery">Home Delivery (Address)</option>
                <option value="Takeaway Counter Pickup">Takeaway Pickup</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">
                Payment Method
              </label>
              <select
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                className="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="upi">⚡ UPI (GPay / PhonePe / Paytm)</option>
                <option value="card">💳 Credit / Debit Card</option>
                <option value="cash">💵 Cash on Table / Delivery</option>
              </select>
            </div>
          </div>

          {/* Dining Guarantee Notice */}
          <div className="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs text-slate-300 flex items-center gap-2">
            <span className="text-base">✨</span>
            <span className="text-[11px] text-slate-300">
              Freshly prepared by our master chefs • Instant kitchen dispatch • Contactless payment
            </span>
          </div>

          <div className="pt-2 flex items-center justify-between border-t border-slate-800">
            <div className="text-xs text-slate-400">
              Total to Pay: <span className="text-base font-bold text-emerald-400">₹{totalAmount.toLocaleString()}</span>
            </div>

            <div className="flex gap-2">
              <button
                type="button"
                onClick={onClose}
                className="btn-secondary text-xs"
                disabled={isSubmitting}
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className="btn-primary text-xs"
              >
                {isSubmitting ? 'Placing Order...' : `Confirm & Pay ₹${totalAmount.toLocaleString()}`}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};
