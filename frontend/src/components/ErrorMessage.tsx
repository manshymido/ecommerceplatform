import { AlertCircle, X } from 'lucide-react';
import { useState } from 'react';

interface ErrorMessageProps {
  message: string;
  dismissible?: boolean;
  onRetry?: () => void | Promise<unknown>;
}

export function ErrorMessage({ message, dismissible = false, onRetry }: ErrorMessageProps) {
  const [dismissed, setDismissed] = useState(false);

  if (dismissed) return null;

  return (
    <div className="flex items-start gap-3 p-4 rounded-xl bg-status-dangerBg border border-status-danger/30 animate-fade-in">
      <AlertCircle className="w-5 h-5 text-status-danger shrink-0 mt-0.5" />
      <p className="flex-1 text-sm text-status-danger">{message}</p>
      {onRetry && (
        <button
          onClick={() => onRetry()}
          className="text-status-danger hover:text-red-400 transition-colors font-medium"
        >
          Retry
        </button>
      )}
      {dismissible && !onRetry && (
        <button
          type="button"
          onClick={() => setDismissed(true)}
          className="text-status-danger hover:text-red-400 transition-colors"
          aria-label="Dismiss"
        >
          <X className="w-4 h-4" />
        </button>
      )}
    </div>
  );
}
