import React from 'react';
import { OrderRecord } from '../api/orderApi';

interface InvoiceModalProps {
  order: OrderRecord;
  onClose: () => void;
}

export const InvoiceModal: React.FC<InvoiceModalProps> = ({ order, onClose }) => {
  const items = Array.isArray(order.items) ? order.items : [];
  const subtotal = items.reduce((acc, it) => acc + (it.price || 0) * (it.quantity || 1), 0) || Math.round(order.total_amount / 1.05);
  const gstTotal = Math.round(subtotal * 0.05);
  const cgst = Math.round(gstTotal / 2);
  const sgst = gstTotal - cgst;
  const grandTotal = Math.round(order.total_amount);

  const formattedDate = order.created_at
    ? new Date(order.created_at).toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
      })
    : new Date().toLocaleString('en-IN');

  const invoiceNumber = `INV-2026-${order.order_id.replace('ORD-', '')}`;

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto animate-fade-in print:p-0 print:bg-white print:fixed print:inset-0">
      <div className="relative w-full max-w-2xl bg-white text-slate-900 rounded-2xl shadow-2xl overflow-hidden my-8 print:my-0 print:shadow-none print:w-full print:max-w-none print:rounded-none">
        {/* Action Header (Hidden during Print) */}
        <div className="flex items-center justify-between px-6 py-3.5 bg-slate-900 text-white print:hidden">
          <div className="flex items-center gap-2">
            <span className="text-emerald-400 text-lg">🧾</span>
            <span className="font-semibold text-sm">Official Restaurant Tax Invoice</span>
            <span className="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">
              Delivered & Paid
            </span>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={handlePrint}
              className="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm"
              title="Print or Save as PDF"
            >
              <span>🖨️</span>
              <span>Print / Save PDF</span>
            </button>
            <button
              onClick={onClose}
              className="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center text-sm font-bold transition-colors"
            >
              ✕
            </button>
          </div>
        </div>

        {/* Printable Invoice Container */}
        <div className="p-8 print:p-6 bg-white text-slate-800 text-sm leading-relaxed" id="printable-invoice">
          {/* Restaurant Header */}
          <div className="border-b-2 border-slate-900 pb-6 mb-6">
            <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-2xl">🍲</span>
                  <h1 className="text-2xl font-black tracking-tight text-slate-900 uppercase">
                    Flavors Global Bistro
                  </h1>
                </div>
                <p className="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                  Authentic Multi-Cuisine Fine Dining & Kitchen
                </p>
                <p className="text-xs text-slate-500 leading-snug">
                  42 MG Road, Gourmet Boulevard, Connaught Place, New Delhi - 110001
                  <br />
                  Phone: +91 98765 43210 • Email: care@flavorsglobalbistro.com
                </p>
              </div>
              <div className="sm:text-right bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span className="inline-block text-[11px] font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded border border-emerald-200 uppercase tracking-wider mb-1">
                  Tax Invoice / Bill of Supply
                </span>
                <p className="font-mono text-xs font-bold text-slate-800">
                  {invoiceNumber}
                </p>
                <p className="text-[11px] text-slate-500">
                  Date: {formattedDate}
                </p>
              </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 pt-3 border-t border-dashed border-slate-200 text-[11px] text-slate-600">
              <div>
                <span className="font-semibold text-slate-700">GSTIN:</span> 07AAAAF1234A1Z5
              </div>
              <div>
                <span className="font-semibold text-slate-700">FSSAI Lic:</span> 10019011006421
              </div>
              <div>
                <span className="font-semibold text-slate-700">SAC Code:</span> 996331 (Restaurant)
              </div>
              <div>
                <span className="font-semibold text-slate-700">Payment:</span> UPI / Card (Paid)
              </div>
            </div>
          </div>

          {/* Customer & Fulfillment Details */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6 text-xs">
            <div>
              <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                Billed To (Customer):
              </p>
              <p className="font-bold text-slate-900 text-sm">{order.customer_name}</p>
              <p className="text-slate-600">{order.customer_email || 'Walk-in Dining Guest'}</p>
            </div>
            <div>
              <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                Order & Fulfillment:
              </p>
              <p className="font-semibold text-slate-900">
                Order ID: <span className="font-mono text-indigo-700 font-bold">{order.order_id}</span>
              </p>
              <p className="text-slate-600">
                Location: <strong className="text-slate-800">{order.table_or_delivery}</strong>
              </p>
              <p className="text-emerald-700 font-semibold flex items-center gap-1 mt-0.5">
                <span>✓ Status:</span> <span>Delivered to Customer</span>
              </p>
            </div>
          </div>

          {/* Itemized Dishes Table */}
          <div className="mb-6 overflow-hidden rounded-lg border border-slate-200">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider font-bold border-b border-slate-200">
                <tr>
                  <th className="py-2.5 px-3 w-8">#</th>
                  <th className="py-2.5 px-3">Item Description</th>
                  <th className="py-2.5 px-3 text-center w-16">Qty</th>
                  <th className="py-2.5 px-3 text-right w-24">Rate (₹)</th>
                  <th className="py-2.5 px-3 text-right w-24">Amount (₹)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {items.length > 0 ? (
                  items.map((it, idx) => (
                    <tr key={idx} className="hover:bg-slate-50/50">
                      <td className="py-2.5 px-3 text-slate-400 font-mono text-[11px]">{idx + 1}</td>
                      <td className="py-2.5 px-3 font-semibold text-slate-900">
                        {it.name || 'Gourmet Dish'}
                      </td>
                      <td className="py-2.5 px-3 text-center font-bold text-slate-700">{it.quantity || 1}</td>
                      <td className="py-2.5 px-3 text-right font-mono text-slate-600">
                        ₹{(it.price || 0).toLocaleString()}
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                        ₹{((it.price || 0) * (it.quantity || 1)).toLocaleString()}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="py-4 text-center text-slate-500 italic">
                      Direct dining billing (₹{subtotal.toLocaleString()})
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Totals & Tax Calculation Breakdown */}
          <div className="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6 pt-2">
            <div className="text-xs text-slate-500 max-w-xs space-y-1">
              <p className="font-semibold text-slate-700">Tax Breakdown Notice:</p>
              <p className="text-[11px]">
                CGST @ 2.5%: ₹{cgst.toLocaleString()}
                <br />
                SGST @ 2.5%: ₹{sgst.toLocaleString()}
                <br />
                Total GST applied: 5.0% on restaurant food services.
              </p>
              <p className="text-[10px] text-slate-400 italic">
                All prices are in Indian National Rupees (INR).
              </p>
            </div>

            <div className="w-full sm:w-64 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2">
              <div className="flex justify-between text-slate-600">
                <span>Subtotal:</span>
                <span className="font-mono font-semibold">₹{subtotal.toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-slate-600">
                <span>CGST (2.5%):</span>
                <span className="font-mono">₹{cgst.toLocaleString()}</span>
              </div>
              <div className="flex justify-between text-slate-600">
                <span>SGST (2.5%):</span>
                <span className="font-mono">₹{sgst.toLocaleString()}</span>
              </div>
              <div className="border-t-2 border-slate-900 pt-2 flex justify-between items-center text-sm">
                <span className="font-black text-slate-900 uppercase">Grand Total:</span>
                <span className="font-black text-slate-900 text-base font-mono">
                  ₹{grandTotal.toLocaleString()}
                </span>
              </div>
            </div>
          </div>

          {/* Footer & Signature */}
          <div className="border-t border-slate-200 pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left text-xs text-slate-500">
            <div>
              <p className="font-semibold text-slate-700">Thank you for dining with Flavors Global Bistro!</p>
              <p className="text-[11px] text-slate-400">
                This is a computer-generated invoice and requires no physical signature.
              </p>
            </div>
            <div className="text-right">
              <div className="inline-block border-b border-slate-400 pb-1 px-4 text-center">
                <span className="font-serif italic font-bold text-slate-700 text-sm">Flavors Bistro</span>
              </div>
              <p className="text-[10px] text-slate-400 uppercase tracking-wider mt-1">Authorized Signatory</p>
            </div>
          </div>
        </div>

        {/* Modal Bottom Close Button (Hidden on Print) */}
        <div className="bg-slate-100 px-6 py-3 border-t border-slate-200 flex justify-end gap-3 print:hidden">
          <button
            onClick={handlePrint}
            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm"
          >
            <span>🖨️ Print / Save as PDF</span>
          </button>
          <button
            onClick={onClose}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
