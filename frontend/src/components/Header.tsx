import { useState, useEffect } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { authApi, cartApi } from '../api';
import { useAuthStore } from '../store/authStore';
import {
  ShoppingCart,
  Heart,
  User,
  Package,
  LogOut,
  Menu,
  X,
  ChevronDown,
  Zap,
  Shield,
  Truck,
  Mail,
  Phone,
} from 'lucide-react';

const NAV_LINKS = [
  { to: '/', label: 'Home' },
  { to: '/products', label: 'Products' },
];

export function Header() {
  const location = useLocation();
  const { token, user, logout, setUser } = useAuthStore();
  // Use location.pathname as key to reset menu state on navigation
  const [menuState, setMenuState] = useState({ mobile: false, user: false, key: location.pathname });
  const [scrolled, setScrolled] = useState(false);

  // Derive menu open states, automatically resetting when pathname changes
  const mobileMenuOpen = menuState.key === location.pathname && menuState.mobile;
  const userMenuOpen = menuState.key === location.pathname && menuState.user;

  const setMobileMenuOpen = (open: boolean) => {
    setMenuState({ mobile: open, user: false, key: location.pathname });
  };

  const setUserMenuOpen = (open: boolean) => {
    setMenuState({ mobile: false, user: open, key: location.pathname });
  };

  useEffect(() => {
    if (!token) return;
    authApi.me().then((res) => setUser(res.user)).catch(() => {});
  }, [token, setUser]);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const isAdmin = user?.roles?.some((r) => r.name === 'admin' || r.name === 'super_admin') ?? false;

  const { data: cartData } = useQuery({
    queryKey: ['cart'],
    queryFn: () => cartApi.show(),
    retry: false,
  });

  const cartCount = cartData?.items?.reduce((sum: number, i: { quantity: number }) => sum + i.quantity, 0) ?? 0;

  return (
    <>
      {/* Top bar */}
      <div className="bg-dark-100 border-b border-surface-border">
        <div className="container-app">
          <div className="flex items-center justify-between py-2 text-xs text-text-muted">
            <div className="hidden sm:flex items-center gap-4">
              <span className="flex items-center gap-1.5">
                <Truck className="w-3.5 h-3.5 text-accent" />
                Free shipping over $50
              </span>
              <span className="flex items-center gap-1.5">
                <Shield className="w-3.5 h-3.5 text-accent" />
                Secure checkout
              </span>
            </div>
            <div className="flex items-center gap-4">
              <a href="mailto:support@razergold.com" className="flex items-center gap-1.5 hover:text-accent transition-colors">
                <Mail className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">support@razergold.com</span>
              </a>
              <a href="tel:+1234567890" className="flex items-center gap-1.5 hover:text-accent transition-colors">
                <Phone className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">+1 (234) 567-890</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      {/* Main header */}
      <header
        className={`sticky top-0 z-50 transition-all duration-300 ${
          scrolled
            ? 'bg-dark/95 backdrop-blur-lg border-b border-surface-border shadow-lg'
            : 'bg-transparent'
        }`}
      >
        <div className="container-app">
          <nav className="flex items-center justify-between h-16 lg:h-20">
            {/* Logo */}
            <Link to="/" className="flex items-center gap-2 group">
              <div className="w-10 h-10 rounded-xl bg-accent flex items-center justify-center shadow-glow group-hover:shadow-glow-lg transition-all duration-300">
                <Zap className="w-6 h-6 text-dark" />
              </div>
              <div className="hidden sm:block">
                <span className="text-xl font-black tracking-tight text-text-primary group-hover:text-accent transition-colors">
                  RAZER<span className="text-accent">GOLD</span>
                </span>
                <span className="block text-[10px] uppercase tracking-widest text-text-muted -mt-1">
                  Digital Store
                </span>
              </div>
            </Link>

            {/* Desktop Navigation */}
            <div className="hidden lg:flex items-center gap-1">
              {NAV_LINKS.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  className={({ isActive }) =>
                    `nav-link ${isActive ? 'active text-accent' : ''}`
                  }
                >
                  {link.label}
                </NavLink>
              ))}
            </div>

            {/* Right side actions */}
            <div className="flex items-center gap-2">
              {/* Wishlist */}
              {token && (
                <Link
                  to="/account/wishlist"
                  className="relative p-2.5 rounded-xl text-text-secondary hover:text-accent hover:bg-surface-hover transition-all"
                  title="Wishlist"
                >
                  <Heart className="w-5 h-5" />
                </Link>
              )}

              {/* Cart */}
              <Link
                to="/cart"
                className="relative p-2.5 rounded-xl text-text-secondary hover:text-accent hover:bg-surface-hover transition-all"
                title="Cart"
              >
                <ShoppingCart className="w-5 h-5" />
                {cartCount > 0 && (
                  <span className="absolute -top-1 -right-1 min-w-[20px] h-5 flex items-center justify-center rounded-full bg-accent text-dark text-xs font-bold px-1.5 shadow-glow-sm animate-fade-in">
                    {cartCount}
                  </span>
                )}
              </Link>

              {/* User menu */}
              {token && user ? (
                <div className="relative">
                  <button
                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                    className="flex items-center gap-2 p-2 rounded-xl text-text-secondary hover:text-accent hover:bg-surface-hover transition-all"
                  >
                    <div className="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-accent font-semibold text-sm">
                      {user.email?.[0]?.toUpperCase() ?? 'U'}
                    </div>
                    <ChevronDown className={`w-4 h-4 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                  </button>

                  {userMenuOpen && (
                    <>
                      <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                      <div className="absolute right-0 top-full mt-2 w-56 rounded-xl bg-surface-card border border-surface-border shadow-cardHover z-50 overflow-hidden animate-fade-in-up">
                        <div className="px-4 py-3 border-b border-surface-border">
                          <p className="text-sm font-medium text-text-primary truncate">{user.email}</p>
                          <p className="text-xs text-text-muted mt-0.5">
                            {isAdmin ? 'Administrator' : 'Customer'}
                          </p>
                        </div>
                        <div className="py-2">
                          <Link
                            to="/account/orders"
                            className="flex items-center gap-3 px-4 py-2.5 text-sm text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors"
                          >
                            <Package className="w-4 h-4" />
                            My Orders
                          </Link>
                          <Link
                            to="/account/wishlist"
                            className="flex items-center gap-3 px-4 py-2.5 text-sm text-text-secondary hover:text-accent hover:bg-surface-hover transition-colors"
                          >
                            <Heart className="w-4 h-4" />
                            Wishlist
                          </Link>
                          {isAdmin && (
                            <Link
                              to="/admin"
                              className="flex items-center gap-3 px-4 py-2.5 text-sm text-accent hover:bg-surface-hover transition-colors"
                            >
                              <Shield className="w-4 h-4" />
                              Admin Panel
                            </Link>
                          )}
                        </div>
                        <div className="border-t border-surface-border py-2">
                          <button
                            onClick={() => {
                              authApi.logout().finally(() => logout());
                            }}
                            className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-status-danger hover:bg-surface-hover transition-colors"
                          >
                            <LogOut className="w-4 h-4" />
                            Sign Out
                          </button>
                        </div>
                      </div>
                    </>
                  )}
                </div>
              ) : (
                <Link to="/login" className="btn-primary btn-sm lg:btn">
                  <User className="w-4 h-4 lg:hidden" />
                  <span className="hidden lg:inline">Sign In</span>
                </Link>
              )}

              {/* Mobile menu button */}
              <button
                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                className="lg:hidden p-2.5 rounded-xl text-text-secondary hover:text-accent hover:bg-surface-hover transition-all"
              >
                {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
              </button>
            </div>
          </nav>
        </div>

        {/* Mobile menu */}
        {mobileMenuOpen && (
          <div className="lg:hidden border-t border-surface-border bg-surface-card animate-fade-in">
            <div className="container-app py-4 space-y-2">
              {NAV_LINKS.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  className={({ isActive }) =>
                    `block px-4 py-3 rounded-xl text-sm font-medium transition-colors ${
                      isActive
                        ? 'bg-accent/10 text-accent'
                        : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary'
                    }`
                  }
                >
                  {link.label}
                </NavLink>
              ))}
              {token && (
                <>
                  <div className="divider my-4" />
                  <NavLink
                    to="/account/orders"
                    className="block px-4 py-3 rounded-xl text-sm font-medium text-text-secondary hover:bg-surface-hover hover:text-text-primary transition-colors"
                  >
                    My Orders
                  </NavLink>
                  <NavLink
                    to="/account/wishlist"
                    className="block px-4 py-3 rounded-xl text-sm font-medium text-text-secondary hover:bg-surface-hover hover:text-text-primary transition-colors"
                  >
                    Wishlist
                  </NavLink>
                </>
              )}
            </div>
          </div>
        )}
      </header>
    </>
  );
}
