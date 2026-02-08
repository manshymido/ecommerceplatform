import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ordersApi } from '../api';
import type { PaymentIntentResponse } from '../api/orders';
import { Button } from '../components/ui';
import { ErrorMessage } from '../components/ErrorMessage';
import { StripePaymentForm } from '../components/StripePaymentForm';
import { getApiErrorMessage } from '../utils/apiError';
import { formatCurrency } from '../utils/format';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import {
  Package,
  ChevronLeft,
  CreditCard,
  CheckCircle,
  ShoppingBag,
  Receipt,
  ArrowRight,
} from 'lucide-react';
import { getOrderStatusConfig } from '../constants/orderStatus';
import { AddressDisplay } from '../components/AddressDisplay';

export function OrderDetailPage() {
  const { id } = useParams<{ id: string }>();
  const orderId = id ? Number(id) : NaN;
  const queryClient = useQueryClient();

  const { data: order, render } = useQueryWithUI({
    queryKey: ['order', orderId],
    queryFn: () => ordersApi.show(orderId),
    fallbackMessage: 'Order not found',
    enabled: Number.isInteger(orderId),
  });

  const [paymentData, setPaymentData] = useState<PaymentIntentResponse | null>(null);

  const payOrder = useMutation({
    mutationFn: () => ordersApi.pay(orderId),
    onSuccess: (res) => {
      if (res?.client_secret && res?.stripe_publishable_key) {
        setPaymentData(res);
      }
    },
  });

  const ui = render();
  if (ui) return ui;
  if (!Number.isInteger(orderId)) return null;
  if (!order) return null;

  const statusConfig = getOrderStatusConfig(order.status);
  const StatusIcon = statusConfig.icon;

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <Link
            to="/account/orders"
            className="inline-flex items-center gap-2 text-text-secondary hover:text-accent transition-colors mb-6"
          >
            <ChevronLeft className="w-4 h-4" />
            Back to Orders
          </Link>

          <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-xl bg-accent/10 flex items-center justify-center">
                <Receipt className="w-7 h-7 text-accent" />
              </div>
              <div>
                <h1 className="heading-md text-text-primary">{order.order_number}</h1>
                <p className="text-text-secondary mt-1">
                  Placed on {new Date(order.created_at ?? '').toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                  })}
                </p>
              </div>
            </div>

            <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl ${statusConfig.bg} ${statusConfig.color} border border-current/20`}>
              <StatusIcon className="w-5 h-5" />
              <span className="font-semibold">{statusConfig.label}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        <div className="grid lg:grid-cols-3 gap-8">
          {/* Order items */}
          <div className="lg:col-span-2 space-y-6">
            <div className="card overflow-hidden">
              <div className="admin-section-header">
                <h2 className="admin-section-title flex items-center gap-2">
                  <Package className="w-5 h-5 text-accent" />
                  Order Items
                </h2>
              </div>

              <div className="divide-y divide-surface-border">
                {order.lines?.map((line) => (
                  <div key={line.id} className="p-6 flex items-center gap-4">
                    <div className="w-16 h-16 rounded-xl bg-dark-100 flex items-center justify-center shrink-0">
                      <ShoppingBag className="w-7 h-7 text-text-muted" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-text-primary">
                        {line.product_name_snapshot ?? `Variant #${line.product_variant_id}`}
                      </h3>
                      {line.sku_snapshot && (
                        <p className="text-xs text-text-muted">SKU: {line.sku_snapshot}</p>
                      )}
                      <p className="text-sm text-text-muted mt-0.5">
                        Quantity: {line.quantity}
                      </p>
                    </div>
                    <div className="text-right">
                      <span className="font-semibold text-accent">
                        {formatCurrency(line.unit_price_amount * line.quantity, line.unit_price_currency)}
                      </span>
                      <p className="text-sm text-text-muted">
                        {formatCurrency(line.unit_price_amount, line.unit_price_currency)} each
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Addresses */}
            {(order.shipping_address || order.billing_address) && (
              <div className="grid sm:grid-cols-2 gap-6">
                <AddressDisplay address={order.shipping_address} title="Shipping Address" />
                <AddressDisplay address={order.billing_address} title="Billing Address" />
              </div>
            )}
          </div>

          {/* Order summary sidebar */}
          <div className="lg:col-span-1">
            <div className="card p-6 sticky top-24">
              <h2 className="font-bold text-lg text-text-primary mb-6">Order Summary</h2>

              <div className="space-y-3 text-sm">
                <div className="flex justify-between text-text-secondary">
                  <span>Subtotal</span>
                  <span>{formatCurrency(order.subtotal_amount ?? order.total_amount, order.currency)}</span>
                </div>
                {order.discount_amount > 0 && (
                  <div className="flex justify-between text-status-success">
                    <span>Discount</span>
                    <span>-{formatCurrency(order.discount_amount, order.currency)}</span>
                  </div>
                )}
                {order.shipping_amount > 0 && (
                  <div className="flex justify-between text-text-secondary">
                    <span>Shipping</span>
                    <span>{formatCurrency(order.shipping_amount, order.currency)}</span>
                  </div>
                )}
                {order.tax_amount > 0 && (
                  <div className="flex justify-between text-text-secondary">
                    <span>Tax</span>
                    <span>{formatCurrency(order.tax_amount, order.currency)}</span>
                  </div>
                )}
              </div>

              <div className="divider my-4" />

              <div className="flex justify-between items-baseline">
                <span className="font-semibold text-text-primary">Total</span>
                <span className="text-2xl font-bold text-accent">
                  {formatCurrency(order.total_amount, order.currency)}
                </span>
              </div>

              {order.status !== 'paid' && order.status !== 'completed' && order.status !== 'cancelled' && (
                <div className="mt-6">
                  {paymentData ? (
                    <StripePaymentForm
                      clientSecret={paymentData.client_secret}
                      publishableKey={paymentData.stripe_publishable_key}
                      paymentIntentId={paymentData.payment_intent_id}
                      orderId={orderId}
                      amount={order.total_amount}
                      currency={order.currency}
                      onSuccess={() => {
                        setPaymentData(null);
                        queryClient.invalidateQueries({ queryKey: ['order', orderId] });
                      }}
                      onCancel={() => setPaymentData(null)}
                    />
                  ) : (
                    <Button
                      onClick={() => payOrder.mutate()}
                      disabled={payOrder.isPending}
                      variant="primary"
                      className="w-full btn-lg"
                    >
                      {payOrder.isPending ? (
                        'Processing...'
                      ) : (
                        <>
                          <CreditCard className="w-5 h-5" />
                          Pay Now
                          <ArrowRight className="w-5 h-5" />
                        </>
                      )}
                    </Button>
                  )}
                  {payOrder.isError && (
                    <div className="mt-3">
                      <ErrorMessage message={getApiErrorMessage(payOrder.error, 'Payment failed')} />
                    </div>
                  )}
                </div>
              )}

              {order.status === 'paid' && (
                <div className="mt-6 p-4 rounded-xl bg-status-successBg border border-status-success/30 text-center">
                  <CheckCircle className="w-8 h-8 text-status-success mx-auto mb-2" />
                  <p className="font-medium text-status-success">Payment Complete</p>
                  <p className="text-sm text-text-muted mt-1">Thank you for your order!</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
