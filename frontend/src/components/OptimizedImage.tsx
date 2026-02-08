import { useState, useRef, useEffect } from 'react';
import { ImageOff } from 'lucide-react';

interface OptimizedImageProps {
  src: string | null | undefined;
  alt: string;
  className?: string;
  containerClassName?: string;
  fallbackIcon?: React.ReactNode;
  aspectRatio?: 'square' | 'video' | 'wide' | 'auto';
  objectFit?: 'cover' | 'contain' | 'fill';
  priority?: boolean;
}

const aspectRatioClasses = {
  square: 'aspect-square',
  video: 'aspect-video',
  wide: 'aspect-[2/1]',
  auto: '',
};

const objectFitClasses = {
  cover: 'object-cover',
  contain: 'object-contain',
  fill: 'object-fill',
};

/**
 * Optimized image component with:
 * - Lazy loading with Intersection Observer
 * - Loading skeleton state
 * - Error fallback with icon
 * - Fade-in animation on load
 * 
 * Uses the src as a key internally to reset loading state when image changes.
 */
export function OptimizedImage(props: OptimizedImageProps) {
  const { src, containerClassName = '', fallbackIcon, aspectRatio = 'square' } = props;

  // No image source - show fallback
  if (!src) {
    return (
      <div className={`relative overflow-hidden bg-dark-100 ${aspectRatioClasses[aspectRatio]} ${containerClassName}`}>
        <div className="absolute inset-0 flex items-center justify-center bg-dark-100">
          {fallbackIcon || (
            <div className="w-16 h-16 rounded-full bg-dark-200 flex items-center justify-center">
              <ImageOff className="w-8 h-8 text-text-muted" />
            </div>
          )}
        </div>
      </div>
    );
  }

  // Render keyed inner component to reset state on src change
  return <OptimizedImageInner key={src} {...props} src={src} />;
}

/**
 * Inner component that handles actual image loading.
 * Keyed by src in parent to reset state on src change.
 */
function OptimizedImageInner({
  src,
  alt,
  className = '',
  containerClassName = '',
  fallbackIcon,
  aspectRatio = 'square',
  objectFit = 'cover',
  priority = false,
}: OptimizedImageProps & { src: string }) {
  const [isLoaded, setIsLoaded] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [isInView, setIsInView] = useState(priority);
  const containerRef = useRef<HTMLDivElement>(null);

  // Intersection Observer for lazy loading
  useEffect(() => {
    if (priority || !containerRef.current) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsInView(true);
          observer.disconnect();
        }
      },
      {
        rootMargin: '50px',
        threshold: 0.01,
      }
    );

    observer.observe(containerRef.current);

    return () => observer.disconnect();
  }, [priority]);

  return (
    <div
      ref={containerRef}
      className={`relative overflow-hidden bg-dark-100 ${aspectRatioClasses[aspectRatio]} ${containerClassName}`}
    >
      {/* Loading skeleton - shown when image is loading */}
      {!isLoaded && !hasError && (
        <div className="absolute inset-0 skeleton animate-pulse" />
      )}

      {/* Actual image */}
      {isInView && !hasError && (
        <img
          src={src}
          alt={alt}
          onLoad={() => setIsLoaded(true)}
          onError={() => setHasError(true)}
          loading={priority ? 'eager' : 'lazy'}
          decoding="async"
          className={`
            w-full h-full ${objectFitClasses[objectFit]}
            transition-opacity duration-300
            ${isLoaded ? 'opacity-100' : 'opacity-0'}
            ${className}
          `}
        />
      )}

      {/* Error fallback */}
      {hasError && (
        <div className="absolute inset-0 flex items-center justify-center bg-dark-100">
          {fallbackIcon || (
            <div className="w-16 h-16 rounded-full bg-dark-200 flex items-center justify-center">
              <ImageOff className="w-8 h-8 text-text-muted" />
            </div>
          )}
        </div>
      )}
    </div>
  );
}

