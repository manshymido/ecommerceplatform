import { Link } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { wishlistApi, cartApi } from '../api';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import { useToastStore } from '../store/toastStore';
import { getApiErrorMessage } from '../utils/apiError';
import { EmptyState } from '../components/EmptyState';
import {
  Heart,
  Trash2,
  ShoppingCart,
  ArrowRight,
  ShoppingBag,
  Eye,
} from 'lucide-react';

const WISHLIST_STAGGER_CLASSES = [
  'stagger-0', 'stagger-50', 'stagger-100', 'stagger-150', 'stagger-200', 'stagger-250',
  'stagger-300', 'stagger-350', 'stagger-400', 'stagger-450', 'stagger-500',
] as const;

export function WishlistPage() {
  const queryClient = useQueryClient();
  const toast = useToastStore((s) => s.add);

  const { data: wishlist, render } = useQueryWithUI({
    queryKey: ['wishlist'],
    queryFn: () => wishlistApi.show(),
    fallbackMessage: 'Failed to load wishlist',
  });

  const removeItem = useMutation({
    mutationFn: (itemId: number) => wishlistApi.removeItem(itemId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['wishlist'] });
      toast('Removed from wishlist', 'success');
    },
    onError: (err) => toast(getApiErrorMessage(err, 'Failed to remove from wishlist'), 'error'),
  });

  const addToCart = useMutation({
    mutationFn: (variantId: number) => cartApi.addItem(variantId, 1),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
      toast('Added to cart', 'success');
    },
    onError: (err) => toast(getApiErrorMessage(err, 'Failed to add to cart'), 'error'),
  });

  const ui = render();
  if (ui) return ui;

  const items = wishlist?.items ?? [];

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <div className="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link to="/" className="hover:text-accent transition-colors">Home</Link>
            <span>/</span>
            <span className="text-text-primary">Wishlist</span>
          </div>
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
              <Heart className="w-6 h-6 text-accent" />
            </div>
            <div>
              <h1 className="heading-lg text-text-primary">My Wishlist</h1>
              <p className="text-text-secondary mt-1">
                {items.length === 0
                  ? 'Save items for later'
                  : `${items.length} ${items.length === 1 ? 'item' : 'items'} saved`}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        {items.length === 0 ? (
          <div className="card p-12 text-center max-w-lg mx-auto">
            <EmptyState
              message="Your wishlist is empty"
              description="Start adding items you love to your wishlist. They'll be saved here for you to find later."
              icon={<Heart className="w-8 h-8 text-text-muted" />}
              action={
                <Link to="/products" className="btn-primary btn-lg inline-flex items-center gap-2">
                  <ShoppingBag className="w-5 h-5" />
                  Browse Products
                </Link>
              }
              className="!p-0"
            />
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {items.map((item, i) => (
              <div
                key={item.id}
                className={`card overflow-hidden group animate-fade-in-up ${WISHLIST_STAGGER_CLASSES[Math.min(i, WISHLIST_STAGGER_CLASSES.length - 1)]}`}
              >
                {/* Image */}
                <div className="aspect-square bg-dark-100 relative overflow-hidden">
                  {item.product?.main_image_url ? (
                    <img
                      src={item.product.main_image_url}
                      alt={item.product.name}
                      className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <Eye className="w-12 h-12 text-text-muted" />
                    </div>
                  )}

                  {/* Quick actions overlay */}
                  <div className="absolute inset-0 bg-dark/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <Link
                      to={item.product ? `/products/${item.product.slug}` : '#'}
                      className="w-10 h-10 rounded-full bg-white flex items-center justify-center text-dark hover:bg-accent transition-colors"
                    >
                      <Eye className="w-5 h-5" />
                    </Link>
                    {item.product_variant_id && (
                      <button
                        type="button"
                        onClick={() => addToCart.mutate(item.product_variant_id)}
                        disabled={addToCart.isPending}
                        className="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-dark hover:bg-accent-hover transition-colors disabled:opacity-50"
                        aria-label="Add to cart"
                      >
                        <ShoppingCart className="w-5 h-5" />
                      </button>
                    )}
                  </div>
                </div>

                {/* Content */}
                <div className="p-4">
                  <Link
                    to={item.product ? `/products/${item.product.slug}` : '#'}
                    className="font-semibold text-text-primary hover:text-accent transition-colors line-clamp-2"
                  >
                    {item.product?.name ?? `Variant #${item.product_variant_id}`}
                  </Link>

                  {item.product_variant?.name && (
                    <p className="text-sm text-text-muted mt-1">
                      {item.product_variant.name}
                    </p>
                  )}

                  <div className="flex items-center justify-between mt-4 pt-4 border-t border-surface-border">
                    <Link
                      to={item.product ? `/products/${item.product.slug}` : '#'}
                      className="text-sm text-accent hover:text-accent-light transition-colors flex items-center gap-1"
                    >
                      View Details
                      <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                    <button
                      type="button"
                      onClick={() => removeItem.mutate(item.id)}
                      disabled={removeItem.isPending}
                      className="w-8 h-8 rounded-lg flex items-center justify-center text-text-muted hover:text-status-danger hover:bg-status-dangerBg transition-colors disabled:opacity-50"
                      aria-label="Remove from wishlist"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
