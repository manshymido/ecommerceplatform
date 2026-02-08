import { getData, postData, patchData, deleteData } from './client';
import type { Cart } from './types';

export const cartApi = {
  show: () => getData<Cart>('/cart'),
  merge: () => postData<Cart>('/cart/merge'),
  addItem: (productVariantId: number, quantity: number) =>
    postData<Cart>('/cart/items', { product_variant_id: productVariantId, quantity }),
  updateItem: (itemId: number, quantity: number) =>
    patchData<Cart>(`/cart/items/${itemId}`, { quantity }),
  removeItem: (itemId: number) => deleteData<Cart>(`/cart/items/${itemId}`),
  applyCoupon: (code: string) => postData<Cart>('/cart/coupon', { code }),
  removeCoupon: () => deleteData<Cart>('/cart/coupon'),
};
