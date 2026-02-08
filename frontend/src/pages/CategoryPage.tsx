import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalogApi } from '../api';
import { EmptyState } from '../components/EmptyState';
import { ErrorMessage } from '../components/ErrorMessage';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { ProductCard } from '../components/ProductCard';
import { Pagination } from '../components/Pagination';
import { getApiErrorMessage } from '../utils/apiError';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import { Layers, ChevronLeft, Sparkles } from 'lucide-react';

const STAGGER_DELAY_CLASSES = [
  'stagger-0', 'stagger-50', 'stagger-100', 'stagger-150', 'stagger-200',
  'stagger-250', 'stagger-300', 'stagger-350', 'stagger-400', 'stagger-450', 'stagger-500',
];

export function CategoryPage() {
  const { slug } = useParams<{ slug: string }>();
  const [page, setPage] = useState(1);

  const { data: category, render: renderCategory } = useQueryWithUI({
    queryKey: ['category', slug],
    queryFn: () => catalogApi.category(slug!),
    fallbackMessage: 'Category not found',
    enabled: !!slug,
  });

  const {
    data: productsRes,
    error: productsError,
    isLoading: productsLoading,
  } = useQuery({
    queryKey: ['products', 'category', category?.id, page],
    queryFn: () =>
      catalogApi.products({
        category_id: category!.id,
        page,
        per_page: 12,
      }),
    enabled: !!category?.id,
  });

  const categoryUi = renderCategory();
  if (categoryUi) return categoryUi;

  const products = productsRes?.data ?? [];
  const meta = productsRes?.meta;

  if (!slug) {
    return <ErrorMessage message="Category not specified" />;
  }
  if (!category) return null;

  if (productsError) {
    return (
      <div className="container-app py-12">
        <ErrorMessage
          message={getApiErrorMessage(productsError, 'Failed to load products')}
        />
      </div>
    );
  }

  return (
    <div className="min-h-screen">
      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <Link
            to="/products"
            className="inline-flex items-center gap-2 text-text-secondary hover:text-accent transition-colors mb-6"
          >
            <ChevronLeft className="w-4 h-4" />
            All Products
          </Link>

          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-xl bg-accent/10 flex items-center justify-center">
              <Layers className="w-7 h-7 text-accent" />
            </div>
            <div>
              <h1 className="heading-lg text-text-primary">{category.name}</h1>
              <p className="text-text-secondary mt-1">
                {meta?.total
                  ? `${meta.total} ${meta.total === 1 ? 'product' : 'products'} in this category`
                  : 'Browse products in this category'}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8 lg:py-12">
        {productsLoading ? (
          <LoadingSpinner />
        ) : products.length === 0 ? (
          <div className="card p-12 text-center max-w-lg mx-auto">
            <EmptyState
              message="No products yet"
              description="There are no products in this category at the moment. Check back soon!"
              icon={<Sparkles className="w-8 h-8 text-text-muted" />}
              action={
                <Link to="/products" className="btn-primary">
                  Browse All Products
                </Link>
              }
              className="!p-0"
            />
          </div>
        ) : (
          <>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
              {products.map((product, i) => (
                <div
                  key={product.id}
                  className={`animate-fade-in-up ${STAGGER_DELAY_CLASSES[Math.min(i, STAGGER_DELAY_CLASSES.length - 1)]}`}
                >
                  <ProductCard
                    id={product.id}
                    slug={product.slug}
                    name={product.name}
                    description={product.description}
                    main_image_url={product.main_image_url}
                    variantId={product.variants?.[0]?.id}
                  />
                </div>
              ))}
            </div>

            {meta && meta.last_page > 1 && (
              <div className="mt-10">
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
