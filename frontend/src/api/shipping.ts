import { getData } from './client';
import type { ShippingQuote } from './types';

export const shippingApi = {
  quotes: (params?: { currency?: string }) => getData<ShippingQuote[]>('/shipping/quotes', { params }),
};
