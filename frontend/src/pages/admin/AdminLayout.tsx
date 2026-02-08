import { useState } from 'react';
import { Link, Outlet, useLocation, useParams } from 'react-router-dom';
import {
  LayoutDashboard,
  Package,
  Layers,
  Tag,
  ShoppingCart,
  Warehouse,
  BarChart3,
  Star,
  ArrowLeft,
  Menu,
  X,
  Zap,
  ChevronRight,
} from 'lucide-react';

const navItems = [
  { to: '/admin', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/admin/products', label: 'Products', icon: Package, end: false },
  { to: '/admin/categories', label: 'Categories', icon: Layers, end: false },
  { to: '/admin/brands', label: 'Brands', icon: Tag, end: false },
  { to: '/admin/orders', label: 'Orders', icon: ShoppingCart, end: false },
  { to: '/admin/warehouses', label: 'Warehouses', icon: Warehouse, end: false },
  { to: '/admin/stock', label: 'Stock', icon: BarChart3, end: false },
  { to: '/admin/reviews', label: 'Reviews', icon: Star, end: false },
];

const pathLabels: Record<string, string> = {
  '': 'Dashboard',
  products: 'Products',
  categories: 'Categories',
  brands: 'Brands',
  orders: 'Orders',
  warehouses: 'Warehouses',
  stock: 'Stock',
  reviews: 'Reviews',
};

function AdminBreadcrumbs() {
  const location = useLocation();
  const params = useParams();
  const segments = location.pathname.replace(/^\/admin\/?/, '').split('/').filter(Boolean);
  const crumbs: { label: string; to: string | null }[] = [{ label: 'Dashboard', to: '/admin' }];
  let path = '/admin';

  for (let i = 0; i < segments.length; i++) {
    const seg = segments[i];
    const isLast = i === segments.length - 1;
    if (seg === 'orders' && segments[i + 1] && params.id) {
      path = `/admin/orders/${params.id}`;
      crumbs.push({ label: 'Orders', to: '/admin/orders' });
      crumbs.push({ label: `Order #${params.id}`, to: null });
      break;
    }
    path = path + (path === '/admin' ? '' : '/') + seg;
    const label = pathLabels[seg] ?? seg;
    crumbs.push({ label: isLast && !pathLabels[seg] ? `#${seg}` : label, to: isLast ? null : path });
  }

  return (
    <nav className="mb-6 flex items-center gap-2 text-sm" aria-label="Breadcrumb">
      {crumbs.map((c, i) => (
        <span key={i} className="flex items-center gap-2">
          {i > 0 && <ChevronRight className="w-4 h-4 text-text-muted" />}
          {c.to ? (
            <Link to={c.to} className="text-text-muted hover:text-accent transition-colors">
              {c.label}
            </Link>
          ) : (
            <span className="text-text-primary font-medium">{c.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}

export function AdminLayout() {
  const location = useLocation();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <div className="min-h-screen bg-dark">
      <div className="flex">
        {/* Sidebar - Desktop */}
        <aside className="sticky top-0 hidden h-screen w-64 shrink-0 border-r border-surface-border bg-dark-50 lg:block">
          <div className="flex h-full flex-col">
            {/* Logo */}
            <div className="p-6 border-b border-surface-border">
              <Link to="/admin" className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-accent flex items-center justify-center shadow-glow-sm">
                  <Zap className="w-5 h-5 text-dark" />
                </div>
                <div>
                  <span className="font-bold text-text-primary">Admin</span>
                  <span className="block text-xs text-text-muted">RazerGold</span>
                </div>
              </Link>
            </div>

            {/* Navigation */}
            <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
              {navItems.map(({ to, label, icon: Icon, end }) => {
                const active = end
                  ? location.pathname === '/admin' || location.pathname === '/admin/'
                  : location.pathname.startsWith(to);
                return (
                  <Link
                    key={to}
                    to={to}
                    className={`flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all ${
                      active
                        ? 'bg-accent/10 text-accent border border-accent/30'
                        : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                    }`}
                  >
                    <Icon className={`w-5 h-5 ${active ? 'text-accent' : ''}`} />
                    {label}
                  </Link>
                );
              })}
            </nav>

            {/* Back to storefront */}
            <div className="p-4 border-t border-surface-border">
              <Link
                to="/"
                className="flex items-center gap-2 rounded-xl px-4 py-3 text-sm text-text-muted hover:bg-surface-hover hover:text-accent transition-colors"
              >
                <ArrowLeft className="w-4 h-4" />
                Back to Store
              </Link>
            </div>
          </div>
        </aside>

        {/* Main content */}
        <main className="min-w-0 flex-1">
          {/* Mobile header */}
          <div className="lg:hidden sticky top-0 z-40 bg-dark-50 border-b border-surface-border">
            <div className="flex items-center justify-between p-4">
              <Link to="/admin" className="flex items-center gap-2">
                <div className="w-8 h-8 rounded-lg bg-accent flex items-center justify-center">
                  <Zap className="w-4 h-4 text-dark" />
                </div>
                <span className="font-bold text-text-primary">Admin</span>
              </Link>
              <button
                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                className="p-2 rounded-lg text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors"
              >
                {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
              </button>
            </div>

            {/* Mobile menu */}
            {mobileMenuOpen && (
              <div className="border-t border-surface-border bg-surface-card p-4 animate-fade-in">
                <nav className="space-y-1">
                  {navItems.map(({ to, label, icon: Icon }) => (
                    <Link
                      key={to}
                      to={to}
                      onClick={() => setMobileMenuOpen(false)}
                      className="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-text-secondary hover:bg-surface-hover hover:text-text-primary transition-colors"
                    >
                      <Icon className="w-5 h-5" />
                      {label}
                    </Link>
                  ))}
                </nav>
                <div className="mt-4 pt-4 border-t border-surface-border">
                  <Link
                    to="/"
                    className="flex items-center gap-2 text-sm text-text-muted hover:text-accent transition-colors"
                  >
                    <ArrowLeft className="w-4 h-4" />
                    Back to Store
                  </Link>
                </div>
              </div>
            )}
          </div>

          {/* Page content */}
          <div className="p-6 lg:p-8">
            <AdminBreadcrumbs />
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
