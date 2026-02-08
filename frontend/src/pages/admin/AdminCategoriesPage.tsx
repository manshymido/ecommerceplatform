import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Category } from '../../api/types';
import { AdminModal } from '../../components/admin/AdminModal';
import { EmptyState } from '../../components/EmptyState';
import { Button, FormField, Input, Select } from '../../components/ui';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';

export function AdminCategoriesPage() {
  const queryClient = useQueryClient();
  const [modalCategory, setModalCategory] = useState<Category | null>(null);
  const [isCreate, setIsCreate] = useState(false);
  const [form, setForm] = useState({
    name: '',
    slug: '',
    parent_id: '' as number | '',
    position: 0,
  });

  const listQuery = useQueryWithUI({
    queryKey: ['admin-categories'],
    queryFn: () => adminApi.categories.list(),
    fallbackMessage: 'Failed to load categories',
  });

  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => adminApi.categories.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-categories'] });
      closeModal();
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Record<string, unknown> }) =>
      adminApi.categories.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-categories'] });
      closeModal();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => adminApi.categories.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-categories'] }),
  });

  function closeModal() {
    setModalCategory(null);
    setIsCreate(false);
    setForm({ name: '', slug: '', parent_id: '', position: 0 });
  }

  function openCreate() {
    setIsCreate(true);
    setModalCategory(null);
    setForm({ name: '', slug: '', parent_id: '', position: 0 });
  }

  function openEdit(c: Category) {
    setIsCreate(false);
    setModalCategory(c);
    setForm({
      name: c.name,
      slug: c.slug,
      parent_id: c.parent_id ?? '',
      position: c.position ?? 0,
    });
  }

  function submitForm() {
    const payload: Record<string, unknown> = {
      name: form.name,
      slug: form.slug,
      parent_id: form.parent_id || null,
      position: form.position,
    };
    if (isCreate) {
      createMutation.mutate(payload);
    } else if (modalCategory) {
      updateMutation.mutate({ id: modalCategory.id, data: payload });
    }
  }

  const listUi = listQuery.render();
  const categories = (listQuery.data ?? []) as Category[];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Categories</h1>
        <button type="button" onClick={openCreate} className="btn-primary flex items-center gap-2">
          <Plus className="w-4 h-4" />
          Add category
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
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Parent
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Position
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {categories.map((c) => (
                    <tr key={c.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3 font-medium text-text-primary">{c.name}</td>
                      <td className="px-4 py-3 font-mono text-sm text-text-muted">{c.slug}</td>
                      <td className="px-4 py-3 text-text-secondary">{c.parent?.name ?? '—'}</td>
                      <td className="px-4 py-3 text-text-muted">{c.position ?? 0}</td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          aria-label="Edit category"
                          onClick={() => openEdit(c)}
                          className="btn-ghost text-sm text-accent hover:underline mr-2"
                        >
                          <Pencil className="w-4 h-4 inline" />
                        </button>
                        <button
                          type="button"
                          aria-label="Delete category"
                          onClick={() =>
                            window.confirm('Delete this category?') && deleteMutation.mutate(c.id)
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
            {categories.length === 0 && (
              <div className="p-8">
                <EmptyState message="No categories yet. Add one to get started." />
              </div>
            )}
          </>
        )}
      </div>

      <AdminModal
        open={!!(isCreate || modalCategory)}
        onClose={closeModal}
        title={isCreate ? 'New category' : 'Edit category'}
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
          <FormField label="Name" htmlFor="category-name">
            <Input
              id="category-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            />
          </FormField>
          <FormField label="Slug" htmlFor="category-slug">
            <Input
              id="category-slug"
              type="text"
              value={form.slug}
              onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
            />
          </FormField>
          <FormField label="Parent" htmlFor="category-parent">
            <Select
              id="category-parent"
              value={form.parent_id === '' ? '' : form.parent_id}
              onChange={(e) =>
                setForm((f) => ({
                  ...f,
                  parent_id: e.target.value === '' ? '' : Number(e.target.value),
                }))
              }
            >
              <option value="">None</option>
              {categories
                .filter((cat) => cat.id !== modalCategory?.id)
                .map((cat) => (
                  <option key={cat.id} value={cat.id}>
                    {cat.name}
                  </option>
                ))}
            </Select>
          </FormField>
          <FormField label="Position" htmlFor="category-position">
            <Input
              id="category-position"
              type="number"
              min={0}
              value={form.position}
              onChange={(e) => setForm((f) => ({ ...f, position: parseInt(e.target.value, 10) || 0 }))}
            />
          </FormField>
        </div>
      </AdminModal>
    </div>
  );
}
