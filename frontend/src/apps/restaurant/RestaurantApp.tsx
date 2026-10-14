import React, { useState, useEffect } from 'react';
import { MenuItem } from './data/menuData';
import { fetchDishes, getCachedDishes } from './api/dishApi';
import { Header } from './components/Header';
import { MenuSection } from './components/MenuSection';
import { CartDrawer } from './components/CartDrawer';
import { CheckoutModal } from './components/CheckoutModal';
import { OrderConfirmation } from './components/OrderConfirmation';
import { DishDetailModal } from './components/DishDetailModal';
import { OrderHistoryModal } from './components/OrderHistoryModal';
import { InvoiceModal } from './components/InvoiceModal';
import { CartItem, OrderRecord, fetchOrders, getCachedOrders } from './api/orderApi';
import { AnalyticsBrowserClient } from '@analytics/browser-sdk';

interface RestaurantAppProps {
  analytics: AnalyticsBrowserClient;
  onSwitchToDashboard: () => void;
}

export const RestaurantApp: React.FC<RestaurantAppProps> = ({
  analytics,
  onSwitchToDashboard,
}) => {
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [dishes, setDishes] = useState<MenuItem[]>(() => getCachedDishes());
  const [isLoadingDishes, setIsLoadingDishes] = useState<boolean>(() => getCachedDishes().length === 0);
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [isOrderHistoryOpen, setIsOrderHistoryOpen] = useState(false);
  const [orders, setOrders] = useState<OrderRecord[]>(() => getCachedOrders());
  const [confirmedOrder, setConfirmedOrder] = useState<OrderRecord | null>(null);
  const [invoiceOrder, setInvoiceOrder] = useState<OrderRecord | null>(null);
  const [selectedDish, setSelectedDish] = useState<MenuItem | null>(null);
  const [ordersCount, setOrdersCount] = useState<number>(() => getCachedOrders().length);

  // Fetch dynamic dishes from MySQL backend on mount
  useEffect(() => {
    fetchDishes()
      .then((data) => {
        if (data && data.length > 0) {
          setDishes(data);
        }
      })
      .catch((err) => {
        console.error('Failed to load dynamic dishes from API:', err);
      })
      .finally(() => {
        setIsLoadingDishes(false);
      });
  }, []);

  // Fetch initial orders from MySQL backend
  useEffect(() => {
    fetchOrders()
      .then((data) => {
        if (data && data.length > 0) {
          setOrders(data);
          setOrdersCount(data.length);
        }
      })
      .catch(() => {});
  }, []);

  // Track pageview on mount and category switch
  useEffect(() => {
    analytics.page('Global Bistro - Menu', {
      path: '/menu',
      category: selectedCategory,
    });
  }, [analytics, selectedCategory]);

  const filteredDishes = selectedCategory === 'all'
    ? dishes
    : dishes.filter((item) => item.category === selectedCategory);

  const handleSelectItem = (item: MenuItem) => {
    setSelectedDish(item);
    analytics.track('view_item', {
      item_id: item.id,
      name: item.name,
      price: item.price,
      currency: 'INR',
      category: item.category,
    });
  };

  const handleAddToCart = (item: MenuItem, quantity = 1) => {
    setCartItems((prev) => {
      const existing = prev.find((i) => i.id === item.id);
      if (existing) {
        return prev.map((i) =>
          i.id === item.id ? { ...i, quantity: i.quantity + quantity } : i
        );
      }
      return [...prev, { id: item.id, name: item.name, price: item.price, quantity, isVeg: item.isVeg }];
    });

    analytics.track('add_to_cart', {
      item_id: item.id,
      name: item.name,
      price: item.price,
      currency: 'INR',
      quantity,
      category: item.category,
    });
  };

  const handleUpdateQuantity = (id: string, delta: number) => {
    setCartItems((prev) =>
      prev
        .map((item) => {
          if (item.id === id) {
            const newQty = item.quantity + delta;
            return newQty > 0 ? { ...item, quantity: newQty } : null;
          }
          return item;
        })
        .filter(Boolean) as CartItem[]
    );
  };

  const handleRemoveItem = (id: string) => {
    const item = cartItems.find((i) => i.id === id);
    if (item) {
      analytics.track('remove_from_cart', {
        item_id: item.id,
        name: item.name,
        price: item.price,
        currency: 'INR',
      });
    }
    setCartItems((prev) => prev.filter((i) => i.id !== id));
  };

  const handleProceedToCheckout = () => {
    const subtotal = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const totalAmount = Math.round(subtotal * 1.05);

    analytics.track('checkout_started', {
      total_amount: totalAmount,
      currency: 'INR',
      items_count: cartItems.length,
      items: cartItems.map((i) => ({ id: i.id, name: i.name, qty: i.quantity })),
    });

    setIsCartOpen(false);
    setIsCheckoutOpen(true);
  };

  const handleOrderSuccess = (order: OrderRecord) => {
    setIsCheckoutOpen(false);
    setCartItems([]);
    setConfirmedOrder(order);
    setOrders((prev) => [order, ...prev.filter((o) => o.order_id !== order.order_id)]);
    setOrdersCount((prev) => prev + 1);
  };

  const totalCartCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);
  const totalCartValue = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);

  return (
    <div className="min-h-screen bg-slate-950 flex flex-col">
      <Header
        activeCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        cartCount={totalCartCount}
        cartTotal={totalCartValue}
        onOpenCart={() => setIsCartOpen(true)}
        onSwitchToDashboard={onSwitchToDashboard}
        onOpenOrderHistory={() => setIsOrderHistoryOpen(true)}
        ordersCount={ordersCount}
      />

      <main className="container-custom py-6 md:py-8 flex-1">
        {confirmedOrder ? (
          <OrderConfirmation
            order={confirmedOrder}
            onContinueShopping={() => setConfirmedOrder(null)}
            onOpenDashboard={onSwitchToDashboard}
            onOpenHistory={() => setIsOrderHistoryOpen(true)}
            onOpenInvoice={(order) => setInvoiceOrder(order)}
          />
        ) : (
          <>
            {/* Hero / Banner */}
            <div className="card p-6 md:p-8 mb-8 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border-indigo-500/20 relative overflow-hidden">
              <div className="max-w-3xl relative z-10">
                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-semibold mb-3">
                  <span>✨ Four World Cuisines Under One Roof</span>
                </div>
                <h2 className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-3">
                  Indian • Mexican • Italian • Chinese
                </h2>
                <p className="text-slate-300 text-xs sm:text-sm leading-relaxed mb-0">
                  Experience a curated journey of global flavors prepared fresh with authentic ingredients. From aromatic Delhi curries and sizzling Jalisco tacos to artisanal Roman pizzas and fragrant wok noodles.
                </p>
              </div>
            </div>

            {/* Menu Grid */}
            {isLoadingDishes && dishes.length === 0 ? (
              <div className="py-20 text-center">
                <div className="w-10 h-10 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p className="text-slate-400 text-sm">Loading dynamic multi-cuisine menu from database...</p>
              </div>
            ) : (
              <MenuSection
                items={filteredDishes}
                onSelectItem={handleSelectItem}
                onAddToCart={handleAddToCart}
              />
            )}
          </>
        )}
      </main>

      {/* Cart Drawer & Checkout Modal */}
      <CartDrawer
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        items={cartItems}
        onUpdateQuantity={handleUpdateQuantity}
        onRemoveItem={handleRemoveItem}
        onProceedToCheckout={handleProceedToCheckout}
      />

      <CheckoutModal
        isOpen={isCheckoutOpen}
        onClose={() => setIsCheckoutOpen(false)}
        items={cartItems}
        identity={analytics.getIdentity()}
        onOrderSuccess={handleOrderSuccess}
      />

      <DishDetailModal
        item={selectedDish}
        onClose={() => setSelectedDish(null)}
        onAddToCart={(item, qty) => handleAddToCart(item, qty)}
      />

      {/* Order History Modal */}
      {isOrderHistoryOpen && (
        <OrderHistoryModal
          initialOrders={orders}
          onClose={() => setIsOrderHistoryOpen(false)}
          onOpenInvoice={(order) => setInvoiceOrder(order)}
        />
      )}

      {/* Tax Invoice Modal */}
      {invoiceOrder && (
        <InvoiceModal
          order={invoiceOrder}
          onClose={() => setInvoiceOrder(null)}
        />
      )}
    </div>
  );
};
