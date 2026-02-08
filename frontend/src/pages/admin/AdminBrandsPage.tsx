import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Brand } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { EmptyState } from '../../components/EmptyState';
import { Button, FormField, Input } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';

export function AdminBrandsPage() {
  const queryClient = useQueryClient();
  const [modalBrand, setModalBrand] = useState<Brand | null>(null);
  const [isCreate, setIsCreate] = useState(false);
  const [form, setForm] = useState({ name: '', slug: '' });

  const listQuery = useQueryWithUI({
    queryKey: ['admin-brands'],
    queryFn: () => adminApi.brands.list(),
    fallbackMessage: 'Failed to load brands',
  });

  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => adminApi.brands.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-brands'] });
      closeModal();
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Record<string, unknown> }) =>
      adminApi.brands.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-brands'] });
      closeModal();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminApi.brands.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-brands'] }),
  });

  function closeModal() {
    setModalBrand(null);
    setIsCreate(false);
    setForm({ name: '', slug: '' });
  }

  function openCreate() {
    setIsCreate(true);
    setModalBrand(null);
    setForm({ name: '', slug: '' });
  }

  function openEdit(b: Brand) {
    setIsCreate(false);
    setModalBrand(b);
    setForm({ name: b.name, slug: b.slug });
  }

  function submitForm() {
    if (isCreate) {
      createMutation.mutate(form);
    } else if (modalBrand) {
      updateMutation.mutate({ id: modalBrand.id, data: form });
    }
  }

  const listUi = listQuery.render();
  const brands = (listQuery.data ?? []) as Brand[];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Brands</h1>
        <button type="button" onClick={openCreate} className="btn-primary flex items-center gap-2">
          <Plus className="w-4 h-4" />
          Add brand
        </button>
      </div>

      <div className="card overflow-hidden">
        {listUi ? (
          <div className="p-6">{listUi}</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[400px]">
                <thead>
                  <tr className="border-b border-surface-border bg-dark-50/80">
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Name
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Slug
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Products
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {brands.map((b) => (
                    <tr key={b.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3 font-medium text-text-primary">{b.name}</td>
                      <td className="px-4 py-3 font-mono text-sm text-text-muted">{b.slug}</td>
                      <td className="px-4 py-3 text-right text-text-muted">
                        {b.products_count ?? 0}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          aria-label="Edit brand"
                          onClick={() => openEdit(b)}
                          className="btn-ghost text-sm text-accent hover:underline mr-2"
                        >
                          <Pencil className="w-4 h-4 inline" />
                        </button>
                        <button
                          type="button"
                          aria-label="Delete brand"
                          onClick={() =>
                            window.confirm('Delete this brand?') && deleteMutation.mutate(b.id)
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
            {brands.length === 0 && (
              <div className="p-8">
                <EmptyState message="No brands yet. Add one to get started." />
              </div>
            )}
          </>
        )}
      </div>

      <AdminModal
        open={!!(isCreate || modalBrand)}
        onClose={closeModal}
        title={isCreate ? 'New brand' : 'Edit brand'}
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
          <FormField label="Name" htmlFor="brand-name">
            <Input
              id="brand-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            />
          </FormField>
          <FormField label="Slug" htmlFor="brand-slug">
            <Input
              id="brand-slug"
              type="text"
              value={form.slug}
              onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
            />
          </FormField>
        </div>
      </AdminModal>
    </div>
  );
}
