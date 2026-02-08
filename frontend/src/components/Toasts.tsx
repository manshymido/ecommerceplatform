import { useEffect } from 'react';
import { useToastStore } from '../store/toastStore';
import { CheckCircle, AlertCircle, Info, X, AlertTriangle } from 'lucide-react';

const TOAST_CONFIG = {
  success: {
    icon: CheckCircle,
    bgColor: 'bg-status-successBg',
    borderColor: 'border-status-success/30',
    iconColor: 'text-status-success',
    textColor: 'text-status-success',
  },
  error: {
    icon: AlertCircle,
    bgColor: 'bg-status-dangerBg',
    borderColor: 'border-status-danger/30',
    iconColor: 'text-status-danger',
    textColor: 'text-status-danger',
  },
  warning: {
    icon: AlertTriangle,
    bgColor: 'bg-status-warningBg',
    borderColor: 'border-status-warning/30',
    iconColor: 'text-status-warning',
    textColor: 'text-status-warning',
  },
  info: {
    icon: Info,
    bgColor: 'bg-status-infoBg',
    borderColor: 'border-status-info/30',
    iconColor: 'text-status-info',
    textColor: 'text-status-info',
  },
};

export function Toasts() {
  const toasts = useToastStore((s) => s.toasts);
  const remove = useToastStore((s) => s.remove);

  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col-reverse gap-3 max-w-sm w-full pointer-events-none">
      {toasts.map((toast) => (
        <Toast key={toast.id} toast={toast} onRemove={() => remove(toast.id)} />
      ))}
    </div>
  );
}

interface ToastProps {
  toast: {
    id: string;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
  };
  onRemove: () => void;
}

function Toast({ toast, onRemove }: ToastProps) {
  const config = TOAST_CONFIG[toast.type];
  const Icon = config.icon;

  useEffect(() => {
    const timer = setTimeout(() => {
      onRemove();
    }, 4000);
    return () => clearTimeout(timer);
  }, [onRemove]);

  return (
    <div
      className={`pointer-events-auto flex items-start gap-3 p-4 rounded-xl border ${config.bgColor} ${config.borderColor} shadow-cardHover animate-slide-in-right`}
    >
      <Icon className={`w-5 h-5 ${config.iconColor} shrink-0 mt-0.5`} />
      <p className={`flex-1 text-sm font-medium ${config.textColor}`}>{toast.message}</p>
      <button
        type="button"
        onClick={onRemove}
        className={`${config.iconColor} hover:opacity-70 transition-opacity shrink-0`}
        aria-label="Dismiss"
      >
        <X className="w-4 h-4" />
      </button>
    </div>
  );
}
