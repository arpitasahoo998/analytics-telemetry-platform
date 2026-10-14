import React, { useState } from 'react';
import { MenuItem } from '../data/menuData';
import { VegBadge } from './VegBadge';

interface DishDetailModalProps {
  item: MenuItem | null;
  onClose: () => void;
  onAddToCart: (item: MenuItem, quantity: number) => void;
}

export const DishDetailModal: React.FC<DishDetailModalProps> = ({
  item,
  onClose,
  onAddToCart,
}) => {
  const [quantity, setQuantity] = useState(1);

  if (!item) return null;

  const getCuisineMeta = (cat: string) => {
    switch (cat) {
      case 'indian':
        return { label: 'Indian Cuisine', icon: '🍲', color: 'border-amber-500/40 bg-amber-500/15 text-amber-300' };
      case 'mexican':
        return { label: 'Mexican Cuisine', icon: '🌮', color: 'border-rose-500/40 bg-rose-500/15 text-rose-300' };
      case 'italian':
        return { label: 'Italian Cuisine', icon: '🍕', color: 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' };
      case 'chinese':
        return { label: 'Chinese Cuisine', icon: '🥢', color: 'border-red-500/40 bg-red-500/15 text-red-300' };
      default:
        return { label: cat, icon: '🍽️', color: 'border-slate-500/40 bg-slate-500/15 text-slate-300' };
    }
  };

  const meta = getCuisineMeta(item.category);

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in"
      onClick={onClose}
    >
      <div
        className="card max-w-xl w-full overflow-hidden shadow-2xl border-slate-700 bg-slate-900 animate-slide-up max-h-[92vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Large Food Image Container */}
        <div className="relative h-64 sm:h-72 w-full bg-slate-950 overflow-hidden shrink-0">
          <img
            src={item.image}
            alt={item.name}
            className="w-full h-full object-cover"
            onError={(e) => {
              (e.target as HTMLImageElement).src =
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&auto=format&fit=crop&q=80';
            }}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/30 to-black/50" />

          {/* Close button */}
          <button
            type="button"
            onClick={onClose}
            className="absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center border border-slate-700 backdrop-blur-md transition-colors shadow-md z-10"
            aria-label="Close dialog"
          >
            ✕
          </button>

          {/* Badges on image */}
          <div className="absolute bottom-3 left-4 right-4 flex flex-wrap items-center justify-between gap-2">
            <div className="flex flex-wrap items-center gap-2">
              <span className={`text-xs font-bold px-2.5 py-1 rounded-full border backdrop-blur-md shadow-xs ${meta.color}`}>
                <span className="mr-1">{meta.icon}</span> {meta.label}
              </span>
              <VegBadge
                isVeg={item.isVeg}
                variant="pill"
                size="md"
                label={item.isVeg ? '100% PURE VEG' : 'NON-VEGETARIAN'}
              />
            </div>

            {item.badge && (
              <span className="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-950/90 text-amber-300 border border-amber-500/40 backdrop-blur-md shadow-xs">
                ★ {item.badge}
              </span>
            )}
          </div>
        </div>

        {/* Scrollable Content Body */}
        <div className="p-5 sm:p-6 overflow-y-auto space-y-4 flex-1">
          {/* Header Title & Pricing */}
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                {item.name}
              </h2>
              <div className="flex items-center gap-2 mt-1">
                <VegBadge isVeg={item.isVeg} size="sm" />
                <span className={`text-xs font-bold ${item.isVeg ? 'text-emerald-400' : 'text-rose-400'}`}>
                  {item.isVeg ? 'Vegetarian preparation' : 'Contains farm meat / poultry'}
                </span>
              </div>
            </div>
            <div className="text-right shrink-0">
              <span className="text-2xl sm:text-3xl font-black text-emerald-400">₹{item.price}</span>
              <span className="block text-[11px] text-slate-400">Inclusive of all taxes</span>
            </div>
          </div>

          {/* Description */}
          <p className="text-sm text-slate-300 leading-relaxed">
            {item.description}
          </p>

          {/* Quick Details Grid (Portion, Prep Time, Calories, Spice) */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2">
            {item.portionSize && (
              <div className="p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/60 text-center">
                <span className="text-[10px] text-slate-400 font-medium uppercase tracking-wider block">Portion</span>
                <span className="text-xs font-bold text-white mt-0.5 block truncate" title={item.portionSize}>
                  🥣 {item.portionSize}
                </span>
              </div>
            )}
            {item.prepTime && (
              <div className="p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/60 text-center">
                <span className="text-[10px] text-slate-400 font-medium uppercase tracking-wider block">Prep Time</span>
                <span className="text-xs font-bold text-white mt-0.5 block">
                  ⏱️ {item.prepTime}
                </span>
              </div>
            )}
            {item.calories && (
              <div className="p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/60 text-center">
                <span className="text-[10px] text-slate-400 font-medium uppercase tracking-wider block">Energy</span>
                <span className="text-xs font-bold text-white mt-0.5 block">
                  🔥 {item.calories}
                </span>
              </div>
            )}
            {item.isDessert ? (
              <div className="p-2.5 rounded-xl bg-pink-500/10 border border-pink-500/30 text-center">
                <span className="text-[10px] text-pink-300 font-medium uppercase tracking-wider block">Flavor</span>
                <span className="text-xs font-bold text-pink-300 mt-0.5 block truncate" title={item.sweetness || 'Sweet Treat'}>
                  🍯 {item.sweetness || 'Sweet Treat'}
                </span>
              </div>
            ) : item.spiceLevel ? (
              <div className="p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/60 text-center">
                <span className="text-[10px] text-slate-400 font-medium uppercase tracking-wider block">Spice Level</span>
                <span className="text-xs font-bold text-amber-400 mt-0.5 block">
                  🌶️ {item.spiceLevel}
                </span>
              </div>
            ) : null}
          </div>

          {/* Chef's Key Ingredients */}
          {item.ingredients && item.ingredients.length > 0 && (
            <div className="pt-2">
              <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                Ingredients & Highlights
              </h3>
              <div className="flex flex-wrap gap-1.5">
                {item.ingredients.map((ing, idx) => (
                  <span
                    key={idx}
                    className="text-xs px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-200 font-medium"
                  >
                    • {ing}
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* Allergen Advisory */}
          {item.allergens && item.allergens.length > 0 && (
            <div className="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-start gap-2.5">
              <span className="text-sm">⚠️</span>
              <div className="text-xs">
                <span className="font-bold text-amber-300">Allergen Notice: </span>
                <span className="text-slate-300">Contains {item.allergens.join(', ')}. Please advise our kitchen team for customizations.</span>
              </div>
            </div>
          )}
        </div>

        {/* Action Row Fixed at Bottom */}
        <div className="p-4 sm:p-5 border-t border-slate-800 bg-slate-900/95 flex items-center justify-between gap-4 shrink-0">
          {/* Quantity Selector */}
          <div className="flex items-center gap-3 bg-slate-800/90 border border-slate-700 rounded-xl p-1">
            <button
              type="button"
              onClick={() => setQuantity((q) => Math.max(1, q - 1))}
              className="w-8 h-8 rounded-lg bg-slate-700/70 hover:bg-slate-700 text-white font-bold flex items-center justify-center transition-colors"
              aria-label="Decrease quantity"
            >
              -
            </button>
            <span className="w-6 text-center font-bold text-white text-sm">{quantity}</span>
            <button
              type="button"
              onClick={() => setQuantity((q) => q + 1)}
              className="w-8 h-8 rounded-lg bg-slate-700/70 hover:bg-slate-700 text-white font-bold flex items-center justify-center transition-colors"
              aria-label="Increase quantity"
            >
              +
            </button>
          </div>

          {/* Add Button */}
          <button
            type="button"
            onClick={() => {
              onAddToCart(item, quantity);
              onClose();
            }}
            className="btn-primary flex-1 py-3 text-sm font-bold flex items-center justify-center gap-2 rounded-xl shadow-lg shadow-indigo-600/20 hover:scale-101 transition-transform"
          >
            <span>Add to Order Bag</span>
            <span className="bg-indigo-700/70 px-2.5 py-0.5 rounded-lg text-xs font-black">
              ₹{item.price * quantity}
            </span>
          </button>
        </div>
      </div>
    </div>
  );
};
