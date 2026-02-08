import { Link, useNavigate, useLocation, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { authApi, cartApi } from '../api';
import { useAuthStore } from '../store/authStore';
import { getGuestToken } from '../store/guestToken';
import { ErrorMessage } from '../components/ErrorMessage';
import { Input, Label, Button } from '../components/ui';
import { getApiErrorMessage } from '../utils/apiError';
import {
  Zap,
  Mail,
  Lock,
  ArrowRight,
  Shield,
  Eye,
  EyeOff,
} from 'lucide-react';
import { useState } from 'react';

const loginSchema = z.object({
  email: z.string().email('Please enter a valid email'),
  password: z.string().min(1, 'Password is required'),
});

type LoginForm = z.infer<typeof loginSchema>;

export function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const setAuth = useAuthStore((s) => s.setAuth);
  const from = (location.state as { from?: { pathname: string } } | null)?.from?.pathname ?? searchParams.get('redirect') ?? '/';
  const [showPassword, setShowPassword] = useState(false);
  const isCartOrCheckoutRedirect = from === '/cart' || from === '/checkout' || from.startsWith('/checkout');

  const login = useMutation({
    mutationFn: (data: LoginForm) => authApi.login(data.email, data.password),
    onSuccess: async (data) => {
      setAuth(data.token, data.user);
      if (getGuestToken()) {
        try {
          await cartApi.merge();
          queryClient.invalidateQueries({ queryKey: ['cart'] });
        } catch {
          // merge optional; cart will load on next visit
        }
      }
      navigate(from, { replace: true });
    },
  });

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
  });

  return (
    <div className="min-h-screen flex">
      {/* Left side - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-dark-100 relative overflow-hidden">
        {/* Background effects */}
        <div className="absolute inset-0">
          <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-accent/10 rounded-full blur-[100px]" />
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-[100px]" />
        </div>

        {/* Grid pattern */}
        <div className="absolute inset-0 opacity-[0.02] login-grid-pattern" />

        <div className="relative z-10 flex flex-col items-center justify-center w-full p-12">
          <Link to="/" className="flex items-center gap-3 mb-12">
            <div className="w-14 h-14 rounded-2xl bg-accent flex items-center justify-center shadow-glow">
              <Zap className="w-8 h-8 text-dark" />
            </div>
            <div>
              <span className="text-3xl font-black tracking-tight text-text-primary">
                RAZER<span className="text-accent">GOLD</span>
              </span>
              <span className="block text-xs uppercase tracking-widest text-text-muted">
                Digital Store
              </span>
            </div>
          </Link>

          <div className="text-center max-w-md">
            <h2 className="heading-md text-text-primary mb-4">
              Welcome Back
            </h2>
            <p className="text-text-secondary mb-8">
              Sign in to access your account, manage orders, and continue your gaming journey.
            </p>

            <div className="space-y-4">
              <div className="flex items-center gap-4 p-4 rounded-xl bg-dark/50 border border-surface-border">
                <div className="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                  <Shield className="w-5 h-5 text-accent" />
                </div>
                <div className="text-left">
                  <p className="text-sm font-medium text-text-primary">Secure Login</p>
                  <p className="text-xs text-text-muted">Your data is protected with 256-bit encryption</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Right side - Form */}
      <div className="flex-1 flex items-center justify-center p-8">
        <div className="w-full max-w-md">
          {/* Mobile logo */}
          <div className="lg:hidden text-center mb-8">
            <Link to="/" className="inline-flex items-center gap-2">
              <div className="w-10 h-10 rounded-xl bg-accent flex items-center justify-center">
                <Zap className="w-6 h-6 text-dark" />
              </div>
              <span className="text-xl font-black tracking-tight">
                RAZER<span className="text-accent">GOLD</span>
              </span>
            </Link>
          </div>

          <div className="text-center lg:text-left mb-8">
            <h1 className="heading-md text-text-primary mb-2">Sign In</h1>
            <p className="text-text-secondary">
              Enter your credentials to access your account
            </p>
            {isCartOrCheckoutRedirect && (
              <p className="mt-3 text-sm text-accent font-medium">
                Your cart is saved. Sign in to continue.
              </p>
            )}
          </div>

          <form onSubmit={handleSubmit((data) => login.mutate(data))} className="space-y-5">
            <div>
              <Label htmlFor="login-email">Email Address</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="login-email"
                  type="email"
                  {...register('email')}
                  autoComplete="email"
                  placeholder="your@email.com"
                  error={!!errors.email}
                  className="pl-11"
                />
              </div>
              {errors.email && (
                <p className="text-status-danger text-sm mt-1.5">{errors.email.message}</p>
              )}
            </div>

            <div>
              <Label htmlFor="login-password">Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="login-password"
                  type={showPassword ? 'text' : 'password'}
                  {...register('password')}
                  autoComplete="current-password"
                  placeholder="Enter your password"
                  error={!!errors.password}
                  className="pl-11 pr-11"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors"
                >
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              {errors.password && (
                <p className="text-status-danger text-sm mt-1.5">{errors.password.message}</p>
              )}
            </div>

            {login.isError && (
              <ErrorMessage
                message={getApiErrorMessage(login.error, 'Invalid email or password')}
              />
            )}

            <Button
              type="submit"
              variant="primary"
              disabled={login.isPending}
              className="w-full btn-lg"
            >
              {login.isPending ? (
                'Signing in...'
              ) : (
                <>
                  Sign In
                  <ArrowRight className="w-5 h-5" />
                </>
              )}
            </Button>
          </form>

          <div className="mt-8 text-center">
            <p className="text-text-secondary">
              Don't have an account?{' '}
              <Link to="/register" className="link font-medium">
                Create one
              </Link>
            </p>
          </div>

          <div className="mt-8 pt-8 border-t border-surface-border">
            <Link
              to="/"
              className="flex items-center justify-center gap-2 text-sm text-text-muted hover:text-accent transition-colors"
            >
              <ArrowRight className="w-4 h-4 rotate-180" />
              Back to Store
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
