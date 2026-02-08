import { Loader2 } from 'lucide-react';

interface LoadingSpinnerProps {
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export function LoadingSpinner({ size = 'md', className = '' }: LoadingSpinnerProps) {
  const sizeClasses = {
    sm: 'w-5 h-5',
    md: 'w-10 h-10',
    lg: 'w-16 h-16',
  };

  return (
    <div className={`flex justify-center items-center py-12 ${className}`} role="status" aria-label="Loading">
      <div className="relative">
        {/* Glow effect */}
        <div className={`absolute inset-0 ${sizeClasses[size]} rounded-full bg-accent/20 blur-xl animate-pulse`} />
        
        {/* Spinner */}
        <Loader2 className={`${sizeClasses[size]} text-accent animate-spin relative`} />
      </div>
    </div>
  );
}
