import type { ReactNode } from 'react';
import { Label } from './Label';

interface FormFieldProps {
  label: string;
  htmlFor?: string;
  error?: string;
  children: ReactNode;
  className?: string;
}

export function FormField({ label, htmlFor, error, children, className = '' }: FormFieldProps) {
  return (
    <div className={className}>
      <Label htmlFor={htmlFor}>{label}</Label>
      {children}
      {error && (
        <p className="mt-1 text-sm text-status-danger" role="alert">
          {error}
        </p>
      )}
    </div>
  );
}
