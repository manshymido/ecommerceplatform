import { getData, getPaginated, postData } from './client';
import type { Order } from './types';

export interface PaymentIntentResponse {
  payment: {
    id: number;
    order_id: number;
    provider: string;
    provider_reference: string;
    amount: number;
    currency: string;
    status: string;
  };
  client_secret: string;
  payment_intent_id: string;
  stripe_publishable_key: string;
}

export const ordersApi = {
  list: (params?: { page?: number }) => getPaginated<Order[]>('/orders', params),
  show: (id: number) => getData<Order>(`/orders/${id}`),
  lookup: (orderNumber: string, email: string) =>
    getData<Order>('/orders/lookup', { params: { order_number: orderNumber, email } }),
  pay: (orderId: number) =>
    postData<PaymentIntentResponse>(`/orders/${orderId}/pay`),
  confirmPayment: (orderId: number, paymentIntentId: string) =>
    postData<{ status: string; already_confirmed?: boolean }>(
      `/orders/${orderId}/pay/confirm`,
      { payment_intent_id: paymentIntentId }
    ),
};
