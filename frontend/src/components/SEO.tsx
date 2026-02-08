import { useEffect } from 'react';

interface SEOProps {
  title?: string;
  description?: string;
  image?: string;
  url?: string;
  type?: 'website' | 'product' | 'article';
  noIndex?: boolean;
}

const DEFAULT_TITLE = 'RazerGold - Digital Gaming Store';
const DEFAULT_DESCRIPTION = 'Your trusted destination for digital products and gaming credits. Fast, secure, and reliable.';

/**
 * SEO component for managing document head metadata.
 * Updates title and meta tags for better search engine optimization.
 */
export function SEO({
  title,
  description = DEFAULT_DESCRIPTION,
  image,
  url,
  type = 'website',
  noIndex = false,
}: SEOProps) {
  const fullTitle = title ? `${title} | RazerGold` : DEFAULT_TITLE;

  useEffect(() => {
    // Update document title
    document.title = fullTitle;

    // Helper to set or create meta tag
    const setMetaTag = (name: string, content: string, isProperty = false) => {
      const attribute = isProperty ? 'property' : 'name';
      let element = document.querySelector(`meta[${attribute}="${name}"]`);
      
      if (!element) {
        element = document.createElement('meta');
        element.setAttribute(attribute, name);
        document.head.appendChild(element);
      }
      
      element.setAttribute('content', content);
    };

    // Standard meta tags
    setMetaTag('description', description);
    
    if (noIndex) {
      setMetaTag('robots', 'noindex, nofollow');
    } else {
      setMetaTag('robots', 'index, follow');
    }

    // Open Graph tags
    setMetaTag('og:title', fullTitle, true);
    setMetaTag('og:description', description, true);
    setMetaTag('og:type', type, true);
    
    if (url) {
      setMetaTag('og:url', url, true);
    }
    
    if (image) {
      setMetaTag('og:image', image, true);
    }

    // Twitter Card tags
    setMetaTag('twitter:card', image ? 'summary_large_image' : 'summary');
    setMetaTag('twitter:title', fullTitle);
    setMetaTag('twitter:description', description);
    
    if (image) {
      setMetaTag('twitter:image', image);
    }

    // Cleanup function to reset to defaults when component unmounts
    return () => {
      document.title = DEFAULT_TITLE;
    };
  }, [fullTitle, description, image, url, type, noIndex]);

  return null;
}

