import { Link } from 'react-router-dom';
import { ShoppingCart, Heart, Star } from 'lucide-react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { cartApi, wishlistApi } from '../api';
import { useAuthStore } from '../store/authStore';
import { useToastStore } from '../store/toastStore';
import { formatCurrency } from '../utils/format';
import { getApiErrorMessage } from '../utils/apiError';
import { OptimizedImage } from './OptimizedImage';

interface ProductCardProps {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  main_image_url: string | null;
  price?: number;
  originalPrice?: number;
  currency?: string;
  rating?: number;
  reviewCount?: number;
  variantId?: number;
  isNew?: boolean;
  isSale?: boolean;
  /** Stock validation: hide or disable add-to-cart when false */
  inStock?: boolean;
  /** Available quantity (for "X left" or validation) */
  availableQuantity?: number;
}

export function ProductCard({
  slug,
  name,
  description,
  main_image_url,
  price,
  originalPrice,
  currency = 'USD',
  rating = 0,
  reviewCount = 0,
  variantId,
  isNew = false,
  isSale = false,
  inStock = true,
  availableQuantity = 0,
}: ProductCardProps) {
  const queryClient = useQueryClient();
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const toast = useToastStore((s) => s.add);

  const addToCart = useMutation({
    mutationFn: () => {
      if (!variantId) throw new Error('No variant');
      return cartApi.addItem(variantId, 1);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
      toast('Added to cart', 'success');
    },
    onError: (err) => {
      toast(getApiErrorMessage(err, 'Failed to add to cart'), 'error');
    },
  });

  const addToWishlist = useMutation({
    mutationFn: () => {
      if (!variantId) throw new Error('No variant');
      return wishlistApi.addItem(variantId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['wishlist'] });
      toast('Added to wishlist', 'success');
    },
    onError: (err) => {
      toast(getApiErrorMessage(err, 'Failed to add to wishlist'), 'error');
    },
  });

  const discount = originalPrice && price ? Math.round((1 - price / originalPrice) * 100) : 0;

  return (
    <div className="group relative">
      <Link
        to={`/products/${slug}`}
        className="block card-hover overflow-hidden"
      >
        {/* Image container */}
        <div className="relative">
          <OptimizedImage
            src={main_image_url}
            alt={name}
            aspectRatio="square"
            className="product-card-image"
          />

          {/* Overlay gradient */}
          <div className="product-card-overlay" />

          {/* Badges */}
          <div className="absolute top-3 left-3 flex flex-col gap-2">
            {!inStock && (
              <span className="badge-danger">Out of stock</span>
            )}
            {inStock && availableQuantity > 0 && availableQuantity < 10 && (
              <span className="badge-accent">{availableQuantity} left</span>
            )}
            {isNew && (
              <span className="badge-accent">NEW</span>
            )}
            {isSale && discount > 0 && (
              <span className="badge-danger">-{discount}%</span>
            )}
          </div>

          {/* Quick actions */}
          <div className="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">
            {isAuth && variantId && (
              <button
                type="button"
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  addToWishlist.mutate();
                }}
                disabled={addToWishlist.isPending}
                className="w-9 h-9 rounded-lg bg-dark/80 backdrop-blur-sm flex items-center justify-center text-text-secondary hover:text-accent hover:bg-dark transition-colors disabled:opacity-50"
                title="Add to wishlist"
              >
                <Heart className="w-4 h-4" />
              </button>
            )}
            {variantId && (
              <button
                type="button"
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  addToCart.mutate();
                }}
                disabled={addToCart.isPending || !inStock}
                title={inStock ? 'Add to cart' : 'Out of stock'}
                className={`w-9 h-9 rounded-lg flex items-center justify-center transition-colors disabled:opacity-50 ${
                  inStock
                    ? 'bg-accent/90 backdrop-blur-sm text-dark hover:bg-accent'
                    : 'bg-dark/80 backdrop-blur-sm text-text-muted cursor-not-allowed'
                }`}
              >
                <ShoppingCart className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>

        {/* Content */}
        <div className="p-4">
          {/* Rating */}
          {rating > 0 && (
            <div className="flex items-center gap-1 mb-2">
              <div className="flex items-center">
                {[...Array(5)].map((_, i) => (
                  <Star
                    key={i}
                    className={`w-3.5 h-3.5 ${
                      i < Math.floor(rating) ? 'text-yellow-400 fill-yellow-400' : 'text-dark-300'
                    }`}
                  />
                ))}
              </div>
              {reviewCount > 0 && (
                <span className="text-xs text-text-muted">({reviewCount})</span>
              )}
            </div>
          )}

          {/* Title */}
          <h3 className="font-semibold text-text-primary leading-snug line-clamp-2 group-hover:text-accent transition-colors">
            {name}
          </h3>

          {/* Description */}
          {description && (
            <p className="text-text-muted text-sm mt-1.5 line-clamp-2">
              {description}
            </p>
          )}

          {/* Price */}
          <div className="mt-3 flex items-baseline gap-2 flex-wrap">
            {price !== undefined && !Number.isNaN(price) ? (
              <>
                <span className="text-lg font-bold text-accent">
                  {formatCurrency(price, currency)}
                </span>
                {originalPrice && originalPrice > price && (
                  <span className="text-sm text-text-muted line-through">
                    {formatCurrency(originalPrice, currency)}
                  </span>
                )}
              </>
            ) : (
              <span className="text-sm text-text-muted">Price on detail</span>
            )}
          </div>
        </div>
      </Link>
    </div>
  );
}
