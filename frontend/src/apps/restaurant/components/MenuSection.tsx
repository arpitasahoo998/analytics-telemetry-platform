import React from 'react';
import { MenuItem } from '../data/menuData';
import { VegBadge } from './VegBadge';

interface MenuSectionProps {
  items: MenuItem[];
  onSelectItem: (item: MenuItem) => void;
  onAddToCart: (item: MenuItem) => void;
}

export const MenuSection: React.FC<MenuSectionProps> = ({
  items,
  onSelectItem,
  onAddToCart,
}) => {
  const getCuisineBadge = (cat: string) => {
    switch (cat) {
      case 'indian':
        return { label: 'Indian', icon: '🍲', color: 'bg-amber-500/15 text-amber-300 border-amber-500/30' };
      case 'mexican':
        return { label: 'Mexican', icon: '🌮', color: 'bg-rose-500/15 text-rose-300 border-rose-500/30' };
      case 'italian':
        return { label: 'Italian', icon: '🍕', color: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' };
      case 'chinese':
        return { label: 'Chinese', icon: '🥢', color: 'bg-red-500/15 text-red-300 border-red-500/30' };
      default:
        return { label: cat, icon: '🍽️', color: 'bg-slate-500/15 text-slate-300 border-slate-500/30' };
    }
  };

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {items.map((dish) => {
        const cuisine = getCuisineBadge(dish.category);

        return (
          <div
            key={dish.id}
            onClick={() => onSelectItem(dish)}
            className="card group overflow-hidden flex flex-col justify-between hover:border-indigo-500/50 hover:shadow-indigo-500/10 hover:shadow-xl transition-all duration-300 cursor-pointer bg-slate-900/90"
          >
            {/* Dish Image Container */}
            <div className="relative h-52 w-full bg-slate-950 overflow-hidden">
              <img
                src={dish.image}
                alt={dish.name}
                loading="lazy"
                onError={(e) => {
                  // Fallback safe placeholder
                  (e.target as HTMLImageElement).src =
                    'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&auto=format&fit=crop&q=80';
                }}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />

              {/* Gradient Overlay for legibility */}
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent pointer-events-none" />

              {/* Top Badges: Cuisine & Special Award */}
              <div className="absolute top-2.5 left-2.5 flex items-center gap-1.5 flex-wrap">
                <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full border backdrop-blur-md shadow-xs ${cuisine.color}`}>
                  <span className="mr-1">{cuisine.icon}</span>
                  {cuisine.label}
                </span>
                {dish.badge && (
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-950/85 text-amber-300 border border-amber-500/40 backdrop-blur-md shadow-xs">
                    ★ {dish.badge}
                  </span>
                )}
              </div>

              {/* Authentic FSSAI Veg / Non-Veg Indicator Badge */}
              <div className="absolute top-2.5 right-2.5">
                <VegBadge isVeg={dish.isVeg} variant="card-badge" showLabel size="sm" />
              </div>

              {/* Spice & Portion Tag at bottom of photo */}
              <div className="absolute bottom-2 left-3 right-3 flex items-center justify-between text-[11px] text-slate-300">
                {dish.portionSize && (
                  <span className="bg-slate-950/75 px-2 py-0.5 rounded text-[10px] font-medium backdrop-blur-xs text-slate-300 border border-slate-800/80">
                    {dish.portionSize}
                  </span>
                )}
                {dish.isDessert ? (
                  <span className="bg-slate-950/75 px-1.5 py-0.5 rounded text-[10px] font-medium backdrop-blur-xs text-pink-300 border border-slate-800/80 flex items-center gap-0.5 ml-auto">
                    🍯 Sweet Treat
                  </span>
                ) : dish.spiceLevel ? (
                  <span className="bg-slate-950/75 px-1.5 py-0.5 rounded text-[10px] font-medium backdrop-blur-xs text-amber-300 border border-slate-800/80 flex items-center gap-0.5 ml-auto">
                    🌶️ {dish.spiceLevel}
                  </span>
                ) : null}
              </div>
            </div>

            {/* Dish Details */}
            <div className="p-4 flex-1 flex flex-col justify-between">
              <div>
                <div className="flex items-start justify-between gap-2">
                  <h3 className="font-bold text-white text-base group-hover:text-indigo-400 transition-colors line-clamp-1">
                    {dish.name}
                  </h3>
                </div>
                <p className="text-xs text-slate-400 line-clamp-2 mt-1 leading-relaxed">
                  {dish.description}
                </p>
              </div>

              {/* Price & Add to Cart */}
              <div className="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-slate-500 font-medium block">Price</span>
                  <span className="text-lg font-extrabold text-emerald-400">
                    ₹{dish.price}
                  </span>
                </div>

                <button
                  type="button"
                  onClick={(e) => {
                    e.stopPropagation();
                    onAddToCart(dish);
                  }}
                  className="btn-primary text-xs py-1.5 px-3 rounded-lg shadow-sm hover:scale-102 transition-transform"
                >
                  + Add to Order
                </button>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
};
