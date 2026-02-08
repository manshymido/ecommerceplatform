import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { ordersApi } from '../api';
import type { Order } from '../api/types';
import { getApiErrorMessage } from '../utils/apiError';
import { ErrorMessage } from '../components/ErrorMessage';
import { OrderSummaryCard } from '../components/OrderSummaryCard';
import { Input, Label, Button } from '../components/ui';
import { Receipt, Search } from 'lucide-react';

export function OrderLookupPage() {
  const [orderNumber, setOrderNumber] = useState('');
  const [email, setEmail] = useState('');
  const [order, setOrder] = useState<Order | null>(null);

  const lookup = useMutation({
    mutationFn: () => ordersApi.lookup(orderNumber.trim(), email.trim()),
    onSuccess: (data) => {
      setOrder(data);
    },
  });

  return (
    <div className="min-h-screen bg-dark-50">
      <div className="container-app py-12 lg:py-20">
        <div className="max-w-2xl mx-auto">
          <div className="text-center mb-8">
            <h1 className="heading-lg text-text-primary mb-2">Look up your order</h1>
            <p className="text-text-secondary">
              Enter your order number and email to view order status and details.
            </p>
          </div>

          {!order ? (
            <div className="card p-6 mb-8">
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  lookup.mutate();
                }}
                className="space-y-5"
              >
                <div>
                  <Label htmlFor="order-number">Order number</Label>
                  <div className="relative">
                    <Receipt className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                    <Input
                      id="order-number"
                      type="text"
                      value={orderNumber}
                      onChange={(e) => setOrderNumber(e.target.value)}
                      placeholder="e.g. ORD-12345"
                      required
                      className="pl-11"
                    />
                  </div>
                </div>
                <div>
                  <Label htmlFor="lookup-email">Email address</Label>
                  <div className="relative">
                    <Input
                      id="lookup-email"
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="your@email.com"
                      required
                    />
                  </div>
                </div>
                {lookup.isError && (
                  <ErrorMessage message={getApiErrorMessage(lookup.error, 'Order not found')} />
                )}
                <Button
                  type="submit"
                  variant="primary"
                  disabled={lookup.isPending}
                  className="w-full btn-lg"
                >
                  <Search className="w-5 h-5" />
                  {lookup.isPending ? 'Looking up...' : 'Look up order'}
                </Button>
              </form>
            </div>
          ) : (
            <div className="mb-10">
              <OrderSummaryCard order={order} statusDisplay="full" />
            </div>
          )}

          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            {order ? (
              <button
                type="button"
                onClick={() => setOrder(null)}
                className="btn btn-secondary btn-lg"
              >
                Look up another order
              </button>
            ) : null}
            <Link to="/" className="btn btn-ghost btn-lg">
              Back to home
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
