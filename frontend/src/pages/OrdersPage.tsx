import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ordersApi } from '../api';
import { EmptyState } from '../components/EmptyState';
import { Pagination } from '../components/Pagination';
import { formatCurrency } from '../utils/format';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import {
  Package,
  ChevronRight,
  ShoppingBag,
} from 'lucide-react';
import { getOrderStatusConfig } from '../constants/orderStatus';

export function OrdersPage() {
  const [page, setPage] = useState(1);

  const { data, render } = useQueryWithUI({
    queryKey: ['orders', page],
    queryFn: () => ordersApi.list({ page }),
    fallbackMessage: 'Failed to load orders',
  });

  const ui = render();
  if (ui) return ui;

  const orders = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <div className="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link to="/" className="hover:text-accent transition-colors">Home</Link>
            <span>/</span>
            <span className="text-text-primary">Orders</span>
          </div>
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
              <Package className="w-6 h-6 text-accent" />
            </div>
            <div>
              <h1 className="heading-lg text-text-primary">My Orders</h1>
              <p className="text-text-secondary mt-1">Track and manage your orders</p>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        {orders.length === 0 ? (
          <div className="card p-12 text-center max-w-lg mx-auto">
            <EmptyState
              message="No orders yet"
              description="When you place your first order, it will appear here for you to track."
              icon={<ShoppingBag className="w-8 h-8 text-text-muted" />}
              action={
                <Link to="/products" className="btn-primary btn-lg inline-flex items-center gap-2">
                  <ShoppingBag className="w-5 h-5" />
                  Start Shopping
                </Link>
              }
              className="!p-0"
            />
          </div>
        ) : (
          <>
            <div className="space-y-4">
              {orders.map((order, i) => {
                const statusConfig = getOrderStatusConfig(order.status);
                const StatusIcon = statusConfig.icon;

                return (
                  <Link
                    key={order.id}
                    to={`/account/orders/${order.id}`}
                    className="card p-6 flex flex-col sm:flex-row sm:items-center gap-4 hover:border-accent/30 transition-all animate-fade-in-up group"
                    style={{ animationDelay: `${i * 50}ms` }}
                  >
                    {/* Order icon */}
                    <div className="w-12 h-12 rounded-xl bg-dark-100 flex items-center justify-center shrink-0">
                      <Package className="w-6 h-6 text-accent" />
                    </div>

                    {/* Order info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2 mb-1">
                        <span className="font-semibold text-text-primary group-hover:text-accent transition-colors">
                          {order.order_number}
                        </span>
                        <span className={`badge ${statusConfig.bg} ${statusConfig.color} border-0`}>
                          <StatusIcon className="w-3 h-3" />
                          {statusConfig.label}
                        </span>
                      </div>
                      <p className="text-sm text-text-muted">
                        Placed on {new Date(order.created_at ?? '').toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric',
                        })}
                      </p>
                    </div>

                    {/* Total */}
                    <div className="text-right">
                      <span className="text-lg font-bold text-accent">
                        {formatCurrency(order.total_amount, order.currency)}
                      </span>
                    </div>

                    {/* Arrow */}
                    <ChevronRight className="w-5 h-5 text-text-muted group-hover:text-accent group-hover:translate-x-1 transition-all hidden sm:block" />
                  </Link>
                );
              })}
            </div>

            {meta && meta.last_page > 1 && (
              <div className="mt-8">
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
