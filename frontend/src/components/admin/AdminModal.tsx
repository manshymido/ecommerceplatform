import type { ReactNode } from 'react';

interface AdminModalProps {
  open: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  footer: ReactNode;
  /** Optional max width class (default: max-w-lg) */
  maxWidth?: string;
}

export function AdminModal({
  open,
  onClose,
  title,
  children,
  footer,
  maxWidth = 'max-w-lg',
}: AdminModalProps) {
  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 overflow-y-auto sm:items-center sm:py-8"
      onClick={onClose}
    >
      <div
        className={`card w-full ${maxWidth} shadow-xl my-4 sm:my-8 flex max-h-[calc(100vh-2rem)] min-h-0 flex-col overflow-hidden`}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="shrink-0 p-6 border-b border-surface-border">
          <h2 className="text-lg font-semibold text-text-primary">{title}</h2>
        </div>
        <div className="min-h-0 flex-1 overflow-y-auto p-6">
          {children}
        </div>
        <div className="shrink-0 p-6 border-t border-surface-border bg-dark-50/50 flex gap-2 justify-end">
          {footer}
        </div>
      </div>
    </div>
  );
}
