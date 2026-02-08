import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Home, Search, Zap } from 'lucide-react';

export function NotFoundPage() {
  useEffect(() => {
    document.title = 'Page Not Found | RazerGold';
  }, []);

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <div className="text-center max-w-lg">
        {/* Animated 404 */}
        <div className="relative mb-8">
          {/* Background glow */}
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="w-64 h-64 bg-accent/10 rounded-full blur-[80px]" />
          </div>

          {/* 404 text */}
          <div className="relative">
            <h1 className="text-[150px] sm:text-[200px] font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-dark-300 to-dark-100 leading-none select-none">
              404
            </h1>
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="w-20 h-20 rounded-full bg-accent/20 flex items-center justify-center animate-float">
                <Zap className="w-10 h-10 text-accent" />
              </div>
            </div>
          </div>
        </div>

        {/* Message */}
        <h2 className="heading-md text-text-primary mb-4">Page Not Found</h2>
        <p className="text-text-secondary mb-8 max-w-md mx-auto">
          Oops! The page you're looking for seems to have vanished into the digital void. 
          Let's get you back on track.
        </p>

        {/* Actions */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link to="/" className="btn-primary btn-lg">
            <Home className="w-5 h-5" />
            Back to Home
          </Link>
          <Link to="/products" className="btn-secondary btn-lg">
            <Search className="w-5 h-5" />
            Browse Products
          </Link>
        </div>

        {/* Helpful links */}
        <div className="mt-12 pt-8 border-t border-surface-border">
          <p className="text-sm text-text-muted mb-4">Or try these popular pages:</p>
          <div className="flex flex-wrap items-center justify-center gap-4">
            <Link to="/products" className="link-subtle text-sm">Products</Link>
            <span className="text-dark-300">·</span>
            <Link to="/cart" className="link-subtle text-sm">Cart</Link>
            <span className="text-dark-300">·</span>
            <Link to="/account/orders" className="link-subtle text-sm">Orders</Link>
            <span className="text-dark-300">·</span>
            <Link to="/login" className="link-subtle text-sm">Sign In</Link>
          </div>
        </div>
      </div>
    </div>
  );
}
