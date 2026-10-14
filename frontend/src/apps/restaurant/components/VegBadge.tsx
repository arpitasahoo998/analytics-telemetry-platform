import React from 'react';

export interface VegBadgeProps {
  isVeg: boolean;
  size?: 'xs' | 'sm' | 'md' | 'lg';
  showLabel?: boolean;
  label?: string;
  variant?: 'minimal' | 'pill' | 'card-badge';
  className?: string;
}

/**
 * Authentic Veg / Non-Veg Indicator
 * Conforms to FSSAI (Food Safety and Standards Authority of India) dining iconography:
 * - Green border square with centered solid green dot (Vegetarian)
 * - Brown/Red border square with centered solid red dot/symbol (Non-Vegetarian)
 */
export const VegBadge: React.FC<VegBadgeProps> = ({
  isVeg,
  size = 'sm',
  showLabel = false,
  label,
  variant = 'minimal',
  className = '',
}) => {
  const sizeMap = {
    xs: { box: 'w-3.5 h-3.5', dot: 'w-1.5 h-1.5', text: 'text-[9px]' },
    sm: { box: 'w-4 h-4', dot: 'w-2 h-2', text: 'text-[10px]' },
    md: { box: 'w-5 h-5', dot: 'w-2.5 h-2.5', text: 'text-xs' },
    lg: { box: 'w-6 h-6', dot: 'w-3.5 h-3.5', text: 'text-sm' },
  };

  const currentSize = sizeMap[size];
  const displayLabel = label || (isVeg ? 'VEG' : 'NON-VEG');

  // Minimal Square with Dot
  const symbolElement = (
    <div
      className={`${currentSize.box} rounded-[3px] border-[1.5px] flex items-center justify-center shrink-0 bg-slate-900/90 shadow-sm transition-transform duration-200 ${
        isVeg
          ? 'border-emerald-500'
          : 'border-rose-500'
      }`}
      aria-label={isVeg ? 'Vegetarian dish' : 'Non-vegetarian dish'}
    >
      <span
        className={`${currentSize.dot} rounded-full shrink-0 ${
          isVeg ? 'bg-emerald-500 shadow-emerald-500/50 shadow-xs' : 'bg-rose-500 shadow-rose-500/50 shadow-xs'
        }`}
      />
    </div>
  );

  if (variant === 'pill') {
    return (
      <span
        className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md backdrop-blur-md border font-bold ${
          isVeg
            ? 'bg-emerald-950/80 text-emerald-400 border-emerald-500/40'
            : 'bg-rose-950/80 text-rose-400 border-rose-500/40'
        } ${currentSize.text} ${className}`}
        title={isVeg ? 'Pure Vegetarian (Green Dot)' : 'Non-Vegetarian (Red Dot)'}
      >
        {symbolElement}
        <span className="tracking-wider uppercase">{displayLabel}</span>
      </span>
    );
  }

  if (variant === 'card-badge') {
    return (
      <div
        className={`inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-950/90 backdrop-blur-md border ${
          isVeg ? 'border-emerald-500/40 text-emerald-400' : 'border-rose-500/40 text-rose-400'
        } ${currentSize.text} ${className}`}
        title={isVeg ? '100% Pure Vegetarian' : 'Non-Vegetarian Item'}
      >
        {symbolElement}
        {showLabel && <span className="font-extrabold tracking-wider">{displayLabel}</span>}
      </div>
    );
  }

  return (
    <span
      className={`inline-flex items-center gap-1.5 select-none ${className}`}
      title={isVeg ? 'Vegetarian (100% Pure Veg)' : 'Non-Vegetarian'}
    >
      {symbolElement}
      {showLabel && (
        <span
          className={`font-black tracking-wider uppercase ${currentSize.text} ${
            isVeg ? 'text-emerald-400' : 'text-rose-400'
          }`}
        >
          {displayLabel}
        </span>
      )}
    </span>
  );
};
