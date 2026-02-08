import { type ReactNode } from 'react';
import { Inbox } from 'lucide-react';

interface EmptyStateProps {
  message: string;
  description?: string;
  icon?: ReactNode;
  action?: ReactNode;
  className?: string;
}

export function EmptyState({
  message,
  description,
  icon,
  action,
  className = '',
}: EmptyStateProps) {
  return (
    <div className={`p-12 text-center ${className}`.trim()}>
      <div className="w-16 h-16 mx-auto rounded-full bg-dark-200 flex items-center justify-center mb-4">
        {icon ?? <Inbox className="w-8 h-8 text-text-muted" />}
      </div>
      <h3 className="font-semibold text-text-primary mb-2">{message}</h3>
      {description && (
        <p className="text-text-muted text-sm max-w-sm mx-auto">{description}</p>
      )}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}
