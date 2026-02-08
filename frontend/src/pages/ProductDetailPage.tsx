import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { catalogApi, cartApi, wishlistApi } from '../api';
import { useAuthStore } from '../store/authStore';
import { getApiErrorMessage } from '../utils/apiError';
import { formatCurrency } from '../utils/format';
import { useToastStore } from '../store/toastStore';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import { EmptyState } from '../components/EmptyState';
import { ErrorMessage } from '../components/ErrorMessage';
import { SEO } from '../components/SEO';
import {
  ShoppingCart,
  Heart,
  Star,
  ChevronLeft,
  ChevronRight,
  Minus,
  Plus,
  Shield,
  RotateCcw,
  Check,
  Share2,
  Zap,
} from 'lucide-react';

export function ProductDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const queryClient = useQueryClient();
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const [quantity, setQuantity] = useState(1);
  const [variantId, setVariantId] = useState<number | null>(null);
  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [isAddedToCart, setIsAddedToCart] = useState(false);

  const { data: product, render } = useQueryWithUI({
    queryKey: ['product', slug],
    queryFn: () => catalogApi.product(slug!),
    fallbackMessage: 'Product not found',
    enabled: !!slug,
  });

  const { data: reviewsRes } = useQuery({
    queryKey: ['reviews', slug],
    queryFn: () => catalogApi.reviews(slug!),
    enabled: !!slug,
  });

  const toast = useToastStore((s) => s.add);

  const addToCart = useMutation({
    mutationFn: () => {
      const vid = variantId ?? product?.variants?.[0]?.id;
      if (!vid) throw new Error('No variant selected');
      const qty = maxQty > 0 ? Math.min(quantity, maxQty) : quantity;
      return cartApi.addItem(vid, qty);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
      toast('Added to cart', 'success');
      setIsAddedToCart(true);
      setTimeout(() => setIsAddedToCart(false), 2000);
    },
  });

  const addToWishlist = useMutation({
    mutationFn: () => {
      const vid = variantId ?? product?.variants?.[0]?.id;
      if (!vid) throw new Error('No variant selected');
      return wishlistApi.addItem(vid);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['wishlist'] });
      toast('Added to wishlist', 'success');
    },
  });

  const reviews = reviewsRes ?? [];
  const averageRating = reviews.length > 0
    ? reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length
    : 0;

  const defaultVariantId = product?.variants?.[0]?.id;
  const selectedVariantId = variantId ?? defaultVariantId ?? null;
  const selectedVariant = product?.variants?.find((v) => v.id === selectedVariantId) ?? product?.variants?.[0];
  const availableQty = selectedVariant?.available_quantity ?? 0;
  const inStock = availableQty > 0;
  const maxQty = inStock ? Math.min(99, availableQty) : 0;
  const effectiveQuantity = maxQty > 0 ? Math.min(quantity, maxQty) : quantity;
  const incrementQty = () => setQuantity(q => Math.min(maxQty, q + 1));
  const decrementQty = () => setQuantity(q => Math.max(1, q - 1));

  const copyLink = () => {
    navigator.clipboard.writeText(window.location.href);
    toast('Link copied to clipboard', 'success');
  };

  if (!slug) return null;
  const ui = render();
  if (ui) return ui;
  if (!product) return null;

  const images = product.main_image_url ? [product.main_image_url] : [];

  return (
    <div className="min-h-screen">
      <SEO
        title={product.name}
        description={product.seo_description || product.description || undefined}
        image={product.main_image_url || undefined}
        type="product"
      />

      {/* Breadcrumb */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-4">
          <div className="flex items-center gap-2 text-sm text-text-muted">
            <Link to="/" className="hover:text-accent transition-colors">Home</Link>
            <span>/</span>
            <Link to="/products" className="hover:text-accent transition-colors">Products</Link>
            <span>/</span>
            <span className="text-text-primary truncate max-w-[200px]">{product.name}</span>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        <div className="grid lg:grid-cols-2 gap-8 lg:gap-12">
          {/* Image Gallery */}
          <div className="space-y-4">
            <div className="card overflow-hidden aspect-square bg-dark-100 relative group">
              {images.length > 0 ? (
                <img
                  src={images[selectedImageIndex]}
                  alt={product.name}
                  className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <div className="text-center">
                    <div className="w-20 h-20 mx-auto rounded-full bg-dark-200 flex items-center justify-center mb-3">
                      <Zap className="w-10 h-10 text-text-muted" />
                    </div>
                    <p className="text-text-muted">No image available</p>
                  </div>
                </div>
              )}

              {/* Image navigation */}
              {images.length > 1 && (
                <>
                  <button
                    type="button"
                    onClick={() => setSelectedImageIndex((i) => (i - 1 + images.length) % images.length)}
                    className="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-dark/80 backdrop-blur-sm flex items-center justify-center text-text-secondary hover:text-accent transition-colors opacity-0 group-hover:opacity-100"
                    aria-label="Previous image"
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>
                  <button
                    type="button"
                    onClick={() => setSelectedImageIndex((i) => (i + 1) % images.length)}
                    className="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-dark/80 backdrop-blur-sm flex items-center justify-center text-text-secondary hover:text-accent transition-colors opacity-0 group-hover:opacity-100"
                    aria-label="Next image"
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </>
              )}
            </div>

            {/* Thumbnails */}
            {images.length > 1 && (
              <div className="flex gap-3 overflow-x-auto pb-2">
                {images.map((img, i) => (
                  <button
                    key={i}
                    type="button"
                    onClick={() => setSelectedImageIndex(i)}
                    className={`w-20 h-20 rounded-xl overflow-hidden shrink-0 transition-all ${
                      i === selectedImageIndex
                        ? 'ring-2 ring-accent shadow-glow-sm'
                        : 'opacity-60 hover:opacity-100'
                    }`}
                    aria-label={`View image ${i + 1} of ${images.length}`}
                  >
                    <img src={img} alt="" className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Product Info */}
          <div className="space-y-6">
            {/* Category & Rating */}
            <div className="flex flex-wrap items-center gap-4">
              {product.categories?.[0] && (
                <Link
                  to={`/products?category=${product.categories[0].id}`}
                  className="badge-accent"
                >
                  {product.categories[0].name}
                </Link>
              )}
              {reviews.length > 0 && (
                <div className="flex items-center gap-2">
                  <div className="flex items-center">
                    {[...Array(5)].map((_, i) => (
                      <Star
                        key={i}
                        className={`w-4 h-4 ${
                          i < Math.round(averageRating)
                            ? 'text-yellow-400 fill-yellow-400'
                            : 'text-dark-300'
                        }`}
                      />
                    ))}
                  </div>
                  <span className="text-sm text-text-secondary">
                    ({reviews.length} reviews)
                  </span>
                </div>
              )}
            </div>

            {/* Title */}
            <h1 className="heading-lg text-text-primary">{product.name}</h1>

            {/* Price */}
            {(() => {
              const variant = product.variants?.find((v) => v.id === selectedVariantId) ?? product.variants?.[0];
              const price = variant?.prices?.find((p) => p.currency === 'USD') ?? variant?.prices?.[0];
              if (!price) return null;
              const amount = typeof price.amount === 'string' ? parseFloat(price.amount) : price.amount;
              const currency = price.currency ?? 'USD';
              return (
                <p className="text-2xl font-bold text-accent">
                  {formatCurrency(Number.isNaN(amount) ? 0 : amount, currency)}
                </p>
              );
            })()}

            {/* Stock status */}
            {selectedVariant && (
              <div className="flex items-center gap-2">
                <span
                  className={`inline-flex items-center gap-1.5 text-sm font-medium ${
                    inStock ? 'text-status-success' : 'text-status-danger'
                  }`}
                >
                  <span
                    className={`w-2 h-2 rounded-full ${inStock ? 'bg-status-success' : 'bg-status-danger'}`}
                    aria-hidden
                  />
                  {inStock
                    ? (availableQty >= 10
                        ? 'In stock'
                        : `Only ${availableQty} left in stock`)
                    : 'Out of stock'}
                </span>
              </div>
            )}

            {/* Description */}
            {product.description && (
              <p className="text-text-secondary leading-relaxed">{product.description}</p>
            )}

            {/* Variant selector */}
            {product.variants && product.variants.length > 1 && (
              <div>
                <label className="label">Select Variant</label>
                <div className="flex flex-wrap gap-2">
                  {product.variants.map((v) => {
                    const vQty = v.available_quantity ?? 0;
                    const vInStock = vQty > 0;
                    return (
                      <button
                        key={v.id}
                        type="button"
                        onClick={() => {
                          setVariantId(v.id);
                          const vMax = Math.min(99, v.available_quantity ?? 99);
                          setQuantity(prev => (vMax > 0 ? Math.min(prev, vMax) : prev));
                        }}
                        disabled={!vInStock}
                        className={`px-4 py-2 rounded-xl border text-sm font-medium transition-all ${
                          !vInStock
                            ? 'border-surface-border text-text-muted cursor-not-allowed opacity-70'
                            : selectedVariantId === v.id
                              ? 'border-accent bg-accent/10 text-accent'
                              : 'border-surface-border text-text-secondary hover:border-accent/50 hover:text-text-primary'
                        }`}
                      >
                        <span>{v.name || v.sku}</span>
                        {typeof v.available_quantity === 'number' && (
                          <span className={vInStock ? 'text-text-muted ml-1' : 'text-status-danger ml-1'}>
                            {vInStock ? `(${vQty >= 10 ? 'In stock' : `${vQty} left`})` : '(Out of stock)'}
                          </span>
                        )}
                      </button>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Quantity & Add to cart */}
            <div className="flex flex-col sm:flex-row gap-4">
              {/* Quantity */}
              <div className="flex items-center">
                <label htmlFor="product-quantity" className="label mr-4">Quantity</label>
                <div className="flex items-center rounded-xl border border-surface-border overflow-hidden">
                  <button
                    type="button"
                    onClick={decrementQty}
                    disabled={effectiveQuantity <= 1}
                    className="w-10 h-10 flex items-center justify-center text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors disabled:opacity-50"
                    aria-label="Decrease quantity"
                  >
                    <Minus className="w-4 h-4" />
                  </button>
                  <input
                    id="product-quantity"
                    type="number"
                    min={1}
                    max={maxQty || 99}
                    value={effectiveQuantity}
                    onChange={(e) => setQuantity(Math.max(1, Math.min(maxQty || 99, Number(e.target.value) || 1)))}
                    className="w-16 h-10 text-center bg-transparent border-x border-surface-border text-text-primary focus:outline-none"
                    aria-label="Quantity"
                  />
                  <button
                    type="button"
                    onClick={incrementQty}
                    disabled={effectiveQuantity >= (maxQty || 99)}
                    className="w-10 h-10 flex items-center justify-center text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors disabled:opacity-50"
                    aria-label="Increase quantity"
                  >
                    <Plus className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            {/* Action buttons */}
            <div className="flex flex-col sm:flex-row gap-3">
              <button
                type="button"
                disabled={addToCart.isPending || !selectedVariantId || !inStock}
                onClick={() => addToCart.mutate()}
                className={`btn-primary btn-lg flex-1 ${isAddedToCart ? 'bg-status-success' : ''} ${!inStock ? 'opacity-60 cursor-not-allowed' : ''}`}
              >
                {isAddedToCart ? (
                  <>
                    <Check className="w-5 h-5" />
                    Added to Cart
                  </>
                ) : addToCart.isPending ? (
                  'Adding...'
                ) : (
                  <>
                    <ShoppingCart className="w-5 h-5" />
                    Add to Cart
                  </>
                )}
              </button>
              {isAuth && (
                <button
                  type="button"
                  onClick={() => addToWishlist.mutate()}
                  disabled={addToWishlist.isPending}
                  className="btn-secondary btn-lg"
                >
                  <Heart className="w-5 h-5" />
                  <span className="sm:hidden lg:inline">Wishlist</span>
                </button>
              )}
              <button type="button" onClick={copyLink} className="btn-secondary btn-icon btn-lg hidden sm:flex" aria-label="Share product">
                <Share2 className="w-5 h-5" />
              </button>
            </div>

            {addToCart.isError && (
              <ErrorMessage message={getApiErrorMessage(addToCart.error, 'Failed to add to cart')} />
            )}

            {/* Features */}
            <div className="grid grid-cols-3 gap-4 pt-6 border-t border-surface-border">
              <div className="text-center">
                <div className="w-10 h-10 mx-auto rounded-xl bg-accent/10 flex items-center justify-center mb-2">
                  <Zap className="w-5 h-5 text-accent" />
                </div>
                <p className="text-xs text-text-muted">Instant Delivery</p>
              </div>
              <div className="text-center">
                <div className="w-10 h-10 mx-auto rounded-xl bg-accent/10 flex items-center justify-center mb-2">
                  <Shield className="w-5 h-5 text-accent" />
                </div>
                <p className="text-xs text-text-muted">Secure Payment</p>
              </div>
              <div className="text-center">
                <div className="w-10 h-10 mx-auto rounded-xl bg-accent/10 flex items-center justify-center mb-2">
                  <RotateCcw className="w-5 h-5 text-accent" />
                </div>
                <p className="text-xs text-text-muted">Easy Returns</p>
              </div>
            </div>
          </div>
        </div>

        {/* Reviews Section */}
        <section className="mt-12 lg:mt-16">
          <div className="flex items-center justify-between mb-6">
            <h2 className="heading-md text-text-primary">Customer Reviews</h2>
            {isAuth && (
              <Link to={`/products/${slug}/review`} className="btn-secondary">
                Write a Review
              </Link>
            )}
          </div>

          {reviews.length === 0 ? (
            <div className="card p-8 text-center">
              <EmptyState
                message="No reviews yet"
                description="Be the first to share your experience"
                icon={<Star className="w-8 h-8 text-text-muted" />}
                action={isAuth ? (
                  <Link to={`/products/${slug}/review`} className="btn-primary">
                    Write a Review
                  </Link>
                ) : undefined}
                className="!p-0"
              />
            </div>
          ) : (
            <div className="grid gap-4">
              {reviews.map((review) => (
                <div key={review.id} className="card p-6">
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center text-accent font-semibold shrink-0">
                      {(review.user_name?.[0] ?? 'U').toUpperCase()}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2 mb-1">
                        <span className="font-medium text-text-primary text-sm">
                          {review.user_name ?? 'User'}
                        </span>
                        {review.created_at && (
                          <span className="text-xs text-text-muted">
                            {new Date(review.created_at).toLocaleDateString('en-US', {
                              year: 'numeric', month: 'short', day: 'numeric',
                            })}
                          </span>
                        )}
                      </div>
                      <div className="flex items-center gap-2 mb-2">
                        <div className="flex items-center">
                          {[...Array(5)].map((_, i) => (
                            <Star
                              key={i}
                              className={`w-4 h-4 ${
                                i < review.rating
                                  ? 'text-yellow-400 fill-yellow-400'
                                  : 'text-dark-300'
                              }`}
                            />
                          ))}
                        </div>
                        {review.title && (
                          <span className="font-medium text-text-primary">{review.title}</span>
                        )}
                      </div>
                      {review.body && (
                        <p className="text-text-secondary text-sm">{review.body}</p>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}
