import { useState, useEffect, useRef } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus, Search, Pencil, Trash2, DollarSign, X, Tag } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Product, Category, Brand, ProductVariant } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { EmptyState } from '../../components/EmptyState';
import { Pagination } from '../../components/Pagination';
import { Button, FormField, Input, Select, Textarea } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import { getApiErrorMessage } from '../../utils/apiError';
import { formatCurrency, getStatusBadgeClass } from '../../utils/format';
import { useToastStore } from '../../store/toastStore';

const STATUS_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'archived', label: 'Archived' },
];

function getProductPriceDisplay(p: Product): { amount: number; currency: string } | null {
  const variants = p.variants ?? [];
  for (const v of variants) {
    const prices = (v as ProductVariant).prices ?? [];
    const usd = prices.find((pr: { currency: string }) => pr.currency === 'USD');
    const first = prices[0];
    const price = usd ?? first;
    if (price) {
      const amount = typeof price.amount === 'string' ? parseFloat(price.amount) : price.amount;
      if (!Number.isNaN(amount)) return { amount, currency: price.currency ?? 'USD' };
    }
  }
  return null;
}

type ProductFormState = {
  name: string;
  slug: string;
  description: string;
  brand_id: number | '';
  status: string;
  main_image_url: string;
  seo_title: string;
  seo_description: string;
  category_ids: number[];
  variantPrices: Record<number, string>;
  defaultVariant: { sku: string; name: string; price: string | number };
};

function AddProductForm({
  form,
  setForm,
  categorySearch,
  setCategorySearch,
  brandList,
  categoryList,
}: {
  form: ProductFormState;
  setForm: React.Dispatch<React.SetStateAction<ProductFormState>>;
  categorySearch: string;
  setCategorySearch: (s: string) => void;
  brandList: Brand[];
  categoryList: Category[];
}) {
  const filteredCategories = categoryList.filter(
    (c) =>
      !categorySearch.trim() ||
      c.name.toLowerCase().includes(categorySearch.toLowerCase())
  );

  return (
    <div className="flex min-w-0 flex-col gap-8">
      <fieldset className="space-y-4 border-0 p-0 min-w-0">
        <legend className="text-sm font-semibold uppercase tracking-wider text-text-muted mb-4 block">
          Basic information
        </legend>
        <div className="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
          <div className="min-w-0">
            <label htmlFor="add-product-name" className="block text-sm font-medium text-text-secondary mb-1.5">
              Name
            </label>
            <Input
              id="add-product-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              placeholder="Product name"
            />
          </div>
          <div className="min-w-0">
            <label htmlFor="add-product-slug" className="block text-sm font-medium text-text-secondary mb-1.5">
              Slug
            </label>
            <Input
              id="add-product-slug"
              type="text"
              value={form.slug}
              onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
              placeholder="url-friendly-slug"
            />
          </div>
        </div>
        <div>
          <label htmlFor="add-product-desc" className="block text-sm font-medium text-text-secondary mb-1.5">
            Description
          </label>
          <Textarea
            id="add-product-desc"
            value={form.description}
            onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
            rows={4}
            placeholder="Optional description"
          />
        </div>
      </fieldset>

      <fieldset className="space-y-4 border-0 p-0 min-w-0">
        <legend className="text-sm font-semibold uppercase tracking-wider text-text-muted mb-4 block">
          Organization
        </legend>
        <div className="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
          <div className="min-w-0">
            <label htmlFor="add-product-brand" className="block text-sm font-medium text-text-secondary mb-1.5">
              Brand
            </label>
            <Select
              id="add-product-brand"
              value={form.brand_id === '' ? '' : form.brand_id}
              onChange={(e) =>
                setForm((f) => ({ ...f, brand_id: e.target.value === '' ? '' : Number(e.target.value) }))}
            >
              <option value="">No brand</option>
              {brandList.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </Select>
          </div>
          <div className="min-w-0">
            <label htmlFor="add-product-status" className="block text-sm font-medium text-text-secondary mb-1.5">
              Status
            </label>
            <Select
              id="add-product-status"
              value={form.status}
              onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </Select>
          </div>
        </div>
      </fieldset>

      <fieldset className="space-y-4 border-0 p-0 min-w-0">
        <legend className="text-sm font-semibold uppercase tracking-wider text-text-muted mb-4 block">
          Categories
        </legend>
        {form.category_ids.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {form.category_ids.map((id) => {
              const cat = categoryList.find((c) => c.id === id);
              return cat ? (
                <span
                  key={id}
                  className="inline-flex items-center gap-1.5 rounded-md bg-accent/20 px-2.5 py-1 text-sm text-accent"
                >
                  {cat.name}
                  <button
                    type="button"
                    onClick={() =>
                      setForm((f) => ({ ...f, category_ids: f.category_ids.filter((i) => i !== id) }))}
                    className="rounded hover:bg-accent/30 p-0.5"
                    aria-label={`Remove ${cat.name}`}
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                </span>
              ) : null;
            })}
          </div>
        )}
        <input
          type="text"
          placeholder="Search categories..."
          value={categorySearch}
          onChange={(e) => setCategorySearch(e.target.value)}
          className="w-full rounded-lg border border-surface-border bg-dark-50 px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-accent focus:ring-1 focus:ring-accent"
          aria-label="Search categories"
        />
        <div className="max-h-44 overflow-y-auto rounded-lg border border-surface-border bg-dark-50/50 p-2 space-y-0.5">
          {filteredCategories.length === 0 ? (
            <p className="px-2 py-3 text-sm text-text-muted">No categories match.</p>
          ) : (
            filteredCategories.map((c) => (
              <label
                key={c.id}
                className="flex items-center gap-2 cursor-pointer rounded px-2 py-2 text-sm text-text-secondary hover:bg-surface-hover hover:text-text-primary"
              >
                <input
                  type="checkbox"
                  checked={form.category_ids.includes(c.id)}
                  onChange={(e) =>
                    setForm((f) => ({
                      ...f,
                      category_ids: e.target.checked
                        ? [...f.category_ids, c.id]
                        : f.category_ids.filter((i) => i !== c.id),
                    }))
                  }
                  className="rounded border-surface-border text-accent focus:ring-accent"
                />
                {c.name}
              </label>
            ))
          )}
        </div>
      </fieldset>

      <fieldset className="space-y-4 border-0 p-0 min-w-0 rounded-xl border border-surface-border bg-dark-50/30 p-5">
        <legend className="text-sm font-semibold uppercase tracking-wider text-text-muted mb-2 block">
          Default variant & price (optional)
        </legend>
        <p className="text-xs text-text-muted">
          Add a default variant with a price so the product can be sold. You can add more variants when editing.
        </p>
        <div className="grid min-w-0 grid-cols-1 gap-4 pt-2 sm:grid-cols-3">
          <div className="min-w-0">
            <label htmlFor="add-variant-sku" className="block text-xs font-medium text-text-muted mb-1">
              SKU (optional)
            </label>
            <Input
              id="add-variant-sku"
              placeholder="Leave blank to auto-generate (e.g. RG-1-A7F2B1)"
              value={form.defaultVariant.sku}
              onChange={(e) =>
                setForm((f) => ({
                  ...f,
                  defaultVariant: { ...f.defaultVariant, sku: e.target.value },
                }))}
            />
          </div>
          <div className="min-w-0">
            <label htmlFor="add-variant-name" className="block text-xs font-medium text-text-muted mb-1">
              Variant name
            </label>
            <Input
              id="add-variant-name"
              placeholder="e.g. Default"
              value={form.defaultVariant.name}
              onChange={(e) =>
                setForm((f) => ({
                  ...f,
                  defaultVariant: { ...f.defaultVariant, name: e.target.value },
                }))}
            />
          </div>
          <div className="min-w-0">
            <label htmlFor="add-variant-price" className="block text-xs font-medium text-text-muted mb-1">
              Price (USD)
            </label>
            <div className="flex items-center gap-2 rounded-lg border border-surface-border bg-dark-50 overflow-hidden focus-within:ring-1 focus-within:ring-accent focus-within:border-accent">
              <DollarSign className="w-4 h-4 text-text-muted ml-3 shrink-0" aria-hidden />
              <input
                id="add-variant-price"
                type="number"
                min={0}
                step={0.01}
                placeholder="0.00"
                value={form.defaultVariant.price}
                onChange={(e) =>
                  setForm((f) => ({
                    ...f,
                    defaultVariant: { ...f.defaultVariant, price: e.target.value },
                  }))}
                className="w-full bg-transparent px-3 py-2 text-text-primary focus:outline-none min-w-0"
              />
            </div>
          </div>
        </div>
      </fieldset>

      <fieldset className="space-y-4 border-0 p-0 min-w-0">
        <legend className="text-sm font-semibold uppercase tracking-wider text-text-muted mb-4 block">
          Media & SEO
        </legend>
        <div className="space-y-5">
          <div>
            <label htmlFor="add-product-image" className="block text-sm font-medium text-text-secondary mb-1.5">
              Main image URL
            </label>
            <Input
              id="add-product-image"
              type="url"
              value={form.main_image_url}
              onChange={(e) => setForm((f) => ({ ...f, main_image_url: e.target.value }))}
              placeholder="https://..."
            />
          </div>
          <div>
            <label htmlFor="add-product-seo-title" className="block text-sm font-medium text-text-secondary mb-1.5">
              SEO title
            </label>
            <Input
              id="add-product-seo-title"
              type="text"
              value={form.seo_title}
              onChange={(e) => setForm((f) => ({ ...f, seo_title: e.target.value }))}
              placeholder="Optional"
            />
          </div>
          <div>
            <label htmlFor="add-product-seo-desc" className="block text-sm font-medium text-text-secondary mb-1.5">
              SEO description
            </label>
            <Textarea
              id="add-product-seo-desc"
              value={form.seo_description}
              onChange={(e) => setForm((f) => ({ ...f, seo_description: e.target.value }))}
              rows={2}
              placeholder="Optional"
            />
          </div>
        </div>
      </fieldset>
    </div>
  );
}

export function AdminProductsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');
  const [brandId, setBrandId] = useState<number | ''>('');
  const [search, setSearch] = useState('');
  const [searchApplied, setSearchApplied] = useState('');
  const [modalProduct, setModalProduct] = useState<Product | null>(null);
  const [isCreate, setIsCreate] = useState(false);
  const [form, setForm] = useState({
    name: '',
    slug: '',
    description: '',
    brand_id: '' as number | '',
    status: 'draft' as string,
    main_image_url: '',
    seo_title: '',
    seo_description: '',
    category_ids: [] as number[],
    variantPrices: {} as Record<number, string>,
    defaultVariant: { sku: '', name: '', price: '' as string | number },
  });
  const [categorySearch, setCategorySearch] = useState('');
  const variantPricesInitializedForProductId = useRef<number | null>(null);

  const { data: brands } = useQuery({
    queryKey: ['admin-brands'],
    queryFn: () => adminApi.brands.list(),
  });
  const { data: categories } = useQuery({
    queryKey: ['admin-categories'],
    queryFn: () => adminApi.categories.list(),
  });

  const listQuery = useQueryWithUI({
    queryKey: ['admin-products', page, status || undefined, brandId || undefined, searchApplied],
    queryFn: () =>
      adminApi.products.list({
        page,
        per_page: 15,
        status: status || undefined,
        brand_id: brandId || undefined,
        search: searchApplied || undefined,
      }),
    fallbackMessage: 'Failed to load products',
  });

  const productDetailQuery = useQuery({
    queryKey: ['admin-product', modalProduct?.id],
    queryFn: () => adminApi.products.get(modalProduct!.id),
    enabled: !!modalProduct && !isCreate,
  });

  const toast = useToastStore((s) => s.add);
  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => adminApi.products.create(payload),
    onSuccess: () => {
      setPage(1);
      queryClient.invalidateQueries({ queryKey: ['admin-products'] });
      closeModal();
      toast('Product created', 'success');
    },
    onError: (err) => toast(getApiErrorMessage(err, 'Failed to create product'), 'error'),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Record<string, unknown> }) =>
      adminApi.products.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-products'] });
      toast('Product updated', 'success');
    },
    onError: (err) => toast(getApiErrorMessage(err, 'Failed to update product'), 'error'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminApi.products.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-products'] }),
  });

  useEffect(() => {
    if (!modalProduct || isCreate || !productDetailQuery.data || productDetailQuery.data.id !== modalProduct.id) return;
    if (variantPricesInitializedForProductId.current === modalProduct.id) return;
    const variants = productDetailQuery.data.variants ?? [];
    if (variants.length === 0) return;
    const variantPrices: Record<number, string> = {};
    for (const v of variants) {
      const usd = (v as ProductVariant).prices?.find((p: { currency: string }) => p.currency === 'USD');
      const first = (v as ProductVariant).prices?.[0];
      const amount = usd ?? first;
      variantPrices[v.id] = amount != null ? String(amount.amount) : '';
    }
    variantPricesInitializedForProductId.current = modalProduct.id;
    setForm((prev) => ({ ...prev, variantPrices }));
  }, [modalProduct, isCreate, productDetailQuery.data]);

  function closeModal() {
    variantPricesInitializedForProductId.current = null;
    setModalProduct(null);
    setIsCreate(false);
    setCategorySearch('');
    setForm({
      name: '',
      slug: '',
      description: '',
      brand_id: '',
      status: 'draft',
      main_image_url: '',
      seo_title: '',
      seo_description: '',
      category_ids: [],
      variantPrices: {},
      defaultVariant: { sku: '', name: '', price: '' },
    });
  }

  function openCreate() {
    setIsCreate(true);
    setModalProduct(null);
    setCategorySearch('');
    setForm({
      name: '',
      slug: '',
      description: '',
      brand_id: '',
      status: 'draft',
      main_image_url: '',
      seo_title: '',
      seo_description: '',
      category_ids: [],
      variantPrices: {},
      defaultVariant: { sku: '', name: '', price: '' },
    });
  }

  function openEdit(p: Product) {
    setIsCreate(false);
    setModalProduct(p);
    setCategorySearch('');
    setForm({
      name: p.name,
      slug: p.slug,
      description: p.description ?? '',
      brand_id: (p as { brand_id?: number }).brand_id ?? (p.brand?.id as number) ?? '',
      status: p.status,
      main_image_url: p.main_image_url ?? '',
      seo_title: p.seo_title ?? '',
      seo_description: p.seo_description ?? '',
      category_ids: p.categories?.map((c) => c.id) ?? [],
      variantPrices: {},
      defaultVariant: { sku: '', name: '', price: '' },
    });
  }

  function slugify(s: string): string {
    return s
      .trim()
      .toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '');
  }

  async function submitForm() {
    const name = form.name.trim();
    const slug =
      form.slug.trim() || (name ? slugify(form.name) : '');
    const payload: Record<string, unknown> = {
      name: name || form.name,
      slug: slug || form.slug,
      description: form.description || null,
      brand_id: form.brand_id || null,
      status: form.status,
      main_image_url: form.main_image_url || null,
      seo_title: form.seo_title || null,
      seo_description: form.seo_description || null,
      category_ids: form.category_ids,
    };
    if (isCreate) {
      const priceVal = form.defaultVariant.price;
      if (priceVal !== '' && priceVal !== null && !Number.isNaN(Number(priceVal))) {
        payload.default_variant = {
          sku: form.defaultVariant.sku || undefined,
          name: form.defaultVariant.name || undefined,
          price: Number(priceVal),
        };
      }
      createMutation.mutate(payload);
      return;
    }
    if (!modalProduct) return;
    try {
      await updateMutation.mutateAsync({ id: modalProduct.id, data: payload });
      const variants = productDetailQuery.data?.variants ?? [];
      for (const v of variants) {
        const val = form.variantPrices[v.id];
        if (val !== undefined && val !== '' && !Number.isNaN(Number(val))) {
          await adminApi.productVariantPrice.update(v.id, { amount: Number(val) });
        }
      }
      queryClient.invalidateQueries({ queryKey: ['admin-products'] });
      queryClient.invalidateQueries({ queryKey: ['admin-product', modalProduct.id] });
      closeModal();
    } catch {
      // Error already handled by mutation
    }
  }

  const listUi = listQuery.render();
  const products = listQuery.data?.data ?? [];
  const meta = listQuery.data?.meta;
  const brandList = (brands ?? []) as Brand[];
  const categoryList = (categories ?? []) as Category[];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Products</h1>
        <button type="button" onClick={openCreate} className="btn-primary flex items-center gap-2">
          <Plus className="w-4 h-4" />
          Add product
        </button>
      </div>

      <div className="card overflow-hidden">
        <div className="p-4 border-b border-surface-border sm:p-6">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
              <input
                type="search"
                aria-label="Search products"
                placeholder="Search by name or SKU..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && setSearchApplied(search)}
                className="w-full rounded-xl border border-surface-border bg-dark-50 py-2.5 pl-10 pr-4 text-text-primary placeholder:text-text-muted focus:border-accent focus:ring-1 focus:ring-accent"
              />
            </div>
            <select
              aria-label="Filter by status"
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
            >
              {STATUS_OPTIONS.map((o) => (
                <option key={o.value || 'all'} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
            <select
              aria-label="Filter by brand"
              value={brandId === '' ? '' : brandId}
              onChange={(e) => setBrandId(e.target.value === '' ? '' : Number(e.target.value))}
              className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
            >
              <option value="">All brands</option>
              {brandList.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>
            <button type="button" onClick={() => setSearchApplied(search)} className="btn-primary shrink-0">
              Apply
            </button>
          </div>
        </div>

        {listUi ? (
          <div className="p-6">{listUi}</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[640px]">
                <thead>
                  <tr className="border-b border-surface-border bg-dark-50/80">
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Product
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Status
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Brand
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Price
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {products.map((p) => {
                    const priceInfo = getProductPriceDisplay(p);
                    return (
                    <tr key={p.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3">
                        <div className="font-medium text-text-primary">{p.name}</div>
                        <div className="text-sm text-text-muted">{p.slug}</div>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${getStatusBadgeClass(p.status)}`}>
                          {p.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-text-secondary">{p.brand?.name ?? '—'}</td>
                      <td className="px-4 py-3 text-text-secondary">
                        {priceInfo ? formatCurrency(priceInfo.amount, priceInfo.currency) : '—'}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          aria-label="Edit product"
                          onClick={() => openEdit(p)}
                          className="btn-ghost text-sm text-accent hover:underline mr-2"
                        >
                          <Pencil className="w-4 h-4 inline" />
                        </button>
                        <button
                          type="button"
                          aria-label="Delete product"
                          onClick={() => window.confirm('Delete this product?') && deleteMutation.mutate(p.id)}
                          className="btn-ghost text-sm text-red-400 hover:text-red-300"
                        >
                          <Trash2 className="w-4 h-4 inline" />
                        </button>
                      </td>
                    </tr>
                  );})}
                </tbody>
              </table>
            </div>
            {products.length === 0 && (
              <div className="p-8">
                <EmptyState message="No products match your filters." />
              </div>
            )}
            {meta && meta.last_page > 1 && (
              <div className="border-t border-surface-border p-4">
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

      <AdminModal
        open={!!(isCreate || modalProduct)}
        onClose={closeModal}
        title={isCreate ? 'New product' : 'Edit product'}
        maxWidth="max-w-2xl"
        footer={
          <>
            <Button type="button" variant="secondary" onClick={closeModal}>
              Cancel
            </Button>
            <Button
              type="button"
              onClick={submitForm}
              disabled={createMutation.isPending || updateMutation.isPending}
            >
              {isCreate ? (createMutation.isPending ? 'Creating…' : 'Create') : updateMutation.isPending ? 'Saving…' : 'Save'}
            </Button>
          </>
        }
      >
        {isCreate ? (
          <AddProductForm
            form={form}
            setForm={setForm}
            categorySearch={categorySearch}
            setCategorySearch={setCategorySearch}
            brandList={brandList}
            categoryList={categoryList}
          />
        ) : (
        <div className="space-y-6 py-1">
          <FormField label="Name" htmlFor="product-name">
            <Input
              id="product-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            />
          </FormField>
          <FormField label="Slug" htmlFor="product-slug">
            <Input
              id="product-slug"
              type="text"
              value={form.slug}
              onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
            />
          </FormField>
          <FormField label="Description" htmlFor="product-description">
            <Textarea
              id="product-description"
              value={form.description}
              onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              rows={3}
            />
          </FormField>
          <FormField label="Brand" htmlFor="product-brand">
            <Select
              id="product-brand"
              value={form.brand_id === '' ? '' : form.brand_id}
              onChange={(e) => setForm((f) => ({ ...f, brand_id: e.target.value === '' ? '' : Number(e.target.value) }))}
            >
              <option value="">—</option>
              {brandList.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Status" htmlFor="product-status">
            <Select
              id="product-status"
              value={form.status}
              onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </Select>
          </FormField>
          <FormField label="Categories">
            <div className="space-y-3 mt-1">
              {form.category_ids.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {form.category_ids.map((id) => {
                    const cat = categoryList.find((c) => c.id === id);
                    return cat ? (
                      <span
                        key={id}
                        className="inline-flex items-center gap-1 rounded-lg bg-accent/15 px-2.5 py-1 text-sm text-accent"
                      >
                        <Tag className="w-3.5 h-3.5" />
                        {cat.name}
                        <button
                          type="button"
                          onClick={() =>
                            setForm((f) => ({ ...f, category_ids: f.category_ids.filter((i) => i !== id) }))
                          }
                          className="rounded p-0.5 hover:bg-accent/20"
                          aria-label={`Remove ${cat.name}`}
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </span>
                    ) : null;
                  })}
                </div>
              )}
              <input
                type="text"
                placeholder="Search categories..."
                value={categorySearch}
                onChange={(e) => setCategorySearch(e.target.value)}
                className="w-full rounded-lg border border-surface-border bg-dark-50 px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-accent focus:ring-1 focus:ring-accent"
              />
              <div className="max-h-40 overflow-y-auto rounded-lg border border-surface-border bg-dark-50/50 p-3 space-y-1">
                {(() => {
                  const filtered = categoryList.filter(
                    (c) =>
                      !categorySearch.trim() ||
                      c.name.toLowerCase().includes(categorySearch.toLowerCase())
                  );
                  return filtered.length === 0 ? (
                    <p className="px-2 py-2 text-sm text-text-muted">No categories match.</p>
                  ) : (
                    filtered.map((c) => (
                      <label
                        key={c.id}
                        className="flex items-center gap-2 cursor-pointer rounded px-2 py-1.5 text-sm text-text-secondary hover:bg-surface-hover hover:text-text-primary"
                      >
                        <input
                          type="checkbox"
                          checked={form.category_ids.includes(c.id)}
                          onChange={(e) =>
                            setForm((f) => ({
                              ...f,
                              category_ids: e.target.checked
                                ? [...f.category_ids, c.id]
                                : f.category_ids.filter((i) => i !== c.id),
                            }))
                          }
                          className="rounded border-surface-border text-accent focus:ring-accent"
                        />
                        {c.name}
                      </label>
                    ))
                  );
                })()}
              </div>
            </div>
          </FormField>

          {(productDetailQuery.data?.variants?.length ?? 0) > 0 ? (
            <FormField label="Price (USD) per variant">
              <div className="space-y-4 pt-2 rounded-lg border border-surface-border bg-dark-50/50 p-4">
                {(productDetailQuery.data?.variants ?? []).map((v) => (
                  <div key={v.id} className="flex items-center gap-3 flex-wrap">
                    <div className="flex-1 min-w-0">
                      <span className="text-sm font-medium text-text-primary">{v.name || v.sku}</span>
                      {v.name && v.sku && (
                        <span className="ml-2 text-xs text-text-muted">({v.sku})</span>
                      )}
                    </div>
                    <div className="flex items-center gap-1 shrink-0 w-32">
                      <DollarSign className="w-4 h-4 text-text-muted shrink-0" />
                      <input
                        type="number"
                        min={0}
                        step={0.01}
                        value={form.variantPrices[v.id] ?? ''}
                        onChange={(e) =>
                          setForm((f) => ({
                            ...f,
                            variantPrices: { ...f.variantPrices, [v.id]: e.target.value },
                          }))
                        }
                        placeholder="0.00"
                        className="w-full rounded-lg border border-surface-border bg-dark-50 px-3 py-2 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
                      />
                    </div>
                  </div>
                ))}
              </div>
            </FormField>
          ) : null}
          <FormField label="Main image URL" htmlFor="product-image">
            <Input
              id="product-image"
              type="url"
              value={form.main_image_url}
              onChange={(e) => setForm((f) => ({ ...f, main_image_url: e.target.value }))}
              placeholder="https://..."
            />
          </FormField>
          <FormField label="SEO title" htmlFor="product-seo-title">
            <Input
              id="product-seo-title"
              type="text"
              value={form.seo_title}
              onChange={(e) => setForm((f) => ({ ...f, seo_title: e.target.value }))}
            />
          </FormField>
          <FormField label="SEO description" htmlFor="product-seo-desc">
            <Textarea
              id="product-seo-desc"
              value={form.seo_description}
              onChange={(e) => setForm((f) => ({ ...f, seo_description: e.target.value }))}
              rows={2}
            />
          </FormField>
        </div>
        )}
      </AdminModal>
    </div>
  );
}
