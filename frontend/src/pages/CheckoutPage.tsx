import { useState, useCallback, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { loadStripe } from '@stripe/stripe-js';
import {
  Elements,
  PaymentElement,
  useStripe,
  useElements,
} from '@stripe/react-stripe-js';
import { checkoutApi, shippingApi, cartApi } from '../api';
import type { CheckoutPaymentIntentResponse } from '../api/checkout';
import type { ShippingQuote, Address, Cart } from '../api/types';
import { useAuthStore } from '../store/authStore';
import { ErrorMessage } from '../components/ErrorMessage';
import { Input, Label, Button } from '../components/ui';
import { getApiErrorMessage } from '../utils/apiError';
import { formatCurrency } from '../utils/format';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import {
  ChevronLeft,
  ChevronRight,
  CreditCard,
  MapPin,
  User,
  Mail,
  Building2,
  Map as MapIcon,
  Hash,
  Globe,
  Shield,
  Lock,
  Truck,
  ShoppingBag,
  Check,
  Loader2,
  Package,
} from 'lucide-react';

/* ─── Schemas ──────────────────────────────────────────────────────── */

const addressSchema = z.object({
  name: z.string().optional(),
  line1: z.string().optional(),
  line2: z.string().optional(),
  city: z.string().optional(),
  state: z.string().optional(),
  postal_code: z.string().optional(),
  country: z.string().length(2).optional().or(z.literal('')),
}).optional();

function buildCheckoutSchema(isGuest: boolean) {
  return z.object({
    email: isGuest
      ? z.string().min(1, 'Email is required').email('Enter a valid email')
      : z.string().email().optional().or(z.literal('')),
    shipping_address: addressSchema,
    billing_address: addressSchema,
    same_as_shipping: z.boolean().optional(),
  });
}

type CheckoutFormData = z.infer<ReturnType<typeof buildCheckoutSchema>>;

/** Strip empty-string values from an address so we don't send blank fields. */
function cleanAddress(addr?: Record<string, unknown>): Address | undefined {
  if (!addr) return undefined;
  const cleaned = Object.fromEntries(
    Object.entries(addr).filter(([, v]) => typeof v === 'string' && v.trim() !== '')
  );
  return Object.keys(cleaned).length > 0 ? (cleaned as Address) : undefined;
}

/* ─── Stripe singleton ─────────────────────────────────────────────── */

const stripePromiseCache = new Map<string, ReturnType<typeof loadStripe>>();
function getStripePromise(key: string) {
  if (!stripePromiseCache.has(key)) stripePromiseCache.set(key, loadStripe(key));
  return stripePromiseCache.get(key)!;
}

/* ─── Step numbers ─────────────────────────────────────────────────── */

const STEPS = [
  { id: 1, label: 'Information', icon: User },
  { id: 2, label: 'Shipping', icon: Truck },
  { id: 3, label: 'Payment', icon: CreditCard },
] as const;

/* ─── Progress bar ─────────────────────────────────────────────────── */

function StepIndicator({ current }: { current: number }) {
  return (
    <div className="flex items-center justify-center gap-0 mb-10 max-w-xl mx-auto">
      {STEPS.map((step, idx) => {
        const done = current > step.id;
        const active = current === step.id;
        const Icon = step.icon;
        return (
          <div key={step.id} className="flex items-center flex-1 last:flex-none">
            <div className="flex flex-col items-center gap-1.5">
              <div
                className={`w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all ${
                  done
                    ? 'bg-accent border-accent text-white'
                    : active
                    ? 'border-accent text-accent bg-accent/10'
                    : 'border-dark-300 text-text-muted bg-dark-100'
                }`}
              >
                {done ? <Check className="w-5 h-5" /> : <Icon className="w-5 h-5" />}
              </div>
              <span
                className={`text-xs font-medium ${
                  active ? 'text-accent' : done ? 'text-text-primary' : 'text-text-muted'
                }`}
              >
                {step.label}
              </span>
            </div>
            {idx < STEPS.length - 1 && (
              <div
                className={`flex-1 h-0.5 mx-2 mt-[-18px] rounded-full transition-colors ${
                  done ? 'bg-accent' : 'bg-dark-300'
                }`}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}

/* ─── Inline Stripe payment form ───────────────────────────────────── */

function InlinePaymentForm({
  onReady,
}: {
  onReady: (helpers: { confirm: () => Promise<{ error?: string }> }) => void;
}) {
  const stripe = useStripe();
  const elements = useElements();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (stripe && elements && !ready) {
      setReady(true);
      onReady({
        confirm: async () => {
          const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
              return_url: `${window.location.origin}/checkout/success`,
            },
            redirect: 'if_required',
          });
          if (error) return { error: getApiErrorMessage(error, 'Payment failed.') };
          return {};
        },
      });
    }
  }, [stripe, elements, ready, onReady]);

  return (
    <div className="p-1">
      <PaymentElement options={{ layout: 'tabs' }} />
    </div>
  );
}

/* ─── Main component ───────────────────────────────────────────────── */

export function CheckoutPage() {
  const navigate = useNavigate();
  const isAuth = useAuthStore((s) => s.isAuthenticated());

  const [step, setStep] = useState(1);
  const [selectedQuote, setSelectedQuote] = useState<ShippingQuote | null>(null);
  const [sameAsShipping, setSameAsShipping] = useState(true);
  const [paymentIntent, setPaymentIntent] = useState<CheckoutPaymentIntentResponse | null>(null);
  const [paymentConfirm, setPaymentConfirm] = useState<{
    confirm: () => Promise<{ error?: string }>;
  } | null>(null);
  const [paymentError, setPaymentError] = useState('');

  /* ── Cart data ─────────────────────────────────────────────────── */

  const { data: cart, render } = useQueryWithUI({
    queryKey: ['cart'],
    queryFn: () => cartApi.show(),
    fallbackMessage: 'Failed to load cart',
  });

  const { data: shippingQuotes } = useQuery({
    queryKey: ['shipping-quotes'],
    queryFn: () => shippingApi.quotes(),
  });

  /* ── Form ──────────────────────────────────────────────────────── */

  const {
    register,
    handleSubmit,
    trigger,
    formState: { errors },
  } = useForm<CheckoutFormData>({
    resolver: zodResolver(buildCheckoutSchema(!isAuth)),
    defaultValues: {
      email: '',
      shipping_address: {},
      billing_address: {},
      same_as_shipping: true,
    },
  });

  /* ── Create PaymentIntent when entering step 3 ─────────────────── */

  const createPI = useMutation({
    mutationFn: () =>
      checkoutApi.createPaymentIntent(selectedQuote?.amount ?? 0),
    onSuccess: (res) => setPaymentIntent(res),
  });

  const goToPaymentStep = useCallback(() => {
    setStep(3);
    if (!paymentIntent) createPI.mutate();
  }, [paymentIntent, createPI]);

  /* ── Place order ───────────────────────────────────────────────── */

  const placeOrder = useMutation({
    mutationFn: async (formData: CheckoutFormData) => {
      // 1. Confirm Stripe payment first
      if (paymentConfirm) {
        const { error } = await paymentConfirm.confirm();
        if (error) throw new Error(error);
      }

      // 2. Build payload
      const p: Parameters<typeof checkoutApi.placeOrder>[0] = {};
      if (formData.email) p.email = formData.email;

      const shipping = cleanAddress(
        formData.shipping_address as Record<string, unknown>
      );
      if (shipping) p.shipping_address = shipping;

      const billing = sameAsShipping
        ? shipping
        : cleanAddress(formData.billing_address as Record<string, unknown>);
      if (billing) p.billing_address = billing;

      if (selectedQuote) {
        p.shipping_method_code = selectedQuote.code;
        p.shipping_method_name = selectedQuote.name;
        p.shipping_amount = selectedQuote.amount;
      }

      if (paymentIntent) {
        p.payment_intent_id = paymentIntent.payment_intent_id;
      }

      return checkoutApi.placeOrder(p);
    },
    onSuccess: (res) => {
      const orderId = res?.id;
      if (orderId) {
        navigate(`/checkout/success?order=${orderId}`, { state: { order: res } });
      }
    },
    onError: (err) => {
      setPaymentError(getApiErrorMessage(err, 'Checkout failed'));
    },
  });

  /* ── Step navigation ───────────────────────────────────────────── */

  const nextStep = useCallback(async () => {
    if (step === 1) {
      const fields: (keyof CheckoutFormData)[] = ['shipping_address'];
      if (!isAuth) fields.push('email');
      const ok = await trigger(fields);
      if (ok) setStep(2);
    } else if (step === 2) {
      goToPaymentStep();
    }
  }, [step, isAuth, trigger, goToPaymentStep]);

  /* ── Render guards ─────────────────────────────────────────────── */

  const ui = render();
  if (ui) return ui;
  if (!cart) return null;

  if (!cart.items || cart.items.length === 0) {
    return (
      <div className="min-h-screen">
        <div className="container-app py-20">
          <div className="card p-12 text-center max-w-lg mx-auto">
            <div className="w-20 h-20 mx-auto rounded-full bg-dark-200 flex items-center justify-center mb-6">
              <ShoppingBag className="w-10 h-10 text-text-muted" />
            </div>
            <h2 className="heading-sm text-text-primary mb-3">Your cart is empty</h2>
            <p className="text-text-muted mb-6">
              Add some items to your cart before checking out.
            </p>
            <Link to="/products" className="btn-primary btn-lg">
              Browse Products
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const items = cart.items;
  const total = cart.total_amount + (selectedQuote?.amount ?? 0);

  return (
    <div className="min-h-screen bg-dark-50">
      {/* Header */}
      <div className="bg-dark border-b border-surface-border">
        <div className="container-app py-5">
          <div className="flex items-center justify-between">
            <Link
              to="/cart"
              className="flex items-center gap-2 text-text-secondary hover:text-accent transition-colors"
            >
              <ChevronLeft className="w-5 h-5" />
              <span>Back to Cart</span>
            </Link>
            <div className="flex items-center gap-2">
              <Lock className="w-4 h-4 text-accent" />
              <span className="text-sm text-text-muted">Secure Checkout</span>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-10">
        <StepIndicator current={step} />

        <form
          onSubmit={handleSubmit((d) => placeOrder.mutate(d))}
          className="flex flex-col lg:flex-row gap-8"
        >
          {/* ── Left column: step content ────────────────────────── */}
          <div className="flex-1 space-y-6">
            {/* ── Step 1: Information ──────────────────────────── */}
            {step === 1 && (
              <>
                {/* Guest email */}
                {!isAuth && (
                  <div className="card p-6">
                    <SectionHeader icon={Mail} title="Contact Information" subtitle="We'll send your order confirmation here" />
                    <div>
                      <Label htmlFor="checkout-email">Email Address</Label>
                      <Input
                        id="checkout-email"
                        type="email"
                        {...register('email')}
                        autoComplete="email"
                        placeholder="your@email.com"
                        error={!!errors.email}
                      />
                      {errors.email && (
                        <p className="text-status-danger text-sm mt-1">{errors.email.message}</p>
                      )}
                    </div>
                  </div>
                )}

                {/* Shipping address */}
                <div className="card p-6">
                  <SectionHeader icon={MapPin} title="Shipping Address" subtitle="Where should we deliver your order?" />
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="sm:col-span-2">
                      <Label htmlFor="shipping-name">Full Name</Label>
                      <IconInput icon={User}>
                        <Input id="shipping-name" {...register('shipping_address.name')} autoComplete="shipping name" placeholder="John Doe" className="pl-10" />
                      </IconInput>
                    </div>
                    <div className="sm:col-span-2">
                      <Label htmlFor="shipping-line1">Address Line 1</Label>
                      <IconInput icon={Building2}>
                        <Input id="shipping-line1" {...register('shipping_address.line1')} autoComplete="shipping address-line1" placeholder="123 Main Street" className="pl-10" />
                      </IconInput>
                    </div>
                    <div className="sm:col-span-2">
                      <Label htmlFor="shipping-line2">Address Line 2 (Optional)</Label>
                      <Input id="shipping-line2" {...register('shipping_address.line2')} autoComplete="shipping address-line2" placeholder="Apt, Suite, Unit" />
                    </div>
                    <div>
                      <Label htmlFor="shipping-city">City</Label>
                      <IconInput icon={MapIcon}>
                        <Input id="shipping-city" {...register('shipping_address.city')} autoComplete="shipping address-level2" placeholder="New York" className="pl-10" />
                      </IconInput>
                    </div>
                    <div>
                      <Label htmlFor="shipping-state">State / Province</Label>
                      <Input id="shipping-state" {...register('shipping_address.state')} autoComplete="shipping address-level1" placeholder="NY" />
                    </div>
                    <div>
                      <Label htmlFor="shipping-postal">Postal Code</Label>
                      <IconInput icon={Hash}>
                        <Input id="shipping-postal" {...register('shipping_address.postal_code')} autoComplete="shipping postal-code" placeholder="10001" className="pl-10" />
                      </IconInput>
                    </div>
                    <div>
                      <Label htmlFor="shipping-country">Country Code</Label>
                      <IconInput icon={Globe}>
                        <Input id="shipping-country" {...register('shipping_address.country')} autoComplete="shipping country" placeholder="US" className="pl-10" />
                      </IconInput>
                    </div>
                  </div>
                </div>

                <div className="flex justify-end">
                  <Button type="button" variant="primary" className="btn-lg" onClick={nextStep}>
                    Continue to Shipping
                    <ChevronRight className="w-5 h-5" />
                  </Button>
                </div>
              </>
            )}

            {/* ── Step 2: Shipping ────────────────────────────── */}
            {step === 2 && (
              <>
                <div className="card p-6">
                  <SectionHeader icon={Truck} title="Shipping Method" subtitle="Choose how you'd like your order delivered" />
                  {shippingQuotes && shippingQuotes.length > 0 ? (
                    <div className="space-y-3">
                      {shippingQuotes.map((quote) => {
                        const isSelected = selectedQuote?.code === quote.code;
                        return (
                          <button
                            key={quote.code}
                            type="button"
                            onClick={() => setSelectedQuote(quote)}
                            className={`w-full flex items-center justify-between p-4 rounded-xl border text-left transition-all ${
                              isSelected
                                ? 'border-accent bg-accent/5 ring-1 ring-accent'
                                : 'border-surface-border hover:border-accent/50'
                            }`}
                          >
                            <div className="flex items-center gap-3">
                              <div
                                className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                  isSelected ? 'border-accent' : 'border-dark-300'
                                }`}
                              >
                                {isSelected && <div className="w-2.5 h-2.5 rounded-full bg-accent" />}
                              </div>
                              <span className="font-medium text-text-primary">{quote.name}</span>
                            </div>
                            <span className={`font-semibold ${quote.amount === 0 ? 'text-accent' : 'text-text-primary'}`}>
                              {quote.amount === 0 ? 'Free' : formatCurrency(quote.amount, quote.currency)}
                            </span>
                          </button>
                        );
                      })}
                    </div>
                  ) : (
                    <p className="text-text-muted text-sm">Free standard shipping on all orders.</p>
                  )}
                </div>

                <div className="flex justify-between">
                  <Button type="button" variant="ghost" onClick={() => setStep(1)}>
                    <ChevronLeft className="w-5 h-5" />
                    Back
                  </Button>
                  <Button type="button" variant="primary" className="btn-lg" onClick={nextStep}>
                    Continue to Payment
                    <ChevronRight className="w-5 h-5" />
                  </Button>
                </div>
              </>
            )}

            {/* ── Step 3: Payment & Review ────────────────────── */}
            {step === 3 && (
              <>
                {/* Payment form */}
                <div className="card p-6">
                  <SectionHeader icon={CreditCard} title="Payment" subtitle="All transactions are secure and encrypted" />

                  {createPI.isPending && (
                    <div className="flex items-center justify-center py-12 gap-3 text-text-muted">
                      <Loader2 className="w-6 h-6 animate-spin text-accent" />
                      <span>Initializing secure payment...</span>
                    </div>
                  )}

                  {createPI.isError && (
                    <ErrorMessage message={getApiErrorMessage(createPI.error, 'Failed to initialize payment')} />
                  )}

                  {paymentIntent && (
                    <Elements
                      stripe={getStripePromise(paymentIntent.stripe_publishable_key)}
                      options={{
                        clientSecret: paymentIntent.client_secret,
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
                            '.Input': { backgroundColor: '#0f0f23', border: '1px solid #2d2d4a' },
                            '.Input:focus': { border: '1px solid #f97316', boxShadow: '0 0 0 1px #f97316' },
                            '.Label': { color: '#94a3b8' },
                          },
                        },
                      }}
                    >
                      <InlinePaymentForm onReady={setPaymentConfirm} />
                    </Elements>
                  )}
                </div>

                {/* Billing address */}
                <div className="card p-6">
                  <SectionHeader icon={Building2} title="Billing Address" subtitle="Address for payment verification" />

                  <label className="flex items-center gap-3 mb-4 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={sameAsShipping}
                      onChange={(e) => setSameAsShipping(e.target.checked)}
                      className="w-5 h-5 rounded border-dark-300 bg-dark-100 text-accent focus:ring-accent focus:ring-offset-0"
                    />
                    <span className="text-sm text-text-secondary">Same as shipping address</span>
                  </label>

                  {!sameAsShipping && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                      <div className="sm:col-span-2">
                        <Label htmlFor="billing-name">Full Name</Label>
                        <Input id="billing-name" {...register('billing_address.name')} autoComplete="billing name" placeholder="John Doe" />
                      </div>
                      <div className="sm:col-span-2">
                        <Label htmlFor="billing-line1">Address Line 1</Label>
                        <Input id="billing-line1" {...register('billing_address.line1')} autoComplete="billing address-line1" placeholder="123 Main Street" />
                      </div>
                      <div className="sm:col-span-2">
                        <Label htmlFor="billing-line2">Address Line 2 (Optional)</Label>
                        <Input id="billing-line2" {...register('billing_address.line2')} autoComplete="billing address-line2" placeholder="Apt, Suite, Unit" />
                      </div>
                      <div>
                        <Label htmlFor="billing-city">City</Label>
                        <Input id="billing-city" {...register('billing_address.city')} autoComplete="billing address-level2" placeholder="New York" />
                      </div>
                      <div>
                        <Label htmlFor="billing-state">State / Province</Label>
                        <Input id="billing-state" {...register('billing_address.state')} autoComplete="billing address-level1" placeholder="NY" />
                      </div>
                      <div>
                        <Label htmlFor="billing-postal">Postal Code</Label>
                        <Input id="billing-postal" {...register('billing_address.postal_code')} autoComplete="billing postal-code" placeholder="10001" />
                      </div>
                      <div>
                        <Label htmlFor="billing-country">Country Code</Label>
                        <Input id="billing-country" {...register('billing_address.country')} autoComplete="billing country" placeholder="US" />
                      </div>
                    </div>
                  )}
                </div>

                {/* Order review */}
                <div className="card p-6">
                  <SectionHeader icon={Package} title="Order Review" subtitle="Please review before completing your purchase" />
                  <div className="space-y-3">
                    {items.map((item) => (
                      <div key={item.id} className="flex items-center gap-3">
                        <div className="w-14 h-14 rounded-lg bg-dark-100 shrink-0 overflow-hidden">
                          {item.product_image_url ? (
                            <img src={item.product_image_url} alt={item.product_name ?? ''} className="w-full h-full object-cover" />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-text-muted">
                              <ShoppingBag className="w-5 h-5" />
                            </div>
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-text-primary truncate">
                            {item.product_name ?? `Variant #${item.product_variant_id}`}
                          </p>
                          <p className="text-xs text-text-muted">Qty: {item.quantity}</p>
                        </div>
                        <span className="text-sm font-semibold text-text-primary">
                          {formatCurrency(item.unit_price_amount * item.quantity, item.unit_price_currency)}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Errors */}
                {(placeOrder.isError || paymentError) && (
                  <ErrorMessage
                    message={
                      (() => {
                        const base = paymentError || getApiErrorMessage(placeOrder.error, 'Checkout failed');
                        const err = placeOrder.error as { response?: { data?: { error_code?: string } } } | undefined;
                        if (placeOrder.isError && err?.response?.data?.error_code === 'STOCK_RESERVATION_FAILED') {
                          return `${base} Your reservation may have expired. Please review your cart and place the order again.`;
                        }
                        return base;
                      })()
                    }
                  />
                )}

                <div className="flex justify-between">
                  <Button type="button" variant="ghost" onClick={() => setStep(2)}>
                    <ChevronLeft className="w-5 h-5" />
                    Back
                  </Button>
                  <Button
                    type="submit"
                    variant="primary"
                    className="btn-lg"
                    disabled={placeOrder.isPending || !paymentConfirm}
                  >
                    {placeOrder.isPending ? (
                      <>
                        <Loader2 className="w-5 h-5 animate-spin" />
                        Processing...
                      </>
                    ) : (
                      <>
                        <Lock className="w-5 h-5" />
                        Pay {formatCurrency(total, cart.currency)}
                      </>
                    )}
                  </Button>
                </div>
              </>
            )}
          </div>

          {/* ── Right column: sidebar ─────────────────────────────── */}
          <Sidebar
            cart={cart}
            selectedQuote={selectedQuote}
            total={total}
          />
        </form>
      </div>
    </div>
  );
}

/* ─── Reusable sub-components ──────────────────────────────────────── */

function SectionHeader({
  icon: Icon,
  title,
  subtitle,
}: {
  icon: React.ElementType;
  title: string;
  subtitle: string;
}) {
  return (
    <div className="flex items-center gap-3 mb-6">
      <div className="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
        <Icon className="w-5 h-5 text-accent" />
      </div>
      <div>
        <h2 className="font-semibold text-text-primary">{title}</h2>
        <p className="text-sm text-text-muted">{subtitle}</p>
      </div>
    </div>
  );
}

function IconInput({
  icon: Icon,
  children,
}: {
  icon: React.ElementType;
  children: React.ReactNode;
}) {
  return (
    <div className="relative">
      <Icon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none" />
      {children}
    </div>
  );
}

function Sidebar({
  cart,
  selectedQuote,
  total,
}: {
  cart: Cart;
  selectedQuote: ShippingQuote | null;
  total: number;
}) {
  const items = cart.items ?? [];
  return (
    <div className="lg:w-96 shrink-0">
      <div className="card p-6 sticky top-24">
        <h2 className="font-bold text-lg text-text-primary mb-6">Order Summary</h2>

        {/* Items */}
        <div className="space-y-4 mb-6 max-h-64 overflow-y-auto">
          {items.map((item) => (
            <div key={item.id} className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-lg bg-dark-100 shrink-0 overflow-hidden">
                {item.product_image_url ? (
                  <img src={item.product_image_url} alt={item.product_name ?? ''} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-text-muted">
                    <ShoppingBag className="w-5 h-5" />
                  </div>
                )}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-text-primary truncate">
                  {item.product_name ?? `Variant #${item.product_variant_id}`}
                </p>
                <p className="text-xs text-text-muted">Qty: {item.quantity}</p>
              </div>
              <span className="text-sm font-medium text-text-primary">
                {formatCurrency(item.unit_price_amount * item.quantity, item.unit_price_currency)}
              </span>
            </div>
          ))}
        </div>

        <div className="divider" />

        {/* Totals */}
        <div className="space-y-3 py-4 text-sm">
          <div className="flex justify-between text-text-secondary">
            <span>Subtotal</span>
            <span>{formatCurrency(cart.subtotal_amount, cart.currency)}</span>
          </div>
          {cart.discount_amount > 0 && (
            <div className="flex justify-between text-status-success">
              <span>Discount</span>
              <span>-{formatCurrency(cart.discount_amount, cart.currency)}</span>
            </div>
          )}
          <div className="flex justify-between text-text-secondary">
            <span>Shipping</span>
            <span className={selectedQuote && selectedQuote.amount > 0 ? 'text-text-primary' : 'text-accent'}>
              {selectedQuote
                ? selectedQuote.amount === 0
                  ? 'Free'
                  : formatCurrency(selectedQuote.amount, selectedQuote.currency)
                : 'Free'}
            </span>
          </div>
        </div>

        <div className="divider" />

        <div className="flex justify-between items-baseline py-4">
          <span className="font-semibold text-text-primary">Total</span>
          <span className="text-2xl font-bold text-accent">
            {formatCurrency(total, cart.currency)}
          </span>
        </div>

        <p className="text-xs text-text-muted text-center py-2">
          Your items are reserved for 30 minutes.
        </p>

        {/* Trust badges */}
        <div className="mt-4 pt-4 border-t border-surface-border">
          <div className="flex items-center justify-center gap-6 text-xs text-text-muted">
            <div className="flex items-center gap-1.5">
              <Shield className="w-4 h-4 text-accent" />
              SSL Encrypted
            </div>
            <div className="flex items-center gap-1.5">
              <Lock className="w-4 h-4 text-accent" />
              Secure Payment
            </div>
            <div className="flex items-center gap-1.5">
              <Check className="w-4 h-4 text-accent" />
              Verified
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
