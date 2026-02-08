import { Package, ShoppingBag, Truck, CheckCircle } from 'lucide-react';
import type { Order } from '../api/types';
import { formatCurrency } from '../utils/format';
import { getOrderStatusConfig } from '../constants/orderStatus';
import { AddressDisplay } from './AddressDisplay';

interface OrderSummaryCardProps {
  order: Order;
  statusDisplay?: 'simple' | 'full';
}

export function OrderSummaryCard({ order, statusDisplay = 'full' }: OrderSummaryCardProps) {
  const itemCount = order.lines?.length ?? 0;
  const statusConfig = statusDisplay === 'full' ? getOrderStatusConfig(order.status) : null;

  return (
    <div className="space-y-6">
      <div className="card p-6">
        <div className="flex items-center gap-3 mb-4">
          <div className="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
            <Package className="w-5 h-5 text-accent" />
          </div>
          <div>
            <h2 className="font-semibold text-text-primary">Order #{order.order_number}</h2>
            <p className="text-sm text-text-muted">
              {itemCount} item{itemCount !== 1 ? 's' : ''}
            </p>
          </div>
          <div className="ml-auto">
            {statusDisplay === 'simple' ? (
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-status-successBg text-status-success text-sm font-medium">
                <CheckCircle className="w-4 h-4" />
                {order.status === 'paid' ? 'Paid' : 'Confirmed'}
              </span>
            ) : statusConfig ? (
              (() => {
                const StatusIcon = statusConfig.icon;
                return (
                  <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium ${statusConfig.bg} ${statusConfig.color}`}>
                    <StatusIcon className="w-4 h-4" />
                    {statusConfig.label}
                  </span>
                );
              })()
            ) : null}
          </div>
        </div>

        <div className="divide-y divide-surface-border">
          {order.lines?.map((line) => (
            <div key={line.id} className="flex items-center gap-3 py-3">
              <div className="w-12 h-12 rounded-lg bg-dark-100 flex items-center justify-center shrink-0">
                <ShoppingBag className="w-5 h-5 text-text-muted" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-text-primary truncate">
                  {line.product_name_snapshot ?? `Variant #${line.product_variant_id}`}
                </p>
                <p className="text-xs text-text-muted">Qty: {line.quantity}</p>
              </div>
              <span className="text-sm font-semibold text-text-primary">
                {formatCurrency(line.unit_price_amount * line.quantity, line.unit_price_currency)}
              </span>
            </div>
          ))}
        </div>

        <div className="divider my-3" />

        <div className="space-y-2 text-sm">
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
          <div className="flex justify-between font-semibold text-text-primary pt-2 border-t border-surface-border">
            <span>Total</span>
            <span className="text-accent">{formatCurrency(order.total_amount, order.currency)}</span>
          </div>
        </div>
      </div>

      <div className="grid sm:grid-cols-2 gap-4">
        {order.shipping_address && (
          <AddressDisplay address={order.shipping_address} title="Shipping To" />
        )}
        {order.shipping_method_name && (
          <div className="card p-5">
            <div className="flex items-center gap-2 mb-3">
              <Truck className="w-4 h-4 text-accent" />
              <h3 className="font-semibold text-text-primary text-sm">Shipping Method</h3>
            </div>
            <p className="text-sm text-text-secondary">{order.shipping_method_name}</p>
          </div>
        )}
      </div>
    </div>
  );
}
