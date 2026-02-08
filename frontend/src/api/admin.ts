import { apiClient, createCrud, getData, getPaginated, postData, putData, patchData, deleteNoContent } from './client';
import type {
  Product,
  Order,
  AdminDashboardResponse,
  Category,
  Brand,
  Warehouse,
  StockItem,
  StockByVariant,
  StockMovement,
  Shipment,
  Payment,
  Refund,
  Review,
} from './types';

const productsBase = '/admin/products';
const ordersBase = '/admin/orders';

export const adminApi = {
  dashboard: () => apiClient.get<AdminDashboardResponse>('/admin/dashboard').then((r) => r.data),

  products: {
    list: (params?: {
      page?: number;
      per_page?: number;
      status?: string;
      brand_id?: number;
      search?: string;
      sort?: string;
      direction?: string;
    }) => getPaginated<Product[]>(productsBase, params),
    get: (id: number) => getData<Product>(`${productsBase}/${id}`),
    create: (data: Partial<Product> | Record<string, unknown>) => postData<Product>(productsBase, data),
    update: (id: number, data: Partial<Product> | Record<string, unknown>) =>
      putData<Product>(`${productsBase}/${id}`, data),
    delete: (id: number) => deleteNoContent(`${productsBase}/${id}`),
  },

  productVariantPrice: {
    update: (variantId: number, data: { currency?: string; amount: number; compare_at_amount?: number }) =>
      patchData<{ id: number; currency: string; amount: number }>(`/admin/product-variants/${variantId}/price`, data),
  },

  categories: createCrud<Category>('/admin/categories'),

  brands: createCrud<Brand>('/admin/brands'),

  warehouses: createCrud<Warehouse>('/admin/warehouses'),

  stock: {
    list: (params?: { warehouse_id?: number; search?: string; page?: number }) =>
      getPaginated<StockItem[]>('/admin/stock', params),
    listByVariant: (params?: { warehouse_id?: number; search?: string; page?: number }) =>
      getPaginated<StockByVariant[]>('/admin/stock/by-variant', params),
    assign: (data: {
      product_variant_id: number;
      warehouse_id: number;
      quantity: number;
      reason_code?: string;
    }) => postData<StockItem>('/admin/stock/assign', data),
    adjust: (data: {
      product_variant_id: number;
      warehouse_id: number;
      quantity_delta: number;
      reason_code: string;
    }) => postData<StockItem>('/admin/stock/adjust', data),
    movements: (params?: {
      warehouse_id?: number;
      product_variant_id?: number;
      search?: string;
      type?: string;
      page?: number;
    }) => getPaginated<StockMovement[]>('/admin/stock/movements', params),
  },

  orders: {
    list: (params?: {
      page?: number;
      per_page?: number;
      status?: string;
      user_id?: number;
      date_from?: string;
      date_to?: string;
      sort?: string;
      direction?: string;
    }) => getPaginated<Order[]>(ordersBase, params),
    get: (id: number) => getData<Order>(`${ordersBase}/${id}`),
    payments: (orderId: number) => getData<Payment[]>(`${ordersBase}/${orderId}/payments`),
  },

  shipments: {
    list: (orderId: number) => getData<Shipment[]>(`/admin/orders/${orderId}/shipments`),
    create: (orderId: number, data: { tracking_number?: string; carrier_code?: string }) =>
      postData<Shipment>(`/admin/orders/${orderId}/shipments`, data),
    update: (
      id: number,
      data: { status?: string; tracking_number?: string; carrier_code?: string }
    ) => patchData<Shipment>(`/admin/shipments/${id}`, data),
  },

  reviews: {
    list: (params?: { status?: string; product_id?: number; page?: number }) =>
      getPaginated<Review[]>('/admin/reviews', params),
    update: (id: number, data: { status: 'approved' | 'rejected' }) =>
      patchData<Review>(`/admin/reviews/${id}`, data),
  },

  refunds: {
    create: (paymentId: number, data?: { amount?: number; reason?: string }) =>
      postData<Refund>(`/admin/payments/${paymentId}/refund`, data ?? {}),
  },
};
