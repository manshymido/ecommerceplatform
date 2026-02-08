import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Order } from '../../api/types';
import { EmptyState } from '../../components/EmptyState';
import { Pagination } from '../../components/Pagination';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import { formatCurrency, formatDate, getStatusBadgeClass } from '../../utils/format';

const STATUS_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'created', label: 'Created' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export function AdminOrdersPage() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const listQuery = useQueryWithUI({
    queryKey: ['admin-orders', page, status || undefined, dateFrom || undefined, dateTo || undefined],
    queryFn: () =>
      adminApi.orders.list({
        page,
        per_page: 15,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
      }),
    fallbackMessage: 'Failed to load orders',
  });

  const listUi = listQuery.render();
  const orders = listQuery.data?.data ?? [];
  const meta = listQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Orders</h1>
      </div>

      <div className="card overflow-hidden">
        <div className="p-4 border-b border-surface-border sm:p-6">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4 flex-wrap">
            <select
              aria-label="Filter by status"
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
            >
              {STATUS_OPTIONS.map((o) => (
                <option key={o.value || 'all'} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
            <div className="flex items-center gap-2">
              <label className="text-sm text-text-muted">From</label>
              <input
                type="date"
                aria-label="Date from"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                className="rounded-xl border border-surface-border bg-dark-50 px-3 py-2 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
              />
            </div>
            <div className="flex items-center gap-2">
              <label className="text-sm text-text-muted">To</label>
              <input
                type="date"
                aria-label="Date to"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                className="rounded-xl border border-surface-border bg-dark-50 px-3 py-2 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
              />
            </div>
          </div>
        </div>

        {listUi ? (
          <div className="p-6">{listUi}</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[640px]">
                <thead>
                  <tr className="border-b border-surface-border bg-dark-50/80">
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Order
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Date
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Status
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Customer
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Total
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {orders.map((order: Order) => (
                    <tr key={order.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3 font-medium text-text-primary">
                        #{order.order_number}
                      </td>
                      <td className="px-4 py-3 text-sm text-text-muted">
                        {formatDate(order.created_at)}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`rounded-full px-2 py-0.5 text-xs font-medium ${getStatusBadgeClass(order.status)}`}
                        >
                          {order.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-text-secondary">
                        {order.guest_email ?? order.user_email ?? order.user_name ?? (order.user_id != null ? `User #${order.user_id}` : '—')}
                      </td>
                      <td className="px-4 py-3 text-right font-medium text-text-primary">
                        {formatCurrency(order.total_amount, order.currency)}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <Link
                          to={`/admin/orders/${order.id}`}
                          className="btn-ghost text-sm text-accent hover:underline inline-flex items-center gap-1"
                        >
                          View
                          <ChevronRight className="w-4 h-4" />
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {orders.length === 0 && (
              <div className="p-8">
                <EmptyState message="No orders match your filters." />
              </div>
            )}
            {meta && meta.last_page > 1 && (
              <div className="border-t border-surface-border p-4">
                <Pagination
                  currentPage={meta.current_page}
                  lastPage={meta.last_page}
                  onPageChange={setPage}
                />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
