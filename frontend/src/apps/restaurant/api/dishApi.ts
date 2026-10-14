import { MenuItem } from '../data/menuData';

const DISHES_CACHE_KEY = 'bistro_cached_dishes';

export function getCachedDishes(): MenuItem[] {
  try {
    const raw = localStorage.getItem(DISHES_CACHE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export function saveCachedDishes(dishes: MenuItem[]): void {
  try {
    localStorage.setItem(DISHES_CACHE_KEY, JSON.stringify(dishes));
  } catch {
    // Ignore storage quota limits
  }
}

export async function fetchDishes(category?: string): Promise<MenuItem[]> {
  const cached = getCachedDishes();
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 8000);

  try {
    const query = category && category !== 'all' ? `?category=${encodeURIComponent(category)}` : '';
    const response = await fetch(`/api/dishes${query}`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
    clearTimeout(timeoutId);

    if (!response.ok) {
      if (cached.length > 0) return cached;
      throw new Error(`Failed to load menu dishes (HTTP ${response.status})`);
    }

    const result = await response.json();
    const dishes: MenuItem[] = result.data || [];
    
    // Save to cache when fetching the full list
    if (!category || category === 'all') {
      saveCachedDishes(dishes);
    }

    return dishes;
  } catch (err) {
    clearTimeout(timeoutId);
    if (cached.length > 0) {
      return category && category !== 'all' 
        ? cached.filter((d) => d.category === category)
        : cached;
    }
    throw err;
  }
}

export async function fetchDishById(dishId: string): Promise<MenuItem | null> {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 6000);

  try {
    const response = await fetch(`/api/dishes/${encodeURIComponent(dishId)}`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
    clearTimeout(timeoutId);

    if (!response.ok) {
      const cached = getCachedDishes();
      return cached.find((d) => d.id === dishId) || null;
    }

    const result = await response.json();
    return result.data || null;
  } catch {
    clearTimeout(timeoutId);
    const cached = getCachedDishes();
    return cached.find((d) => d.id === dishId) || null;
  }
}
