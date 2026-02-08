import type { LucideIcon } from 'lucide-react';
import {
  Clock,
  Package,
  Truck,
  CheckCircle,
  XCircle,
  AlertCircle,
} from 'lucide-react';

export interface OrderStatusConfig {
  icon: LucideIcon;
  color: string;
  bg: string;
  label: string;
}

const defaultConfig: OrderStatusConfig = {
  icon: Clock,
  color: 'text-status-warning',
  bg: 'bg-status-warningBg',
  label: 'Pending',
};

export const ORDER_STATUS_CONFIG: Record<string, OrderStatusConfig> = {
  pending: { ...defaultConfig },
  pending_payment: { icon: Clock, color: 'text-status-warning', bg: 'bg-status-warningBg', label: 'Pending payment' },
  processing: { icon: Package, color: 'text-status-info', bg: 'bg-status-infoBg', label: 'Processing' },
  shipped: { icon: Truck, color: 'text-status-info', bg: 'bg-status-infoBg', label: 'Shipped' },
  delivered: { icon: CheckCircle, color: 'text-status-success', bg: 'bg-status-successBg', label: 'Delivered' },
  completed: { icon: CheckCircle, color: 'text-status-success', bg: 'bg-status-successBg', label: 'Completed' },
  paid: { icon: CheckCircle, color: 'text-status-success', bg: 'bg-status-successBg', label: 'Paid' },
  fulfilled: { icon: CheckCircle, color: 'text-status-success', bg: 'bg-status-successBg', label: 'Fulfilled' },
  cancelled: { icon: XCircle, color: 'text-status-danger', bg: 'bg-status-dangerBg', label: 'Cancelled' },
  refunded: { icon: AlertCircle, color: 'text-status-warning', bg: 'bg-status-warningBg', label: 'Refunded' },
};

export function getOrderStatusConfig(status: string): OrderStatusConfig {
  return ORDER_STATUS_CONFIG[status] ?? { ...defaultConfig, label: status.replace(/_/g, ' ') };
}
