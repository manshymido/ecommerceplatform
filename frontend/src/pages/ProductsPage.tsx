import { useState, useEffect, useRef } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { catalogApi } from '../api';
import type { Category } from '../api/types';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { ProductCard } from '../components/ProductCard';
import { Pagination } from '../components/Pagination';
import { SEO } from '../components/SEO';
import { useDebounce } from '../hooks/useDebounce';
import { useQueryWithUI } from '../hooks/useQueryWithUI';
import {
  Search,
  X,
  Grid3X3,
  LayoutGrid,
  SlidersHorizontal,
  Tag,
  Layers,
  Sparkles,
} from 'lucide-react';

const PRODUCT_STAGGER_CLASSES = [
  'stagger-0', 'stagger-30', 'stagger-60', 'stagger-90', 'stagger-120', 'stagger-150',
  'stagger-180', 'stagger-210', 'stagger-240', 'stagger-270', 'stagger-300', 'stagger-330',
] as const;

export function ProductsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const categoryId = searchParams.get('category') ?? '';
  const brandId = searchParams.get('brand') ?? '';

  const [page, setPage] = useState(1);
  const [searchInput, setSearchInput] = useState('');
  const [appliedSearch, setAppliedSearch] = useState('');
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [showFilters, setShowFilters] = useState(false);
  const [gridSize, setGridSize] = useState<'small' | 'large'>('large');
  const searchContainerRef = useRef<HTMLDivElement>(null);
  const debouncedSearch = useDebounce(searchInput.trim(), 300);

  const setCategoryInUrl = (id: string) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      if (id) p.set('category', id);
      else p.delete('category');
      p.delete('page');
      return p;
    });
    setPage(1);
  };

  const setBrandInUrl = (id: string) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      if (id) p.set('brand', id);
      else p.delete('brand');
      p.delete('page');
      return p;
    });
    setPage(1);
  };

  const { data: productsRes, render, isLoading } = useQueryWithUI({
    queryKey: ['products', page, appliedSearch, categoryId, brandId],
    queryFn: () =>
      catalogApi.products({
        page,
        per_page: 12,
        ...(appliedSearch && { search: appliedSearch }),
        ...(categoryId && { category_id: Number(categoryId) }),
        ...(brandId && { brand_id: Number(brandId) }),
      }),
    fallbackMessage: 'Failed to load products',
  });

  const { data: suggestions = [], isFetching: suggestionsLoading } = useQuery({
    queryKey: ['product-suggestions', debouncedSearch],
    queryFn: () => catalogApi.searchSuggestions(debouncedSearch, 10),
    enabled: debouncedSearch.length >= 2,
    staleTime: 60_000,
  });

  const { data: categoriesRes } = useQuery({
    queryKey: ['categories'],
    queryFn: () => catalogApi.categories(),
  });
  const { data: brandsRes } = useQuery({
    queryKey: ['brands'],
    queryFn: () => catalogApi.brands(),
  });

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (searchContainerRef.current && !searchContainerRef.current.contains(e.target as Node)) {
        setShowSuggestions(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const applySearch = (value: string) => {
    setAppliedSearch(value);
    setSearchInput(value);
    setPage(1);
    setShowSuggestions(false);
  };

  const clearFilters = () => {
    setSearchInput('');
    setAppliedSearch('');
    setPage(1);
    setShowSuggestions(false);
    setSearchParams({});
  };

  const hasActiveFilters = appliedSearch || categoryId || brandId;

  const ui = render();
  if (ui) return ui;

  const products = productsRes?.data ?? [];
  const meta = productsRes?.meta;
  const categories = (categoriesRes ?? []) as Category[];
  const brands = brandsRes ?? [];
  const canShowSuggestions = showSuggestions && searchInput.length >= 2;

  return (
    <div className="min-h-screen">
      <SEO
        title="Products"
        description="Browse our collection of digital products and gaming credits. Find the best deals on Razer Gold, Steam, PlayStation, Xbox and more."
      />

      {/* Header */}
      <div className="bg-dark-50 border-b border-surface-border">
        <div className="container-app py-8 lg:py-12">
          <div className="flex items-center gap-2 text-sm text-text-muted mb-4">
            <Link to="/" className="hover:text-accent transition-colors">Home</Link>
            <span>/</span>
            <span className="text-text-primary">Products</span>
          </div>
          <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
              <h1 className="heading-lg text-text-primary">All Products</h1>
              <p className="text-text-secondary mt-2">
                {meta?.total ? `${meta.total} products found` : 'Browse our collection'}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="container-app py-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Sidebar Filters - Desktop */}
          <aside className="hidden lg:block w-64 shrink-0">
            <div className="sticky top-24 space-y-6">
              {/* Search */}
              <div className="card p-4">
                <h3 className="font-semibold text-text-primary mb-3 flex items-center gap-2">
                  <Search className="w-4 h-4 text-accent" />
                  Search
                </h3>
                <div ref={searchContainerRef} className="relative">
                  <input
                    type="search"
                    placeholder="Search products..."
                    value={searchInput}
                    onChange={(e) => {
                      setSearchInput(e.target.value);
                      setShowSuggestions(true);
                    }}
                    onFocus={() => searchInput.length >= 2 && setShowSuggestions(true)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') applySearch(searchInput.trim());
                    }}
                    className="input pr-10"
                  />
                  {searchInput && (
                    <button
                      type="button"
                      onClick={() => {
                        setSearchInput('');
                        setAppliedSearch('');
                      }}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
                      aria-label="Clear search"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  )}
                  {canShowSuggestions && (
                    <div className="absolute z-20 mt-2 w-full rounded-xl bg-surface-card border border-surface-border shadow-cardHover overflow-hidden">
                      {suggestionsLoading ? (
                        <div className="px-4 py-3 text-sm text-text-muted">Loading...</div>
                      ) : suggestions.length === 0 ? (
                        <div className="px-4 py-3 text-sm text-text-muted">No results found</div>
                      ) : (
                        <ul className="max-h-60 overflow-auto">
                          {suggestions.map((s) => (
                            <li key={s.id}>
                              <Link
                                to={`/products/${s.slug}`}
                                onClick={() => setShowSuggestions(false)}
                                className="block px-4 py-3 text-sm text-text-primary hover:bg-surface-hover hover:text-accent transition-colors"
                              >
                                {s.name}
                              </Link>
                            </li>
                          ))}
                        </ul>
                      )}
                    </div>
                  )}
                </div>
              </div>

              {/* Categories */}
              <div className="card p-4">
                <h3 className="font-semibold text-text-primary mb-3 flex items-center gap-2">
                  <Layers className="w-4 h-4 text-accent" />
                  Categories
                </h3>
                <ul className="space-y-1">
                  <li>
                    <button
                      type="button"
                      onClick={() => setCategoryInUrl('')}
                      className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                        !categoryId
                          ? 'bg-accent/10 text-accent font-medium'
                          : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                      }`}
                    >
                      All Categories
                    </button>
                  </li>
                  {categories.map((c) => (
                    <li key={c.id}>
                      <button
                        type="button"
                        onClick={() => setCategoryInUrl(String(c.id))}
                        className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                          categoryId === String(c.id)
                            ? 'bg-accent/10 text-accent font-medium'
                            : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                        }`}
                      >
                        {c.name}
                      </button>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Brands */}
              <div className="card p-4">
                <h3 className="font-semibold text-text-primary mb-3 flex items-center gap-2">
                  <Tag className="w-4 h-4 text-accent" />
                  Brands
                </h3>
                <ul className="space-y-1">
                  <li>
                    <button
                      type="button"
                      onClick={() => setBrandInUrl('')}
                      className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                        !brandId
                          ? 'bg-accent/10 text-accent font-medium'
                          : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                      }`}
                    >
                      All Brands
                    </button>
                  </li>
                  {brands.map((b) => (
                    <li key={b.id}>
                      <button
                        type="button"
                        onClick={() => setBrandInUrl(String(b.id))}
                        className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                          brandId === String(b.id)
                            ? 'bg-accent/10 text-accent font-medium'
                            : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                        }`}
                      >
                        {b.name}
                      </button>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Clear filters */}
              {hasActiveFilters && (
                <button onClick={clearFilters} className="btn-secondary w-full">
                  <X className="w-4 h-4" />
                  Clear Filters
                </button>
              )}
            </div>
          </aside>

          {/* Main content */}
          <div className="flex-1 min-w-0">
            {/* Mobile filter bar */}
            <div className="lg:hidden mb-6">
              <div className="flex gap-3">
                <div ref={searchContainerRef} className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
                  <input
                    type="search"
                    placeholder="Search..."
                    value={searchInput}
                    onChange={(e) => {
                      setSearchInput(e.target.value);
                      setShowSuggestions(true);
                    }}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter') applySearch(searchInput.trim());
                    }}
                    className="input pl-10"
                  />
                </div>
                <button
                  type="button"
                  onClick={() => setShowFilters(!showFilters)}
                  aria-label={showFilters ? 'Hide filters' : 'Show filters'}
                  className={`btn-secondary ${hasActiveFilters ? 'border-accent text-accent' : ''}`}
                >
                  <SlidersHorizontal className="w-4 h-4" />
                  {hasActiveFilters && (
                    <span className="w-2 h-2 rounded-full bg-accent" />
                  )}
                </button>
              </div>

              {/* Mobile filters dropdown */}
              {showFilters && (
                <div className="mt-4 card p-4 space-y-4 animate-fade-in">
                  <div>
                    <label id="products-mobile-category-label" className="label" htmlFor="products-mobile-category">
                      Category
                    </label>
                    <select
                      id="products-mobile-category"
                      aria-labelledby="products-mobile-category-label"
                      value={categoryId}
                      onChange={(e) => setCategoryInUrl(e.target.value)}
                      className="select"
                    >
                      <option value="">All Categories</option>
                      {categories.map((c) => (
                        <option key={c.id} value={String(c.id)}>{c.name}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label id="products-mobile-brand-label" className="label" htmlFor="products-mobile-brand">
                      Brand
                    </label>
                    <select
                      id="products-mobile-brand"
                      aria-labelledby="products-mobile-brand-label"
                      value={brandId}
                      onChange={(e) => setBrandInUrl(e.target.value)}
                      className="select"
                    >
                      <option value="">All Brands</option>
                      {brands.map((b) => (
                        <option key={b.id} value={String(b.id)}>{b.name}</option>
                      ))}
                    </select>
                  </div>
                  {hasActiveFilters && (
                    <button onClick={clearFilters} className="btn-ghost w-full">
                      <X className="w-4 h-4" />
                      Clear Filters
                    </button>
                  )}
                </div>
              )}
            </div>

            {/* Active filters */}
            {hasActiveFilters && (
              <div className="flex flex-wrap items-center gap-2 mb-6">
                <span className="text-sm text-text-muted">Active filters:</span>
                {appliedSearch && (
                  <button
                    onClick={() => {
                      setSearchInput('');
                      setAppliedSearch('');
                    }}
                    className="badge-accent group"
                  >
                    Search: {appliedSearch}
                    <X className="w-3 h-3 group-hover:text-white" />
                  </button>
                )}
                {categoryId && (
                  <button
                    type="button"
                    onClick={() => setCategoryInUrl('')}
                    className="badge-accent group"
                  >
                    {categories.find(c => String(c.id) === categoryId)?.name}
                    <X className="w-3 h-3 group-hover:text-white" />
                  </button>
                )}
                {brandId && (
                  <button
                    type="button"
                    onClick={() => setBrandInUrl('')}
                    className="badge-accent group"
                  >
                    {brands.find(b => String(b.id) === brandId)?.name}
                    <X className="w-3 h-3 group-hover:text-white" />
                  </button>
                )}
              </div>
            )}

            {/* Grid controls */}
            <div className="hidden lg:flex items-center justify-between mb-6">
              <p className="text-sm text-text-muted">
                {meta?.total ? `Showing ${products.length} of ${meta.total} products` : ''}
              </p>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setGridSize('large')}
                  aria-label="Large grid layout"
                  className={`p-2 rounded-lg transition-colors ${
                    gridSize === 'large'
                      ? 'bg-accent/10 text-accent'
                      : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
                  }`}
                >
                  <LayoutGrid className="w-5 h-5" />
                </button>
                <button
                  type="button"
                  onClick={() => setGridSize('small')}
                  aria-label="Small grid layout"
                  className={`p-2 rounded-lg transition-colors ${
                    gridSize === 'small'
                      ? 'bg-accent/10 text-accent'
                      : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
                  }`}
                >
                  <Grid3X3 className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Products grid */}
            {isLoading ? (
              <div className="flex justify-center py-20">
                <LoadingSpinner />
              </div>
            ) : products.length === 0 ? (
              <div className="card p-12 text-center">
                <div className="w-16 h-16 mx-auto rounded-full bg-dark-200 flex items-center justify-center mb-4">
                  <Sparkles className="w-8 h-8 text-text-muted" />
                </div>
                <h3 className="font-semibold text-text-primary mb-2">No products found</h3>
                <p className="text-text-muted mb-4">Try adjusting your filters or search terms</p>
                {hasActiveFilters && (
                  <button onClick={clearFilters} className="btn-secondary">
                    Clear Filters
                  </button>
                )}
              </div>
            ) : (
              <div
                className={`grid gap-4 lg:gap-6 ${
                  gridSize === 'large'
                    ? 'grid-cols-2 lg:grid-cols-3'
                    : 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4'
                }`}
              >
                {products.map((product, i) => {
                  const firstVariant = product.variants?.[0];
                  const priceObj = firstVariant?.prices?.find((p: { currency: string }) => p.currency === 'USD') ?? firstVariant?.prices?.[0];
                  const price = priceObj && typeof priceObj.amount === 'number' ? priceObj.amount : typeof priceObj?.amount === 'string' ? parseFloat(priceObj.amount) : undefined;
                  const currency = priceObj?.currency ?? 'USD';
                  const availableQty = firstVariant?.available_quantity ?? 0;
                  return (
                    <div
                      key={product.id}
                      className={`animate-fade-in-up ${PRODUCT_STAGGER_CLASSES[Math.min(i, PRODUCT_STAGGER_CLASSES.length - 1)]}`}
                    >
                      <ProductCard
                        id={product.id}
                        slug={product.slug}
                        name={product.name}
                        description={product.description}
                        main_image_url={product.main_image_url}
                        variantId={firstVariant?.id}
                        price={price}
                        currency={currency}
                        inStock={availableQty > 0}
                        availableQuantity={availableQty}
                      />
                    </div>
                  );
                })}
              </div>
            )}

            {/* Pagination */}
            {meta && meta.last_page > 1 && (
              <div className="mt-8">
                <Pagination
                  currentPage={meta.current_page}
                  lastPage={meta.last_page}
                  onPageChange={setPage}
                />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
