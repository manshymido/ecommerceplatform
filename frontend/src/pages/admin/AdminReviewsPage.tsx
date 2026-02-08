import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Star, Check, X } from 'lucide-react';
import { adminApi } from '../../api/admin';
import type { Review } from '../../api/types';
import { EmptyState } from '../../components/EmptyState';
import { Pagination } from '../../components/Pagination';
import { useQueryWithUI } from '../../hooks/useQueryWithUI';
import { formatDate, getStatusBadgeClass } from '../../utils/format';

const STATUS_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

export function AdminReviewsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');

  const listQuery = useQueryWithUI({
    queryKey: ['admin-reviews', page, status || undefined],
    queryFn: () =>
      adminApi.reviews.list({
        page,
        status: status || undefined,
      }),
    fallbackMessage: 'Failed to load reviews',
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: 'approved' | 'rejected' }) =>
      adminApi.reviews.update(id, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-reviews'] });
    },
  });

  const listUi = listQuery.render();
  const reviews = listQuery.data?.data ?? [];
  const meta = listQuery.data?.meta;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-2xl font-bold text-text-primary">Reviews</h1>
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
      </div>

      <div className="card overflow-hidden">
        {listUi ? (
          <div className="p-6">{listUi}</div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[600px]">
                <thead>
                  <tr className="border-b border-surface-border bg-dark-50/80">
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Product / User
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Rating
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Content
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Date
                    </th>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Status
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-border">
                  {reviews.map((r: Review) => (
                    <tr key={r.id} className="hover:bg-surface-hover/50">
                      <td className="px-4 py-3">
                        <div className="font-medium text-text-primary">Product #{r.product_id}</div>
                        <div className="text-sm text-text-muted">{r.user_name ?? `User #${r.user_id}`}</div>
                      </td>
                      <td className="px-4 py-3">
                        <span className="inline-flex items-center gap-1 text-amber-400">
                          <Star className="w-4 h-4 fill-current" />
                          {r.rating}
                        </span>
                      </td>
                      <td className="px-4 py-3 max-w-xs">
                        {r.title && (
                          <div className="font-medium text-text-primary truncate">{r.title}</div>
                        )}
                        {r.body && (
                          <div className="text-sm text-text-muted line-clamp-2">{r.body}</div>
                        )}
                        {!r.title && !r.body && <span className="text-text-muted">—</span>}
                      </td>
                      <td className="px-4 py-3 text-sm text-text-muted">
                        {formatDate(r.created_at)}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`rounded-full px-2 py-0.5 text-xs font-medium ${getStatusBadgeClass(r.status)}`}
                        >
                          {r.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right">
                        {r.status === 'pending' && (
                          <span className="inline-flex gap-1">
                            <button
                              type="button"
                              onClick={() => updateMutation.mutate({ id: r.id, status: 'approved' })}
                              disabled={updateMutation.isPending}
                              className="btn-ghost text-sm text-green-400 hover:text-green-300 p-1 rounded"
                              aria-label="Approve review"
                            >
                              <Check className="w-4 h-4" />
                            </button>
                            <button
                              type="button"
                              onClick={() => updateMutation.mutate({ id: r.id, status: 'rejected' })}
                              disabled={updateMutation.isPending}
                              className="btn-ghost text-sm text-red-400 hover:text-red-300 p-1 rounded"
                              aria-label="Reject review"
                            >
                              <X className="w-4 h-4" />
                            </button>
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {reviews.length === 0 && (
              <div className="p-8">
                <EmptyState message="No reviews match your filters." />
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
    </div>
  );
}
