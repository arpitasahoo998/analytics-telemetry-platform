import React from 'react';

interface HeaderProps {
  activeCategory: string;
  onSelectCategory: (category: string) => void;
  cartCount: number;
  cartTotal: number;
  onOpenCart: () => void;
  onSwitchToDashboard: () => void;
  onOpenOrderHistory: () => void;
  ordersCount?: number;
}

export const Header: React.FC<HeaderProps> = ({
  activeCategory,
  onSelectCategory,
  cartCount,
  cartTotal,
  onOpenCart,
  onSwitchToDashboard,
  onOpenOrderHistory,
  ordersCount = 0,
}) => {
  const categories = [
    { id: 'all', label: 'All Cuisines', icon: '🌍' },
    { id: 'indian', label: 'Indian Delights', icon: '🍲' },
    { id: 'mexican', label: 'Mexican Fiesta', icon: '🌮' },
    { id: 'italian', label: 'Italian Classics', icon: '🍕' },
    { id: 'chinese', label: 'Chinese Wok', icon: '🥢' },
  ];

  return (
    <header className="border-b border-slate-800 bg-slate-900/95 backdrop-blur-md sticky top-0 z-30 shadow-md">
      <div className="container-custom py-3 flex flex-col md:flex-row items-center justify-between gap-3">
        {/* Brand identity */}
        <div className="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start">
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-indigo-600 flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-500/20 text-white">
              🍲
            </div>
            <div>
              <h1 className="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                Flavors Global Bistro
                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                  Gourmet Kitchen
                </span>
              </h1>
              <p className="text-[11px] text-slate-400">
                Indian • Mexican • Italian • Chinese • Dining & Fast Delivery
              </p>
            </div>
          </div>

          {/* Mobile Action Buttons */}
          <div className="flex md:hidden items-center gap-2">
            <button
              onClick={onOpenOrderHistory}
              className="btn-secondary text-xs py-1.5 px-2.5 rounded-lg border-slate-700"
              title="Order History & Invoices"
            >
              📜 {ordersCount > 0 && `(${ordersCount})`}
            </button>
            <button
              onClick={onOpenCart}
              className="btn-primary text-xs py-1.5 px-3 rounded-lg"
            >
              🛒 {cartCount > 0 && `(${cartCount})`}
            </button>
          </div>
        </div>

        {/* Cuisine Category Filter Bar */}
        <div className="flex items-center gap-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800 overflow-x-auto max-w-full w-full md:w-auto scrollbar-none">
          {categories.map((c) => (
            <button
              key={c.id}
              onClick={() => onSelectCategory(c.id)}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all whitespace-nowrap flex items-center gap-1.5 ${
                activeCategory === c.id
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
              }`}
            >
              <span>{c.icon}</span>
              <span>{c.label}</span>
            </button>
          ))}
        </div>

        {/* Action Buttons (Desktop) */}
        <div className="hidden md:flex items-center gap-2.5">
          <button
            onClick={onOpenOrderHistory}
            className="btn-secondary text-xs py-2 px-3 rounded-xl border-slate-700 hover:border-slate-600 relative flex items-center gap-1.5"
            title="View Order History & Download Tax Invoices"
          >
            <span>📜</span>
            <span>Orders & Invoices</span>
            {ordersCount > 0 && (
              <span className="bg-indigo-600/80 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full">
                {ordersCount}
              </span>
            )}
          </button>

          <button
            onClick={onOpenCart}
            className="btn-primary text-xs py-2 px-3.5 rounded-xl relative shadow-md"
            title="View Order Bag"
          >
            <span>🛒 Cart</span>
            {cartCount > 0 && (
              <span className="bg-rose-500 text-white text-[11px] font-bold px-2 py-0.5 rounded-full ml-1 animate-fade-in">
                {cartCount} • ₹{cartTotal}
              </span>
            )}
          </button>

          <button
            onClick={onSwitchToDashboard}
            className="btn-secondary text-xs py-2 px-3.5 rounded-xl border-slate-700 hover:border-indigo-500/50"
            title="Open Live Analytics Dashboard"
          >
            📊 Dashboard
          </button>
        </div>
      </div>
    </header>
  );
};
