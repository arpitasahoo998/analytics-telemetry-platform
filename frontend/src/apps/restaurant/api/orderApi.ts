import { UserIdentity } from '@analytics/browser-sdk';

export interface CartItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
  isVeg?: boolean;
}

export interface PlaceOrderPayload {
  customerName: string;
  customerEmail: string;
  tableOrDelivery: string;
  items: CartItem[];
  totalAmount: number;
  currency?: string;
  identity: UserIdentity;
}

export interface OrderRecord {
  id: number;
  order_id: string;
  customer_name: string;
  customer_email?: string;
  table_or_delivery: string;
  total_amount: number;
  currency: string;
  items: CartItem[];
  items_count?: number;
  status: 'placed' | 'preparing' | 'out_for_delivery' | 'delivered' | 'completed';
  created_at: string;
  updated_at?: string;
  server_event_tracked?: boolean;
}

export type OrderConfirmationData = OrderRecord;

const STORAGE_KEY = 'bistro_cached_orders';

export function getCachedOrders(): OrderRecord[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export function saveCachedOrders(orders: OrderRecord[]) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(orders.slice(0, 50)));
  } catch {
    // Ignore quota issues
  }
}

export async function submitOrder(payload: PlaceOrderPayload): Promise<OrderRecord> {
  const anonId = payload.identity?.anonymousId || `anon_${Math.random().toString(36).substring(2, 10)}`;
  const sessId = payload.identity?.sessionId || `sess_${Math.random().toString(36).substring(2, 10)}`;
  const userId = payload.identity?.userId || null;

  const executePost = async (): Promise<OrderRecord> => {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 25000);

    try {
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Anonymous-Id': anonId,
          'X-Session-Id': sessId,
        },
        signal: controller.signal,
        body: JSON.stringify({
          customer_name: payload.customerName,
          customer_email: payload.customerEmail,
          table_or_delivery: payload.tableOrDelivery,
          items: payload.items,
          total_amount: payload.totalAmount,
          currency: payload.currency || 'INR',
          anonymous_id: anonId,
          session_id: sessId,
          user_id: userId,
        }),
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Failed to submit order (HTTP ${response.status})`);
      }

      const result = await response.json();
      const order: OrderRecord = result.data;
      
      // Update local storage cache immediately
      const existing = getCachedOrders();
      saveCachedOrders([order, ...existing.filter((o) => o.order_id !== order.order_id)]);
      
      return order;
    } catch (err) {
      clearTimeout(timeoutId);
      throw err;
    }
  };

  try {
    return await executePost();
  } catch (err) {
    if (err instanceof Error && (err.name === 'AbortError' || err.message.includes('fetch'))) {
      try {
        return await executePost();
      } catch (retryErr) {
        if (retryErr instanceof Error && retryErr.name === 'AbortError') {
          throw new Error('Order submission timed out. Please check server connection and retry.');
        }
        throw retryErr;
      }
    }
    throw err;
  }
}

export async function fetchOrders(email?: string, anonId?: string): Promise<OrderRecord[]> {
  const cached = getCachedOrders();
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 6000);

  try {
    const params = new URLSearchParams();
    if (email) params.append('email', email);
    if (anonId) params.append('anonymous_id', anonId);

    const url = `/api/orders${params.toString() ? '?' + params.toString() : ''}`;
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
      signal: controller.signal,
    });
    clearTimeout(timeoutId);

    if (!response.ok) {
      if (cached.length > 0) return cached;
      throw new Error(`Failed to fetch orders (HTTP ${response.status})`);
    }

    const json = await response.json();
    const serverOrders: OrderRecord[] = json.data || [];
    saveCachedOrders(serverOrders);
    return serverOrders;
  } catch (err) {
    clearTimeout(timeoutId);
    if (cached.length > 0) {
      return cached;
    }
    throw err;
  }
}

export async function updateOrderStatus(
  orderId: string,
  status: 'placed' | 'preparing' | 'out_for_delivery' | 'delivered'
): Promise<OrderRecord> {
  // Update local cache optimistically
  const cached = getCachedOrders();
  const updatedCache = cached.map((o) => (o.order_id === orderId ? { ...o, status } : o));
  saveCachedOrders(updatedCache);

  try {
    const response = await fetch(`/api/orders/${orderId}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status }),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `Failed to update order status (HTTP ${response.status})`);
    }

    const json = await response.json();
    const updated: OrderRecord = json.data;
    saveCachedOrders(cached.map((o) => (o.order_id === orderId ? updated : o)));
    return updated;
  } catch (err) {
    // If network drops, return optimistic object
    const match = cached.find((o) => o.order_id === orderId);
    if (match) {
      return { ...match, status };
    }
    throw err;
  }
}
