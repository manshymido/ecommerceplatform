import { forwardRef } from 'react';

type SelectProps = React.ComponentPropsWithoutRef<'select'> & {
  error?: boolean;
};

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className = '', error, ...props }, ref) => {
    return (
      <select
        ref={ref}
        className={`select ${error ? 'border-status-danger focus:border-status-danger focus:ring-status-danger/20' : ''} ${className}`}
        {...props}
      />
    );
  }
);

Select.displayName = 'Select';
