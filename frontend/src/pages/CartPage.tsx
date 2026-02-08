import { Link } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { cartApi } from '../api';
import { getApiErrorMessage } from '../utils/apiError';
import { formatCurrency } from '../utils/format';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import { EmptyState } from '../components/EmptyState';
import { ErrorMessage } from '../components/ErrorMessage';
import { SEO } from '../components/SEO';
import {
  ShoppingCart,
  Trash2,
  Plus,
  Minus,
  Tag,
  X,
  ArrowRight,
  ShoppingBag,
  ArrowLeft,
  Shield,
  Truck,
  Check,
} from 'lucide-react';

export function CartPage() {
  const queryClient = useQueryClient();
  const [couponCode, setCouponCode] = useState('');
  const [couponApplied, setCouponApplied] = useState(false);

  const { data: cart, render } = useQueryWithUI({
    queryKey: ['cart'],
    queryFn: () => cartApi.show(),
    fallbackMessage: 'Failed to load cart',
  });

  const applyCoupon = useMutation({
    mutationFn: (code: string) => cartApi.applyCoupon(code),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
      setCouponApplied(true);
      setTimeout(() => setCouponApplied(false), 2000);
    },
  });

  const removeCoupon = useMutation({
    mutationFn: () => cartApi.removeCoupon(),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['cart'] }),
  });

  const updateItem = useMutation({
    mutationFn: ({ itemId, quantity }: { itemId: number; quantity: number }) =>
      cartApi.updateItem(itemId, quantity),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['cart'] }),
  });

  const removeItem = useMutation({
    mutationFn: (itemId: number) => cartApi.removeItem(itemId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['cart'] }),
  });

  const ui = render();
  if (ui) return ui;
  if (!cart) return null;

  const items = cart.items ?? [];
  const hasOutOfStock = items.some((i) => typeof i.available_quantity === 'number' && i.available_quantity === 0);

  return (
    <div className="min-h-screen">
      <SEO title="Shopping Cart" noIndex />

      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <div className="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link to="/" className="hover:text-accent transition-colors">Home</Link>
            <span>/</span>
            <span className="text-text-primary">Shopping Cart</span>
          </div>
          <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
              <h1 className="heading-lg text-text-primary">Shopping Cart</h1>
              <p className="text-text-secondary mt-2">
                {items.length === 0
                  ? 'Your cart is empty'
                  : `${items.length} ${items.length === 1 ? 'item' : 'items'} in your cart`}
              </p>
            </div>
            <Link to="/products" className="btn-ghost">
              <ArrowLeft className="w-4 h-4" />
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        {items.length === 0 ? (
          <div className="card p-12 text-center max-w-lg mx-auto">
            <EmptyState
              message="Your cart is empty"
              description="Looks like you haven't added any items to your cart yet. Start shopping to fill it up!"
              icon={<ShoppingCart className="w-8 h-8 text-text-muted" />}
              action={
                <Link to="/products" className="btn-primary btn-lg inline-flex items-center gap-2">
                  <ShoppingCart className="w-5 h-5" />
                  Start Shopping
                </Link>
              }
              className="!p-0"
            />
          </div>
        ) : (
          <div className="flex flex-col lg:flex-row gap-8">
            {/* Cart items */}
            <div className="flex-1 min-w-0">
              <div className="card overflow-hidden">
                {updateItem.isError && (
                  <div className="px-6 py-3 border-b border-surface-border">
                    <ErrorMessage message={getApiErrorMessage(updateItem.error, 'Failed to update quantity')} />
                  </div>
                )}
                {/* Header */}
                <div className="hidden sm:grid sm:grid-cols-12 gap-4 px-6 py-4 bg-dark-100 border-b border-surface-border text-xs uppercase tracking-wider text-text-muted font-semibold">
                  <div className="col-span-6">Product</div>
                  <div className="col-span-2 text-center">Quantity</div>
                  <div className="col-span-2 text-right">Price</div>
                  <div className="col-span-2 text-right">Total</div>
                </div>

                {/* Items */}
                <ul className="divide-y divide-surface-border">
                  {items.map((item) => {
                    const availableQty = item.available_quantity ?? null;
                    const maxQty = availableQty != null ? Math.min(99, availableQty) : 99;
                    const inStock = availableQty == null || availableQty > 0;
                    return (
                    <li key={item.id} className="p-4 sm:p-6">
                      <div className="sm:grid sm:grid-cols-12 sm:gap-4 sm:items-center">
                        {/* Product info */}
                        <div className="col-span-6 flex items-start gap-4 mb-4 sm:mb-0">
                          <Link
                            to={item.product_slug ? `/products/${item.product_slug}` : '#'}
                            className="w-20 h-20 rounded-xl bg-dark-100 shrink-0 overflow-hidden"
                          >
                            {item.product_image_url ? (
                              <img src={item.product_image_url} alt={item.product_name ?? ''} className="w-full h-full object-cover" />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center text-text-muted">
                                <ShoppingBag className="w-8 h-8" />
                              </div>
                            )}
                          </Link>
                          <div className="flex-1 min-w-0">
                            <Link to={item.product_slug ? `/products/${item.product_slug}` : '#'} className="font-semibold text-text-primary hover:text-accent transition-colors">
                              {item.product_name ?? `Variant #${item.product_variant_id}`}
                            </Link>
                            {item.variant_name && (
                              <p className="text-xs text-text-muted mt-0.5">{item.variant_name}</p>
                            )}
                            <p className="text-sm text-text-muted mt-1">
                              Unit price: {formatCurrency(item.unit_price_amount, item.unit_price_currency)}
                            </p>
                            {availableQty != null && (
                              <p className={`text-xs font-medium mt-1 ${inStock ? 'text-status-success' : 'text-status-danger'}`}>
                                {inStock ? (availableQty >= 10 ? 'In stock' : `Only ${availableQty} left`) : 'Out of stock'}
                              </p>
                            )}
                            <button
                              onClick={() => removeItem.mutate(item.id)}
                              disabled={removeItem.isPending}
                              className="inline-flex items-center gap-1.5 text-sm text-status-danger hover:text-red-400 mt-2 sm:hidden transition-colors"
                            >
                              <Trash2 className="w-4 h-4" />
                              Remove
                            </button>
                          </div>
                        </div>

                        {/* Quantity */}
                        <div className="col-span-2 flex items-center justify-center mb-4 sm:mb-0">
                          <div className="flex items-center rounded-xl border border-surface-border overflow-hidden">
                            <button
                              type="button"
                              aria-label="Decrease quantity"
                              onClick={() =>
                                updateItem.mutate({
                                  itemId: item.id,
                                  quantity: Math.max(1, item.quantity - 1),
                                })
                              }
                              disabled={item.quantity <= 1}
                              className="w-8 h-8 flex items-center justify-center text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors disabled:opacity-50"
                            >
                              <Minus className="w-3 h-3" />
                            </button>
                            <span className="w-10 text-center text-sm font-medium text-text-primary">
                              {item.quantity}
                            </span>
                            <button
                              type="button"
                              aria-label="Increase quantity"
                              onClick={() =>
                                updateItem.mutate({
                                  itemId: item.id,
                                  quantity: Math.min(maxQty, item.quantity + 1),
                                })
                              }
                              disabled={item.quantity >= maxQty}
                              className="w-8 h-8 flex items-center justify-center text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors disabled:opacity-50"
                            >
                              <Plus className="w-3 h-3" />
                            </button>
                          </div>
                        </div>

                        {/* Price */}
                        <div className="col-span-2 text-right hidden sm:block">
                          <span className="text-text-secondary">
                            {formatCurrency(item.unit_price_amount, item.unit_price_currency)}
                          </span>
                        </div>

                        {/* Total */}
                        <div className="col-span-2 flex items-center justify-between sm:justify-end gap-4">
                          <span className="font-semibold text-accent">
                            {formatCurrency(item.unit_price_amount * item.quantity, item.unit_price_currency)}
                          </span>
                          <button
                            type="button"
                            aria-label="Remove item"
                            onClick={() => removeItem.mutate(item.id)}
                            disabled={removeItem.isPending}
                            className="hidden sm:flex w-8 h-8 items-center justify-center rounded-lg text-text-muted hover:text-status-danger hover:bg-status-dangerBg transition-colors"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </li>
                  );
                  })}
                </ul>
              </div>

              {/* Coupon */}
              <div className="card p-6 mt-6">
                <h3 className="font-semibold text-text-primary mb-4 flex items-center gap-2">
                  <Tag className="w-5 h-5 text-accent" />
                  Have a coupon?
                </h3>
                <div className="flex flex-col sm:flex-row gap-3">
                  <div className="flex-1 relative">
                    <input
                      type="text"
                      placeholder="Enter coupon code"
                      value={couponCode}
                      onChange={(e) => setCouponCode(e.target.value)}
                      className="input pr-10"
                    />
                    {couponCode && (
                      <button
                        type="button"
                        aria-label="Clear coupon code"
                        onClick={() => setCouponCode('')}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  <button
                    type="button"
                    onClick={() => couponCode && applyCoupon.mutate(couponCode)}
                    disabled={!couponCode || applyCoupon.isPending}
                    className={`btn-secondary ${couponApplied ? 'bg-status-success text-white border-status-success' : ''}`}
                  >
                    {couponApplied ? (
                      <>
                        <Check className="w-4 h-4" />
                        Applied
                      </>
                    ) : applyCoupon.isPending ? (
                      'Applying...'
                    ) : (
                      'Apply Coupon'
                    )}
                  </button>
                </div>

                {cart.applied_coupon && (
                  <div className="mt-4 flex items-center justify-between p-3 rounded-xl bg-status-successBg border border-status-success/30">
                    <div className="flex items-center gap-2">
                      <Check className="w-4 h-4 text-status-success" />
                      <span className="font-medium text-status-success">
                        {cart.applied_coupon.code}
                      </span>
                      <span className="text-sm text-text-secondary">
                        saves {formatCurrency(cart.applied_coupon.discount_amount, cart.currency)}
                      </span>
                    </div>
                    <button
                      type="button"
                      aria-label="Remove coupon"
                      onClick={() => removeCoupon.mutate()}
                      disabled={removeCoupon.isPending}
                      className="text-text-muted hover:text-status-danger transition-colors"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </div>
                )}

                {applyCoupon.isError && (
                  <div className="mt-3">
                    <ErrorMessage message={getApiErrorMessage(applyCoupon.error, 'Failed to apply coupon')} />
                  </div>
                )}
              </div>
            </div>

            {/* Order summary */}
            <div className="lg:w-96 shrink-0">
              <div className="card p-6 sticky top-24">
                <h2 className="font-bold text-lg text-text-primary mb-6">Order Summary</h2>

                <div className="space-y-3 text-sm">
                  <div className="flex justify-between text-text-secondary">
                    <span>Subtotal ({items.length} items)</span>
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
                    <span className="text-accent">Free</span>
                  </div>
                </div>

                <div className="divider my-4" />

                <div className="flex justify-between items-baseline">
                  <span className="font-semibold text-text-primary">Total</span>
                  <span className="text-2xl font-bold text-accent">
                    {formatCurrency(cart.total_amount, cart.currency)}
                  </span>
                </div>

                {hasOutOfStock ? (
                  <div className="mt-6 space-y-2">
                    <span className="btn-primary btn-lg w-full mt-6 inline-flex items-center justify-center gap-2 opacity-60 cursor-not-allowed pointer-events-none">
                      Proceed to Checkout
                      <ArrowRight className="w-5 h-5" />
                    </span>
                    <p className="text-sm text-text-muted text-center">Remove out-of-stock items to proceed.</p>
                  </div>
                ) : (
                  <Link to="/checkout" className="btn-primary btn-lg w-full mt-6 inline-flex items-center justify-center gap-2">
                    Proceed to Checkout
                    <ArrowRight className="w-5 h-5" />
                  </Link>
                )}

                {/* Trust badges */}
                <div className="mt-6 pt-6 border-t border-surface-border">
                  <div className="flex items-center justify-center gap-4 text-xs text-text-muted">
                    <div className="flex items-center gap-1.5">
                      <Shield className="w-4 h-4 text-accent" />
                      Secure
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Truck className="w-4 h-4 text-accent" />
                      Fast Delivery
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
