import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Warehouse } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { EmptyState } from '../../components/EmptyState';
import { Button, FormField, Input } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';

export function AdminWarehousesPage() {
  const queryClient = useQueryClient();
  const [modalWarehouse, setModalWarehouse] = useState<Warehouse | null>(null);
  const [isCreate, setIsCreate] = useState(false);
  const [form, setForm] = useState({
    name: '',
    code: '',
    country_code: '',
    region: '',
    city: '',
  });

  const listQuery = useQueryWithUI({
    queryKey: ['admin-warehouses'],
    queryFn: () => adminApi.warehouses.list(),
    fallbackMessage: 'Failed to load warehouses',
  });

  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => adminApi.warehouses.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-warehouses'] });
      closeModal();
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Record<string, unknown> }) =>
      adminApi.warehouses.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-warehouses'] });
      closeModal();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminApi.warehouses.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-warehouses'] }),
  });

  function closeModal() {
    setModalWarehouse(null);
    setIsCreate(false);
    setForm({ name: '', code: '', country_code: '', region: '', city: '' });
  }

  function openCreate() {
    setIsCreate(true);
    setModalWarehouse(null);
    setForm({ name: '', code: '', country_code: '', region: '', city: '' });
  }

  function openEdit(w: Warehouse) {
    setIsCreate(false);
    setModalWarehouse(w);
    setForm({
      name: w.name,
      code: w.code,
      country_code: w.country_code ?? '',
      region: w.region ?? '',
      city: w.city ?? '',
    });
  }

  function submitForm() {
    const payload: Record<string, unknown> = {
      name: form.name,
      code: form.code,
      country_code: form.country_code || null,
      region: form.region || null,
      city: form.city || null,
    };
    if (isCreate) {
      createMutation.mutate(payload);
    } else if (modalWarehouse) {
      updateMutation.mutate({ id: modalWarehouse.id, data: payload });
    }
  }

  const listUi = listQuery.render();
  const warehouses = (listQuery.data ?? []) as Warehouse[];

  function locationText(w: Warehouse): string {
    return [w.city, w.region, w.country_code].filter(Boolean).join(', ') || '—';
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Warehouses</h1>
        <button type="button" onClick={openCreate} className="btn-primary flex items-center gap-2">
          <Plus className="w-4 h-4" />
          Add warehouse
        </button>
      </div>

      <div className="card overflow-hidden">
        {listUi ? (
          <div className="p-6">{listUi}</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[500px]">
                <thead>
                  <tr className="border-b border-surface-border bg-dark-50/80">
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Code
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Name
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Location
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Stock items
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {warehouses.map((w) => (
                    <tr key={w.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3 font-mono font-medium text-text-primary">{w.code}</td>
                      <td className="px-4 py-3 text-text-primary">{w.name}</td>
                      <td className="px-4 py-3 text-text-muted">{locationText(w)}</td>
                      <td className="px-4 py-3 text-right text-text-muted">
                        {w.stock_items_count ?? 0}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          aria-label="Edit warehouse"
                          onClick={() => openEdit(w)}
                          className="btn-ghost text-sm text-accent hover:underline mr-2"
                        >
                          <Pencil className="w-4 h-4 inline" />
                        </button>
                        <button
                          type="button"
                          aria-label="Delete warehouse"
                          onClick={() =>
                            window.confirm('Delete this warehouse?') && deleteMutation.mutate(w.id)
                          }
                          className="btn-ghost text-sm text-red-400 hover:text-red-300"
                        >
                          <Trash2 className="w-4 h-4 inline" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {warehouses.length === 0 && (
              <div className="p-8">
                <EmptyState message="No warehouses yet. Add one to get started." />
              </div>
            )}
          </>
        )}
      </div>

      <AdminModal
        open={!!(isCreate || modalWarehouse)}
        onClose={closeModal}
        title={isCreate ? 'New warehouse' : 'Edit warehouse'}
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
              {isCreate
                ? createMutation.isPending
                  ? 'Creating…'
                  : 'Create'
                : updateMutation.isPending
                  ? 'Saving…'
                  : 'Save'}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <FormField label="Name" htmlFor="warehouse-name">
            <Input
              id="warehouse-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            />
          </FormField>
          <FormField label="Code" htmlFor="warehouse-code">
            <Input
              id="warehouse-code"
              type="text"
              value={form.code}
              onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))}
              placeholder="WH01"
            />
          </FormField>
          <FormField label="Country code (2 letters)" htmlFor="warehouse-country">
            <Input
              id="warehouse-country"
              type="text"
              value={form.country_code}
              onChange={(e) => setForm((f) => ({ ...f, country_code: e.target.value }))}
              placeholder="US"
              maxLength={2}
            />
          </FormField>
          <FormField label="Region" htmlFor="warehouse-region">
            <Input
              id="warehouse-region"
              type="text"
              value={form.region}
              onChange={(e) => setForm((f) => ({ ...f, region: e.target.value }))}
              placeholder="California"
            />
          </FormField>
          <FormField label="City" htmlFor="warehouse-city">
            <Input
              id="warehouse-city"
              type="text"
              value={form.city}
              onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
              placeholder="Los Angeles"
            />
          </FormField>
        </div>
      </AdminModal>
    </div>
  );
}
