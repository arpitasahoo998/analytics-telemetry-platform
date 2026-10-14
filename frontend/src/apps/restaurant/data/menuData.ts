export interface MenuItem {
  id: string;
  db_id?: number;
  name: string;
  category: 'indian' | 'mexican' | 'italian' | 'chinese';
  description: string;
  price: number; // in INR (₹)
  image: string;
  badge?: string;
  spiceLevel?: 'Mild' | 'Medium' | 'Spicy';
  isDessert?: boolean;
  sweetness?: string;
  isVeg: boolean;
  portionSize?: string;
  prepTime?: string;
  calories?: string;
  ingredients?: string[];
  allergens?: string[];
  isAvailable?: boolean;
}

/**
 * @deprecated All menu items are now dynamically loaded from MySQL via GET /api/dishes.
 * Static mock data is completely removed in accordance with production dynamic data requirements.
 */
export const MENU_ITEMS: MenuItem[] = [];
