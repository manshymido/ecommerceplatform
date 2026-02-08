import { useState, useCallback } from 'react';
import { loadStripe } from '@stripe/stripe-js';
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements,
} from '@stripe/react-stripe-js';
import { ordersApi } from '../api';
import { getApiErrorMessage } from '../utils/apiError';
import { Button } from './ui';
import { CreditCard, Loader2, CheckCircle, XCircle } from 'lucide-react';

interface StripePaymentFormProps {
  clientSecret: string;
  publishableKey: string;
  paymentIntentId: string;
  orderId: number;
  amount: number;
  currency: string;
  onSuccess: () => void;
  onCancel: () => void;
}

// Cache the stripe promise per publishable key to avoid re-loading
const stripePromiseCache = new Map<string, ReturnType<typeof loadStripe>>();
function getStripePromise(publishableKey: string) {
  if (!stripePromiseCache.has(publishableKey)) {
    stripePromiseCache.set(publishableKey, loadStripe(publishableKey));
  }
  return stripePromiseCache.get(publishableKey)!;
}

function CheckoutForm({
  orderId,
  paymentIntentId,
  amount,
  currency,
  onSuccess,
  onCancel,
}: Omit<StripePaymentFormProps, 'clientSecret' | 'publishableKey'>) {
  const stripe = useStripe();
  const elements = useElements();
  const [status, setStatus] = useState<'idle' | 'processing' | 'succeeded' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState('');

  const handleSubmit = useCallback(
    async (e: React.FormEvent) => {
      e.preventDefault();
      if (!stripe || !elements) return;

      setStatus('processing');
      setErrorMessage('');

      const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
          return_url: `${window.location.origin}/account/orders/${orderId}`,
        },
        redirect: 'if_required',
      });

      if (error) {
        setStatus('error');
        setErrorMessage(getApiErrorMessage(error, 'Payment failed. Please try again.'));
      } else {
        // Confirm payment on the backend (updates order status without relying on webhook)
        try {
          await ordersApi.confirmPayment(orderId, paymentIntentId);
        } catch {
          // Non-critical: webhook will handle it if confirm fails
        }
        setStatus('succeeded');
        setTimeout(onSuccess, 1500);
      }
    },
    [stripe, elements, orderId, paymentIntentId, onSuccess]
  );

  const formattedAmount = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
  }).format(amount);

  if (status === 'succeeded') {
    return (
      <div className="text-center py-8">
        <CheckCircle className="w-16 h-16 text-status-success mx-auto mb-4" />
        <h3 className="text-xl font-bold text-text-primary mb-2">Payment Successful!</h3>
        <p className="text-text-secondary">Your order has been paid. Redirecting...</p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="p-4 rounded-xl bg-dark-100 border border-surface-border">
        <PaymentElement
          options={{
            layout: 'tabs',
          }}
        />
      </div>

      {status === 'error' && errorMessage && (
        <div className="flex items-center gap-2 p-3 rounded-lg bg-status-dangerBg border border-status-danger/30 text-status-danger text-sm">
          <XCircle className="w-4 h-4 shrink-0" />
          {errorMessage}
        </div>
      )}

      <div className="flex gap-3">
        <Button
          type="submit"
          variant="primary"
          className="flex-1 btn-lg"
          disabled={!stripe || !elements || status === 'processing'}
        >
          {status === 'processing' ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              Processing...
            </>
          ) : (
            <>
              <CreditCard className="w-5 h-5" />
              Pay {formattedAmount}
            </>
          )}
        </Button>
        <Button
          type="button"
          variant="ghost"
          onClick={onCancel}
          disabled={status === 'processing'}
        >
          Cancel
        </Button>
      </div>
    </form>
  );
}

export function StripePaymentForm({
  clientSecret,
  publishableKey,
  paymentIntentId,
  orderId,
  amount,
  currency,
  onSuccess,
  onCancel,
}: StripePaymentFormProps) {
  const stripePromise = getStripePromise(publishableKey);

  return (
    <Elements
      stripe={stripePromise}
      options={{
        clientSecret,
        appearance: {
          theme: 'night',
          variables: {
            colorPrimary: '#f97316',
            colorBackground: '#1a1a2e',
            colorText: '#e2e8f0',
            colorDanger: '#ef4444',
            borderRadius: '8px',
            fontFamily: 'inherit',
          },
          rules: {
            '.Input': {
              backgroundColor: '#0f0f23',
              border: '1px solid #2d2d4a',
            },
            '.Input:focus': {
              border: '1px solid #f97316',
              boxShadow: '0 0 0 1px #f97316',
            },
            '.Label': {
              color: '#94a3b8',
            },
          },
        },
      }}
    >
      <CheckoutForm
        orderId={orderId}
        paymentIntentId={paymentIntentId}
        amount={amount}
        currency={currency}
        onSuccess={onSuccess}
        onCancel={onCancel}
      />
    </Elements>
  );
}
