import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Plus, Pencil } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { OrderLine, Address, Payment, Shipment } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { Button, FormField, Input, Select } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import { formatCurrency, formatDate, getStatusBadgeClass } from '../../utils/format';

function formatAddress(a: Address | null): string {
  if (!a) return '—';
  const parts = [
    a.name,
    a.line1,
    a.line2,
    [a.city, a.state, a.postal_code].filter(Boolean).join(' '),
    a.country,
  ].filter(Boolean);
  return parts.join(', ') || '—';
}

export function AdminOrderDetailPage() {
  const { id } = useParams<{ id: string }>();
  const orderId = id ? parseInt(id, 10) : 0;
  const queryClient = useQueryClient();
  const [refundPaymentId, setRefundPaymentId] = useState<number | null>(null);
  const [refundAmount, setRefundAmount] = useState('');
  const [refundReason, setRefundReason] = useState('');
  const [shipmentModal, setShipmentModal] = useState<'create' | number | null>(null);
  const [shipmentTracking, setShipmentTracking] = useState('');
  const [shipmentCarrier, setShipmentCarrier] = useState('');
  const [shipmentStatus, setShipmentStatus] = useState('');

  const orderQuery = useQueryWithUI({
    queryKey: ['admin-order', orderId],
    queryFn: () => adminApi.orders.get(orderId),
    enabled: orderId > 0,
    fallbackMessage: 'Failed to load order',
  });

  const paymentsQuery = useQuery({
    queryKey: ['admin-order-payments', orderId],
    queryFn: () => adminApi.orders.payments(orderId),
    enabled: orderId > 0 && (!orderQuery.data || !Array.isArray(orderQuery.data.payments)),
  });

  const shipmentsQuery = useQuery({
    queryKey: ['admin-order-shipments', orderId],
    queryFn: () => adminApi.shipments.list(orderId),
    enabled: orderId > 0 && (!orderQuery.data || !Array.isArray(orderQuery.data.shipments)),
  });

  const refundMutation = useMutation({
    mutationFn: ({ paymentId, data }: { paymentId: number; data?: { amount?: number; reason?: string } }) =>
      adminApi.refunds.create(paymentId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-order', orderId] });
      queryClient.invalidateQueries({ queryKey: ['admin-order-payments', orderId] });
      setRefundPaymentId(null);
      setRefundAmount('');
      setRefundReason('');
    },
  });

  const createShipmentMutation = useMutation({
    mutationFn: (data: { tracking_number?: string; carrier_code?: string }) =>
      adminApi.shipments.create(orderId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-order', orderId] });
      queryClient.invalidateQueries({ queryKey: ['admin-order-shipments', orderId] });
      setShipmentModal(null);
      setShipmentTracking('');
      setShipmentCarrier('');
    },
  });

  const updateShipmentMutation = useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: number;
      data: { status?: string; tracking_number?: string; carrier_code?: string };
    }) => adminApi.shipments.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-order', orderId] });
      queryClient.invalidateQueries({ queryKey: ['admin-order-shipments', orderId] });
      setShipmentModal(null);
      setShipmentTracking('');
      setShipmentCarrier('');
      setShipmentStatus('');
    },
  });

  const orderUi = orderQuery.render();
  const order = orderQuery.data;
  const payments = (order?.payments ?? paymentsQuery.data ?? []) as Payment[];
  const shipments = (order?.shipments ?? shipmentsQuery.data ?? []) as Shipment[];

  if (orderUi || !order) {
    return (
      <div className="space-y-6">
        <Link to="/admin/orders" className="btn-ghost text-sm inline-flex items-center gap-1">
          <ArrowLeft className="w-4 h-4" />
          Back to orders
        </Link>
        {orderUi}
      </div>
    );
  }

  const currency = order.currency;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link to="/admin/orders" className="btn-ghost text-sm inline-flex items-center gap-1 mb-2">
            <ArrowLeft className="w-4 h-4" />
            Back to orders
          </Link>
          <h1 className="text-2xl font-bold text-text-primary">Order #{order.order_number}</h1>
          <p className="text-sm text-text-muted mt-1">
            {formatDate(order.created_at)} ·{' '}
            <span className={getStatusBadgeClass(order.status)}>{order.status}</span>
          </p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="card p-6">
          <h2 className="text-lg font-semibold text-text-primary mb-4">Order lines</h2>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-surface-border text-left text-xs font-semibold uppercase text-text-muted">
                  <th className="pb-2">Product</th>
                  <th className="pb-2 text-right">Qty</th>
                  <th className="pb-2 text-right">Unit price</th>
                  <th className="pb-2 text-right">Total</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-surface-border">
                {(order.lines ?? []).map((line: OrderLine) => (
                  <tr key={line.id}>
                    <td className="py-2">
                      <div className="font-medium text-text-primary">
                        {line.product_name_snapshot ?? line.sku_snapshot ?? `Variant #${line.product_variant_id}`}
                      </div>
                      {line.sku_snapshot && (
                        <div className="text-sm text-text-muted">{line.sku_snapshot}</div>
                      )}
                    </td>
                    <td className="py-2 text-right text-text-secondary">{line.quantity}</td>
                    <td className="py-2 text-right text-text-muted">
                      {formatCurrency(line.unit_price_amount, line.unit_price_currency)}
                    </td>
                    <td className="py-2 text-right font-medium text-text-primary">
                      {formatCurrency(line.total_line_amount, line.unit_price_currency)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 pt-4 border-t border-surface-border space-y-1 text-sm">
            <div className="flex justify-between text-text-muted">
              <span>Subtotal</span>
              <span>{formatCurrency(order.subtotal_amount, currency)}</span>
            </div>
            {order.discount_amount > 0 && (
              <div className="flex justify-between text-text-muted">
                <span>Discount</span>
                <span>-{formatCurrency(order.discount_amount, currency)}</span>
              </div>
            )}
            {order.tax_amount > 0 && (
              <div className="flex justify-between text-text-muted">
                <span>Tax</span>
                <span>{formatCurrency(order.tax_amount, currency)}</span>
              </div>
            )}
            {order.shipping_amount > 0 && (
              <div className="flex justify-between text-text-muted">
                <span>Shipping</span>
                <span>{formatCurrency(order.shipping_amount, currency)}</span>
              </div>
            )}
            <div className="flex justify-between font-semibold text-text-primary pt-2">
              <span>Total</span>
              <span>{formatCurrency(order.total_amount, currency)}</span>
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-text-primary mb-2">Billing address</h2>
            <p className="text-text-secondary text-sm">{formatAddress(order.billing_address)}</p>
            <h2 className="text-lg font-semibold text-text-primary mt-4 mb-2">Shipping address</h2>
            <p className="text-text-secondary text-sm">{formatAddress(order.shipping_address)}</p>
            {order.shipping_method_name && (
              <p className="text-text-muted text-sm mt-2">Shipping: {order.shipping_method_name}</p>
            )}
          </div>

          <div className="card p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-text-primary">Payments</h2>
            </div>
            {payments.length === 0 ? (
              <p className="text-sm text-text-muted">No payments recorded.</p>
            ) : (
              <ul className="space-y-3">
                {payments.map((pay) => (
                  <li
                    key={pay.id}
                    className="flex items-center justify-between rounded-xl border border-surface-border bg-dark-50/50 p-3"
                  >
                    <div>
                      <span className="font-medium text-text-primary">
                        {formatCurrency(pay.amount, pay.currency)}
                      </span>
                      <span className={`ml-2 rounded-full px-2 py-0.5 text-xs ${getStatusBadgeClass(pay.status)}`}>
                        {pay.status}
                      </span>
                      {pay.provider_reference && (
                        <div className="text-xs text-text-muted mt-1">{pay.provider_reference}</div>
                      )}
                    </div>
                    {pay.status === 'succeeded' && (
                      <button
                        type="button"
                        aria-label="Refund payment"
                        onClick={() => setRefundPaymentId(pay.id)}
                        className="btn-ghost text-sm text-red-400 hover:text-red-300"
                      >
                        Refund
                      </button>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="card p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-text-primary">Shipments</h2>
              <button
                type="button"
                onClick={() => {
                  setShipmentModal('create');
                  setShipmentTracking('');
                  setShipmentCarrier('');
                }}
                className="btn-primary text-sm flex items-center gap-1"
              >
                <Plus className="w-4 h-4" />
                Add shipment
              </button>
            </div>
            {shipments.length === 0 ? (
              <p className="text-sm text-text-muted">No shipments yet.</p>
            ) : (
              <ul className="space-y-3">
                {shipments.map((s) => (
                  <li
                    key={s.id}
                    className="rounded-xl border border-surface-border bg-dark-50/50 p-3 flex flex-col gap-2"
                  >
                    <div className="flex items-center justify-between">
                      <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${getStatusBadgeClass(s.status)}`}>
                        {s.status}
                      </span>
                      <button
                        type="button"
                        onClick={() => {
                          setShipmentModal(s.id);
                          setShipmentTracking(s.tracking_number ?? '');
                          setShipmentCarrier(s.carrier_code ?? '');
                          setShipmentStatus('');
                        }}
                        className="btn-ghost text-sm text-accent"
                        aria-label="Edit shipment"
                      >
                        <Pencil className="w-4 h-4 inline" />
                      </button>
                    </div>
                    {s.tracking_number && (
                      <p className="text-sm text-text-secondary">Tracking: {s.tracking_number}</p>
                    )}
                    {s.carrier_code && (
                      <p className="text-sm text-text-muted">Carrier: {s.carrier_code}</p>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </div>

      <AdminModal
        open={refundPaymentId !== null}
        onClose={() => !refundMutation.isPending && setRefundPaymentId(null)}
        title="Refund payment"
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setRefundPaymentId(null)}>
              Cancel
            </Button>
            <Button
              type="button"
              onClick={() => {
                refundMutation.mutate({
                  paymentId: refundPaymentId!,
                  data: {
                    amount: refundAmount ? parseFloat(refundAmount) : undefined,
                    reason: refundReason || undefined,
                  },
                });
              }}
              disabled={refundMutation.isPending}
            >
              {refundMutation.isPending ? 'Processing…' : 'Refund'}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <FormField label="Amount (optional, full refund if empty)" htmlFor="refund-amount">
            <Input
              id="refund-amount"
              type="number"
              step="0.01"
              min={0}
              value={refundAmount}
              onChange={(e) => setRefundAmount(e.target.value)}
              placeholder="Leave empty for full refund"
            />
          </FormField>
          <FormField label="Reason (optional)" htmlFor="refund-reason">
            <Input
              id="refund-reason"
              type="text"
              value={refundReason}
              onChange={(e) => setRefundReason(e.target.value)}
            />
          </FormField>
        </div>
      </AdminModal>

      <AdminModal
        open={shipmentModal !== null}
        onClose={() => {
          if (!createShipmentMutation.isPending && !updateShipmentMutation.isPending) {
            setShipmentModal(null);
          }
        }}
        title={shipmentModal === 'create' ? 'Add shipment' : 'Update shipment'}
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setShipmentModal(null)}>
              Cancel
            </Button>
            {shipmentModal === 'create' ? (
              <Button
                type="button"
                onClick={() =>
                  createShipmentMutation.mutate({
                    tracking_number: shipmentTracking || undefined,
                    carrier_code: shipmentCarrier || undefined,
                  })
                }
                disabled={createShipmentMutation.isPending}
              >
                {createShipmentMutation.isPending ? 'Creating…' : 'Create'}
              </Button>
            ) : (
              <Button
                type="button"
                onClick={() => {
                  if (typeof shipmentModal === 'number') {
                    updateShipmentMutation.mutate({
                      id: shipmentModal,
                      data: {
                        tracking_number: shipmentTracking || undefined,
                        carrier_code: shipmentCarrier || undefined,
                        ...(shipmentStatus === 'shipped' || shipmentStatus === 'delivered'
                          ? { status: shipmentStatus as 'shipped' | 'delivered' }
                          : {}),
                      },
                    });
                  }
                }}
                disabled={updateShipmentMutation.isPending}
              >
                {updateShipmentMutation.isPending ? 'Saving…' : 'Save'}
              </Button>
            )}
          </>
        }
      >
        <div className="space-y-4">
          <FormField label="Tracking number" htmlFor="shipment-tracking">
            <Input
              id="shipment-tracking"
              type="text"
              value={shipmentTracking}
              onChange={(e) => setShipmentTracking(e.target.value)}
            />
          </FormField>
          <FormField label="Carrier code" htmlFor="shipment-carrier">
            <Input
              id="shipment-carrier"
              type="text"
              value={shipmentCarrier}
              onChange={(e) => setShipmentCarrier(e.target.value)}
            />
          </FormField>
          {shipmentModal !== 'create' && (
            <FormField label="Mark status" htmlFor="shipment-status">
              <Select
                id="shipment-status"
                value={shipmentStatus}
                onChange={(e) => setShipmentStatus(e.target.value)}
              >
                <option value="">No change</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
              </Select>
            </FormField>
          )}
        </div>
      </AdminModal>
    </div>
  );
}
