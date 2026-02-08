export function formatCurrency(amount: number, currency: string = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(amount);
}

/**
 * Format a date for display. Accepts ISO string, timestamp (ms), or Date.
 * Returns "—" for null/undefined or invalid dates.
 */
export function formatDate(
  value: string | number | Date | null | undefined
): string {
  if (value == null) return '—';
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString(undefined, {
    dateStyle: 'short',
    timeStyle: 'short',
  });
}

/** Returns admin status badge class for order/payment/shipment status. */
export function getStatusBadgeClass(status: string): string {
  const s = status.toLowerCase();
  if (['completed', 'succeeded', 'shipped', 'delivered', 'paid'].includes(s))
    return 'badge-success';
  if (['pending', 'processing', 'created', 'confirmed'].includes(s))
    return 'badge-warning';
  if (['cancelled', 'failed', 'refunded', 'rejected'].includes(s))
    return 'badge-danger';
  return 'badge-neutral';
}
