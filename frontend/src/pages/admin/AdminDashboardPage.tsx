import { Link } from 'react-router-dom';
import { adminApi } from '../../api/admin';
import { EmptyState } from '../../components/EmptyState';
import { formatCurrency, formatDate, getStatusBadgeClass } from '../../utils/format';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import {
  Package,
  ShoppingCart,
  DollarSign,
  TrendingUp,
  ArrowRight,
  Eye,
} from 'lucide-react';

function StatCard({
  label,
  value,
  sub,
  icon: Icon,
  trend,
}: {
  label: string;
  value: string | number;
  sub?: string;
  icon: React.ElementType;
  trend?: string;
}) {
  return (
    <div className="card p-6 hover:border-accent/30 transition-all">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-text-muted uppercase tracking-wide">{label}</p>
          <p className="mt-2 text-3xl font-bold text-text-primary tabular-nums">{value}</p>
          {sub != null && <p className="mt-1 text-sm text-text-muted">{sub}</p>}
          {trend && (
            <p className="mt-2 flex items-center gap-1 text-sm text-status-success">
              <TrendingUp className="w-4 h-4" />
              {trend}
            </p>
          )}
        </div>
        <div className="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
          <Icon className="w-6 h-6 text-accent" />
        </div>
      </div>
    </div>
  );
}

export function AdminDashboardPage() {
  const { data, render } = useQueryWithUI({
    queryKey: ['admin-dashboard'],
    queryFn: () => adminApi.dashboard(),
    fallbackMessage: 'Failed to load dashboard',
  });

  const ui = render();
  if (ui) return ui;

  const stats = data?.stats;
  const recentOrders = data?.recent_orders ?? [];

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="heading-md text-text-primary">Dashboard</h1>
          <p className="text-text-secondary mt-1">Welcome back! Here's an overview of your store.</p>
        </div>
        <div className="flex gap-3">
          <Link to="/admin/products" className="btn-secondary">
            <Package className="w-4 h-4" />
            Products
          </Link>
          <Link to="/admin/orders" className="btn-primary">
            <ShoppingCart className="w-4 h-4" />
            Orders
          </Link>
        </div>
      </div>

      {/* Stats */}
      {stats && (
        <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <StatCard
            label="Total Products"
            value={stats.total_products}
            icon={Package}
          />
          <StatCard
            label="Total Orders"
            value={stats.total_orders}
            icon={ShoppingCart}
          />
          <StatCard
            label="Revenue"
            value={formatCurrency(stats.revenue)}
            icon={DollarSign}
          />
        </section>
      )}

      {/* Recent Orders */}
      <section className="card overflow-hidden">
        <div className="admin-section-header flex items-center justify-between">
          <div>
            <h2 className="admin-section-title">Recent Orders</h2>
            <p className="admin-section-desc">Latest 5 orders from your customers</p>
          </div>
          <Link to="/admin/orders" className="btn-ghost text-sm hidden sm:inline-flex">
            View All
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

        {recentOrders.length === 0 ? (
          <EmptyState
            message="No orders yet"
            description="Orders will appear here once customers place them."
            icon={<ShoppingCart className="w-8 h-8 text-text-muted" />}
            className="px-6 py-12"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="table">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th className="text-right">Total</th>
                  <th className="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                {recentOrders.map((order) => (
                  <tr key={order.id}>
                    <td>
                      <span className="font-mono font-medium text-text-primary">{order.order_number}</span>
                    </td>
                    <td>
                      <span className={getStatusBadgeClass(order.status)}>{order.status}</span>
                    </td>
                    <td className="text-text-secondary">
                      {formatDate(order.created_at)}
                    </td>
                    <td className="text-right font-medium tabular-nums text-accent">
                      {formatCurrency(order.total_amount, order.currency)}
                    </td>
                    <td className="text-right">
                      <Link
                        to={`/admin/orders/${order.id}`}
                        className="inline-flex items-center gap-1 text-sm font-medium text-accent hover:text-accent-light transition-colors"
                      >
                        <Eye className="w-4 h-4" />
                        View
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {recentOrders.length > 0 && (
          <div className="border-t border-surface-border bg-dark-100 px-6 py-3 text-center sm:hidden">
            <Link to="/admin/orders" className="btn-ghost text-sm">
              View all orders
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        )}
      </section>
    </div>
  );
}
