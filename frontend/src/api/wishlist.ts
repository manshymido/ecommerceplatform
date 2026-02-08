import { getData, postData, deleteData } from './client';
import type { Wishlist } from './types';

export const wishlistApi = {
  show: () => getData<Wishlist>('/wishlist'),
  addItem: (productVariantId: number) =>
    postData<Wishlist>('/wishlist/items', { product_variant_id: productVariantId }),
  removeItem: (itemId: number) => deleteData<Wishlist>(`/wishlist/items/${itemId}`),
};
