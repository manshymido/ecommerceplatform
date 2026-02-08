/** API response wrappers */
export interface ApiData<T> {
  data: T;
}

export interface ApiPaginated<T> {
  data: T;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

/** Auth */
export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  roles?: { id: number; name: string }[];
}

export interface LoginResponse {
  token: string;
  user: User;
}

/** Catalog */
export interface Product {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  status: string;
  main_image_url: string | null;
  seo_title: string | null;
  seo_description: string | null;
  brand?: Brand;
  categories?: Category[];
  variants?: ProductVariant[];
  created_at: string;
  updated_at: string;
}

export interface ProductVariant {
  id: number;
  sku: string;
  name: string;
  attributes: Record<string, unknown>;
  is_default: boolean;
  product?: Pick<Product, 'id' | 'name' | 'slug'>;
  prices?: ProductPrice[];
  /** Available quantity in stock (only present on product detail response). */
  available_quantity?: number;
}

export interface ProductPrice {
  id: number;
  amount: number;
  currency: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  position: number;
  parent_id?: number | null;
  parent?: Category;
  children?: Category[];
  products?: Product[];
  created_at: string;
  updated_at: string;
}

export interface Brand {
  id: number;
  name: string;
  slug: string;
  products_count?: number;
  created_at: string;
  updated_at: string;
}

/** Cart */
export interface CartItem {
  id: number;
  product_variant_id: number;
  quantity: number;
  unit_price_amount: number;
  unit_price_currency: string;
  discount_amount: number;
  discount_currency: string | null;
  line_total?: number;
  variant_name?: string | null;
  variant_sku?: string | null;
  product_name?: string | null;
  product_slug?: string | null;
  product_image_url?: string | null;
  /** Available quantity in stock (when cart includes availability). */
  available_quantity?: number;
}

export interface AppliedCoupon {
  code: string;
  discount_amount: number;
}

export interface Cart {
  id: number;
  guest_token?: string;
  currency: string;
  status: string;
  items: CartItem[];
  subtotal_amount: number;
  discount_amount: number;
  total_amount: number;
  applied_coupon?: AppliedCoupon;
}

/** Order */
export interface OrderLine {
  id: number;
  product_variant_id: number;
  product_name_snapshot?: string | null;
  sku_snapshot?: string | null;
  quantity: number;
  unit_price_amount: number;
  unit_price_currency: string;
  total_line_amount: number;
}

export interface Address {
  name?: string;
  line1?: string;
  line2?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
}

export interface Order {
  id: number;
  order_number: string;
  user_id?: number;
  guest_email?: string | null;
  user_email?: string | null;
  user_name?: string | null;
  status: string;
  currency: string;
  subtotal_amount: number;
  discount_amount: number;
  tax_amount: number;
  shipping_amount: number;
  total_amount: number;
  lines: OrderLine[];
  billing_address: Address | null;
  shipping_address: Address | null;
  shipping_method_code?: string | null;
  shipping_method_name?: string | null;
  created_at?: string;
  payments?: Payment[];
  shipments?: Shipment[];
}

export interface AdminDashboardStats {
  total_products: number;
  total_orders: number;
  revenue: number;
}

export interface AdminDashboardResponse {
  message: string;
  stats: AdminDashboardStats;
  recent_orders: Order[];
}

/** Checkout payload */
export interface CheckoutPayload {
  email?: string;
  shipping_method_code?: string;
  shipping_method_name?: string;
  shipping_amount?: number;
  tax_amount?: number;
  billing_address?: Address;
  shipping_address?: Address;
  payment_intent_id?: string;
}

/** Review */
export interface Review {
  id: number;
  user_id: number;
  product_id: number;
  rating: number;
  title: string | null;
  body: string | null;
  status: string;
  user_name?: string | null;
  created_at?: string;
}

/** Admin: Warehouse */
export interface Warehouse {
  id: number;
  name: string;
  code: string;
  country_code: string | null;
  region: string | null;
  city: string | null;
  stock_items_count?: number;
  created_at?: string;
  updated_at?: string;
}

/** Admin: Stock */
export interface StockItem {
  id: number;
  product_variant_id: number;
  warehouse_id: number;
  quantity: number;
  safety_stock?: number;
  available?: number;
  product_variant?: ProductVariant;
  warehouse?: Warehouse;
}

/** Admin: Stock grouped by variant (one row per product variant, warehouses breakdown) */
export interface StockByVariant {
  product_variant_id: number;
  product_variant?: ProductVariant;
  total_quantity: number;
  warehouses: {
    warehouse_id: number;
    warehouse_code?: string;
    warehouse_name?: string;
    quantity: number;
    safety_stock: number;
  }[];
}

export interface StockMovement {
  id: number;
  product_variant_id: number;
  warehouse_id: number;
  type: string;
  quantity: number;
  reason_code: string | null;
  reference_type: string | null;
  reference_id: number | null;
  created_at: string;
  product_variant?: ProductVariant;
  warehouse?: Warehouse;
}

/** Admin: Shipment */
export interface Shipment {
  id: number;
  order_id: number;
  tracking_number: string | null;
  carrier_code: string | null;
  status: string;
  shipped_at: string | null;
  delivered_at: string | null;
}

/** Admin: Payment (for refund) */
export interface Payment {
  id: number;
  order_id: number;
  provider: string;
  provider_reference: string | null;
  amount: number;
  currency: string;
  status: string;
}

/** Admin: Refund */
export interface Refund {
  id: number;
  payment_id: number;
  amount: number;
  currency: string;
  status: string;
  reason: string | null;
}

/** Wishlist */
export interface WishlistItem {
  id: number;
  product_variant_id: number;
  product_variant?: Pick<ProductVariant, 'id' | 'name' | 'sku'>;
  product?: Pick<Product, 'id' | 'name' | 'slug' | 'main_image_url'>;
}

export interface Wishlist {
  items: WishlistItem[];
}

/** Shipping */
export interface ShippingQuote {
  code: string;
  name: string;
  amount: number;
  currency: string;
}
