import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Package, History, Search, Filter, MapPin } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { StockByVariant, StockMovement, Warehouse } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { EmptyState } from '../../components/EmptyState';
import { Pagination } from '../../components/Pagination';
import { Button, FormField, Input } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import { formatDate } from '../../utils/format';

type Tab = 'inventory' | 'movements';

export function AdminStockPage() {
  const queryClient = useQueryClient();
  const [tab, setTab] = useState<Tab>('inventory');
  const [assignRow, setAssignRow] = useState<StockByVariant | null>(null);
  const [assignWarehouseId, setAssignWarehouseId] = useState<number | ''>('');
  const [assignQuantity, setAssignQuantity] = useState<number>(0);
  const [assignReason, setAssignReason] = useState('assignment');
  const [addVariantId, setAddVariantId] = useState<string>('');
  const [addWarehouseId, setAddWarehouseId] = useState<number | ''>('');
  const [addQuantity, setAddQuantity] = useState<number>(0);

  // Inventory filters & query
  const [invPage, setInvPage] = useState(1);
  const [invWarehouseId, setInvWarehouseId] = useState<number | ''>('');
  const [invSearch, setInvSearch] = useState('');
  const [invSearchDebounced, setInvSearchDebounced] = useState('');

  // Movement filters & query
  const [movPage, setMovPage] = useState(1);
  const [movWarehouseId, setMovWarehouseId] = useState<number | ''>('');
  const [movType, setMovType] = useState('');
  const [movSearch, setMovSearch] = useState('');
  const [movSearchDebounced, setMovSearchDebounced] = useState('');

  const { data: warehouses } = useQuery({
    queryKey: ['admin-warehouses'],
    queryFn: () => adminApi.warehouses.list(),
  });
  const warehouseList = warehouses ?? [];

  const invQuery = useQueryWithUI({
    queryKey: [
      'admin-stock-by-variant',
      invPage,
      invWarehouseId || undefined,
      invSearchDebounced,
    ],
    queryFn: () =>
      adminApi.stock.listByVariant({
        page: invPage,
        warehouse_id: invWarehouseId || undefined,
        search: invSearchDebounced || undefined,
      }),
    fallbackMessage: 'Failed to load stock',
  });

  const movQuery = useQueryWithUI({
    queryKey: [
      'admin-stock-movements',
      movPage,
      movWarehouseId || undefined,
      movType || undefined,
      movSearchDebounced,
    ],
    queryFn: () =>
      adminApi.stock.movements({
        page: movPage,
        warehouse_id: movWarehouseId || undefined,
        type: movType || undefined,
        search: movSearchDebounced || undefined,
      }),
    fallbackMessage: 'Failed to load movements',
  });

  const assignMutation = useMutation({
    mutationFn: (payload: {
      product_variant_id: number;
      warehouse_id: number;
      quantity: number;
      reason_code?: string;
    }) => adminApi.stock.assign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-stock-by-variant'] });
      queryClient.invalidateQueries({ queryKey: ['admin-stock-movements'] });
      setAssignRow(null);
      setAssignWarehouseId('');
      setAssignQuantity(0);
      setAssignReason('assignment');
    },
  });

  const openAssign = (row: StockByVariant) => {
    setAssignRow(row);
    setAssignWarehouseId(warehouseList.length ? warehouseList[0].id : '');
    setAssignQuantity(row.total_quantity || 0);
    setAssignReason('assignment');
  };

  const submitAssign = () => {
    if (!assignRow || assignWarehouseId === '') return;
    assignMutation.mutate({
      product_variant_id: assignRow.product_variant_id,
      warehouse_id: Number(assignWarehouseId),
      quantity: Math.max(0, assignQuantity),
      reason_code: assignReason || 'assignment',
    });
  };

  const submitAddStock = () => {
    const variantId = parseInt(addVariantId, 10);
    if (!variantId || addWarehouseId === '') return;
    assignMutation.mutate({
      product_variant_id: variantId,
      warehouse_id: Number(addWarehouseId),
      quantity: Math.max(0, addQuantity),
      reason_code: 'assignment',
    });
    setAddVariantId('');
    setAddWarehouseId(warehouseList.length ? warehouseList[0].id : '');
    setAddQuantity(0);
  };

  // Debounce search inputs (simple: commit on blur or after 400ms)
  const commitInvSearch = () => setInvSearchDebounced(invSearch);
  const commitMovSearch = () => setMovSearchDebounced(movSearch);

  const invUI = invQuery.render();
  const movUI = movQuery.render();

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Stock</h1>
        <div className="flex rounded-xl border border-surface-border bg-surface-card p-1">
          <button
            type="button"
            onClick={() => setTab('inventory')}
            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all ${
              tab === 'inventory'
                ? 'bg-accent/10 text-accent border border-accent/30'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'
            }`}
          >
            <Package className="w-4 h-4" />
            Inventory
          </button>
          <button
            type="button"
            onClick={() => setTab('movements')}
            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all ${
              tab === 'movements'
                ? 'bg-accent/10 text-accent border border-accent/30'
                : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'
            }`}
          >
            <History className="w-4 h-4" />
            Movement history
          </button>
        </div>
      </div>

      {tab === 'inventory' && (
        <div className="card overflow-hidden">
          <div className="p-4 border-b border-surface-border sm:p-6">
            <div className="mb-4 rounded-xl border border-surface-border bg-dark-50/50 p-4">
              <p className="mb-3 text-sm font-medium text-text-secondary">
                Add stock for a product (by variant ID)
              </p>
              <div className="flex flex-wrap items-end gap-3">
                <FormField label="Variant ID" className="w-28">
                  <Input
                    type="number"
                    min={1}
                    placeholder="ID"
                    value={addVariantId}
                    onChange={(e) => setAddVariantId(e.target.value)}
                    className="font-mono"
                  />
                </FormField>
                <FormField label="Warehouse" className="min-w-[180px]">
                  <select
                    aria-label="Warehouse"
                    value={addWarehouseId === '' ? '' : addWarehouseId}
                    onChange={(e) =>
                      setAddWarehouseId(e.target.value === '' ? '' : Number(e.target.value))
                    }
                    className="w-full rounded-xl border border-surface-border bg-dark-50 px-4 py-2 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
                  >
                    <option value="">Select</option>
                    {warehouseList.map((w: Warehouse) => (
                      <option key={w.id} value={w.id}>
                        {w.code} – {w.name}
                      </option>
                    ))}
                  </select>
                </FormField>
                <FormField label="Quantity" className="w-24">
                  <Input
                    type="number"
                    min={0}
                    value={addQuantity}
                    onChange={(e) =>
                      setAddQuantity(Math.max(0, parseInt(e.target.value, 10) || 0))
                    }
                    className="font-mono"
                  />
                </FormField>
                <Button
                  type="button"
                  onClick={submitAddStock}
                  disabled={
                    !addVariantId ||
                    addWarehouseId === '' ||
                    assignMutation.isPending
                  }
                >
                  {assignMutation.isPending ? 'Saving…' : 'Add stock'}
                </Button>
              </div>
              <p className="mt-2 text-xs text-text-muted">
                Variant ID is shown on the product edit page (variants table).
              </p>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                <input
                  type="search"
                  placeholder="Search by product or SKU..."
                  value={invSearch}
                  onChange={(e) => setInvSearch(e.target.value)}
                  onBlur={commitInvSearch}
                  onKeyDown={(e) => e.key === 'Enter' && commitInvSearch()}
                  className="w-full rounded-xl border border-surface-border bg-dark-50 py-2.5 pl-10 pr-4 text-text-primary placeholder:text-text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                />
              </div>
              <div className="flex items-center gap-2">
                <Filter className="h-4 w-4 text-text-muted" />
                <select
                  aria-label="Filter by warehouse"
                  value={invWarehouseId === '' ? '' : invWarehouseId}
                  onChange={(e) =>
                    setInvWarehouseId(
                      e.target.value === '' ? '' : Number(e.target.value)
                    )
                  }
                  className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
                >
                  <option value="">All warehouses</option>
                  {warehouseList.map((w: Warehouse) => (
                    <option key={w.id} value={w.id}>
                      {w.code} – {w.name}
                    </option>
                  ))}
                </select>
              </div>
              <button
                type="button"
                onClick={commitInvSearch}
                className="btn-primary shrink-0"
              >
                Apply
              </button>
            </div>
          </div>
          {invUI ? (
            <div className="p-6">{invUI}</div>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full min-w-[640px]">
                  <thead>
                    <tr className="border-b border-surface-border bg-dark-50/80">
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Product / Variant
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Total quantity
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Warehouses
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-surface-border">
                    {(invQuery.data?.data ?? []).map((row: StockByVariant) => (
                      <tr
                        key={row.product_variant_id}
                        className="hover:bg-surface-hover/50"
                      >
                        <td className="px-4 py-3">
                          <div className="font-medium text-text-primary">
                            {row.product_variant?.product?.name ?? '—'}
                          </div>
                          <div className="text-sm text-text-muted">
                            {row.product_variant?.sku ?? '—'}
                            {row.product_variant?.name
                              ? ` · ${row.product_variant.name}`
                              : ''}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-right font-mono text-text-primary">
                          {row.total_quantity}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex flex-wrap gap-x-3 gap-y-1 text-sm text-text-secondary">
                            {(row.warehouses ?? []).length === 0 ? (
                              <span className="text-text-muted">No warehouse assigned</span>
                            ) : (
                              (row.warehouses ?? []).map((w) => (
                                <span key={w.warehouse_id} className="inline-flex items-center gap-1">
                                  <MapPin className="w-3.5 h-3.5 text-text-muted" />
                                  {w.warehouse_code ?? w.warehouse_name ?? `#${w.warehouse_id}`}:{' '}
                                  <strong className="font-mono text-text-primary">{w.quantity}</strong>
                                </span>
                              ))
                            )}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-right">
                          <button
                            type="button"
                            onClick={() => openAssign(row)}
                            className="btn-ghost text-sm text-accent hover:underline"
                          >
                            Assign stock
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {(invQuery.data?.data ?? []).length === 0 && (
                <div className="p-8">
                  <EmptyState message="No products with stock match your filters. Use Assign stock to set quantity per warehouse." />
                </div>
              )}
              {invQuery.data?.meta && invQuery.data.meta.last_page > 1 && (
                <div className="border-t border-surface-border p-4">
                  <Pagination
                    currentPage={invQuery.data.meta.current_page}
                    lastPage={invQuery.data.meta.last_page}
                    onPageChange={setInvPage}
                  />
                </div>
              )}
            </>
          )}
        </div>
      )}

      {tab === 'movements' && (
        <div className="card overflow-hidden">
          <div className="p-4 border-b border-surface-border sm:p-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                <input
                  type="search"
                  placeholder="Search product, SKU, warehouse..."
                  value={movSearch}
                  onChange={(e) => setMovSearch(e.target.value)}
                  onBlur={commitMovSearch}
                  onKeyDown={(e) => e.key === 'Enter' && commitMovSearch()}
                  className="w-full rounded-xl border border-surface-border bg-dark-50 py-2.5 pl-10 pr-4 text-text-primary placeholder:text-text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                />
              </div>
              <select
                aria-label="Filter movements by warehouse"
                value={movWarehouseId === '' ? '' : movWarehouseId}
                onChange={(e) =>
                  setMovWarehouseId(
                    e.target.value === '' ? '' : Number(e.target.value)
                  )
                }
                className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
              >
                <option value="">All warehouses</option>
                {warehouseList.map((w: Warehouse) => (
                  <option key={w.id} value={w.id}>
                    {w.code} – {w.name}
                  </option>
                ))}
              </select>
              <select
                aria-label="Filter movements by type"
                value={movType}
                onChange={(e) => setMovType(e.target.value)}
                className="rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
              >
                <option value="">All types</option>
                <option value="adjustment">Adjustment</option>
                <option value="sale">Sale</option>
                <option value="restock">Restock</option>
                <option value="return">Return</option>
                <option value="transfer">Transfer</option>
              </select>
              <button
                type="button"
                onClick={commitMovSearch}
                className="btn-primary shrink-0"
              >
                Apply
              </button>
            </div>
          </div>
          {movUI ? (
            <div className="p-6">{movUI}</div>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full min-w-[640px]">
                  <thead>
                    <tr className="border-b border-surface-border bg-dark-50/80">
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Date
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Product / Variant
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Warehouse
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Type
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Quantity
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                        Reason
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-surface-border">
                    {(movQuery.data?.data ?? []).map((m: StockMovement) => (
                      <tr key={m.id} className="hover:bg-surface-hover/50">
                        <td className="px-4 py-3 text-sm text-text-muted">
                          {formatDate(m.created_at)}
                        </td>
                        <td className="px-4 py-3">
                          <div className="font-medium text-text-primary">
                            {m.product_variant?.product?.name ?? '—'}
                          </div>
                          <div className="text-sm text-text-muted">
                            {m.product_variant?.sku ?? '—'}
                          </div>
                        </td>
                        <td className="px-4 py-3 text-text-secondary">
                          {m.warehouse?.code ?? '—'}
                        </td>
                        <td className="px-4 py-3">
                          <span className="rounded-full bg-dark-100 px-2 py-0.5 text-xs font-medium text-text-secondary">
                            {m.type}
                          </span>
                        </td>
                        <td
                          className={`px-4 py-3 text-right font-mono ${
                            m.quantity > 0
                              ? 'text-green-400'
                              : 'text-red-400'
                          }`}
                        >
                          {m.quantity > 0 ? '+' : ''}
                          {m.quantity}
                        </td>
                        <td className="px-4 py-3 text-sm text-text-muted">
                          {m.reason_code ?? '—'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {(movQuery.data?.data ?? []).length === 0 && (
                <div className="p-8">
                  <EmptyState message="No movements match your filters." />
                </div>
              )}
              {movQuery.data?.meta && movQuery.data.meta.last_page > 1 && (
                <div className="border-t border-surface-border p-4">
                  <Pagination
                    currentPage={movQuery.data.meta.current_page}
                    lastPage={movQuery.data.meta.last_page}
                    onPageChange={setMovPage}
                  />
                </div>
              )}
            </>
          )}
        </div>
      )}

      <AdminModal
        open={!!assignRow}
        onClose={() => !assignMutation.isPending && setAssignRow(null)}
        title="Assign stock"
        footer={
          <>
            <Button type="button" variant="secondary" onClick={() => setAssignRow(null)}>
              Cancel
            </Button>
            <Button
              type="button"
              onClick={submitAssign}
              disabled={!assignRow || assignWarehouseId === '' || assignMutation.isPending}
            >
              {assignMutation.isPending ? 'Saving…' : 'Save'}
            </Button>
          </>
        }
      >
        {assignRow && (
          <>
            <p className="text-sm text-text-muted mb-1">
              {assignRow.product_variant?.product?.name} · {assignRow.product_variant?.sku}
            </p>
            <p className="text-sm text-text-secondary mb-4">
              Total quantity across warehouses: <strong>{assignRow.total_quantity}</strong>
            </p>
            <div className="space-y-4">
              <FormField label="Warehouse">
                <select
                  aria-label="Warehouse"
                  value={assignWarehouseId === '' ? '' : assignWarehouseId}
                  onChange={(e) =>
                    setAssignWarehouseId(e.target.value === '' ? '' : Number(e.target.value))
                  }
                  className="w-full rounded-xl border border-surface-border bg-dark-50 px-4 py-2.5 text-sm text-text-primary focus:border-accent focus:ring-1 focus:ring-accent"
                >
                  <option value="">Select warehouse</option>
                  {warehouseList.map((w: Warehouse) => (
                    <option key={w.id} value={w.id}>
                      {w.code} – {w.name}
                    </option>
                  ))}
                </select>
              </FormField>
              <FormField label="Quantity (set for this warehouse)">
                <Input
                  type="number"
                  min={0}
                  value={assignQuantity}
                  onChange={(e) =>
                    setAssignQuantity(Math.max(0, parseInt(e.target.value, 10) || 0))
                  }
                  className="w-24 font-mono"
                />
              </FormField>
              <FormField label="Reason code" htmlFor="assign-reason">
                <Input
                  id="assign-reason"
                  type="text"
                  value={assignReason}
                  onChange={(e) => setAssignReason(e.target.value)}
                  placeholder="e.g. assignment"
                  maxLength={50}
                />
              </FormField>
            </div>
            {assignMutation.isError && (
              <p className="mt-4 text-sm text-status-danger" role="alert">
                {assignMutation.error?.message ?? 'Failed to assign stock.'}
              </p>
            )}
          </>
        )}
      </AdminModal>
    </div>
  );
}
