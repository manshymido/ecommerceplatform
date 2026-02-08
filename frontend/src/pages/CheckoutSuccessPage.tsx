import { Link, useLocation, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ordersApi } from '../api';
import type { Order } from '../api/types';
import { useAuthStore } from '../store/authStore';
import { OrderSummaryCard } from '../components/OrderSummaryCard';
import {
  CheckCircle,
  ShoppingBag,
  ArrowRight,
  Receipt,
  Mail,
} from 'lucide-react';

export function CheckoutSuccessPage() {
  const [params] = useSearchParams();
  const location = useLocation();
  const orderId = params.get('order');
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const orderFromState = location.state?.order as Order | undefined;

  const { data: orderFetched } = useQuery({
    queryKey: ['order', orderId],
    queryFn: () => ordersApi.show(Number(orderId)),
    enabled: !!orderId && isAuth && !orderFromState,
  });

  const order = orderFromState ?? orderFetched;

  return (
    <div className="min-h-screen bg-dark-50">
      <div className="container-app py-12 lg:py-20">
        <div className="max-w-2xl mx-auto">
          {/* Success animation header */}
          <div className="text-center mb-10">
            <div className="relative inline-flex mb-6">
              <div className="w-24 h-24 rounded-full bg-status-successBg flex items-center justify-center animate-[scale-in_0.4s_ease-out]">
                <CheckCircle className="w-12 h-12 text-status-success" />
              </div>
              <div className="absolute inset-0 rounded-full bg-status-success/20 animate-ping success-ping-ring" />
            </div>
            <h1 className="heading-lg text-text-primary mb-3">
              Order Confirmed!
            </h1>
            <p className="text-text-secondary text-lg">
              Thank you for your purchase. Your order has been placed successfully.
            </p>
            {orderId && (
              <div className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-dark-100 border border-surface-border">
                <Receipt className="w-4 h-4 text-accent" />
                <span className="text-sm text-text-muted">Order ID:</span>
                <span className="font-mono font-bold text-accent">#{orderId}</span>
              </div>
            )}
          </div>

          {/* Order details (if authenticated and fetched) */}
          {order && (
            <div className="mb-10">
              <OrderSummaryCard order={order} statusDisplay="simple" />
            </div>
          )}

          {/* Confirmation note */}
          <div className="card p-6 mb-8 bg-accent/5 border-accent/20">
            <div className="flex items-start gap-3">
              <Mail className="w-5 h-5 text-accent mt-0.5 shrink-0" />
              <div>
                <h3 className="font-semibold text-text-primary mb-1">Confirmation Email</h3>
                <p className="text-sm text-text-secondary">
                  We've sent a confirmation email with your order details. Please check your inbox
                  {!isAuth && ' and consider creating an account to track your order'}.
                </p>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            {isAuth && orderId ? (
              <Link to={`/account/orders/${orderId}`} className="btn btn-primary btn-lg">
                View Order Details
                <ArrowRight className="w-5 h-5" />
              </Link>
            ) : !isAuth ? (
              <>
                <Link to="/register" className="btn btn-primary btn-lg">
                  Create Account
                  <ArrowRight className="w-5 h-5" />
                </Link>
                <Link to="/order-lookup" className="btn btn-secondary btn-lg">
                  Track your order
                </Link>
              </>
            ) : null}
            <Link to="/products" className="btn btn-secondary btn-lg">
              <ShoppingBag className="w-5 h-5" />
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
