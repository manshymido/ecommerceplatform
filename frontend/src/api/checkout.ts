import { postData } from './client';
import type { Order } from './types';
import type { CheckoutPayload } from './types';

export interface CheckoutPaymentIntentResponse {
  client_secret: string;
  payment_intent_id: string;
  stripe_publishable_key: string;
  amount: number;
  currency: string;
}

export const checkoutApi = {
  placeOrder: (payload: CheckoutPayload) => postData<Order>('/checkout', payload),
  createPaymentIntent: (shippingAmount?: number) =>
    postData<CheckoutPaymentIntentResponse>('/checkout/payment-intent', {
      shipping_amount: shippingAmount ?? 0,
    }),
};
