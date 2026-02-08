import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation } from '@tanstack/react-query';
import { authApi } from '../api';
import { useAuthStore } from '../store/authStore';
import { ErrorMessage } from '../components/ErrorMessage';
import { Input, Label, Button } from '../components/ui';
import { getApiErrorMessage } from '../utils/apiError';
import {
  Zap,
  Mail,
  Lock,
  User,
  ArrowRight,
  Check,
  Eye,
  EyeOff,
} from 'lucide-react';
import { useState } from 'react';

const registerSchema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.string().email('Please enter a valid email'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

type RegisterForm = z.infer<typeof registerSchema>;

const BENEFITS = [
  'Track your orders in real-time',
  'Save items to your wishlist',
  'Faster checkout experience',
  'Exclusive member promotions',
];

export function RegisterPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);
  const [showPassword, setShowPassword] = useState(false);

  const registerMutation = useMutation({
    mutationFn: (data: RegisterForm) =>
      authApi.register(data.name, data.email, data.password, data.password_confirmation),
    onSuccess: (data) => {
      setAuth(data.token, data.user);
      navigate('/', { replace: true });
    },
  });

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegisterForm>({
    resolver: zodResolver(registerSchema),
  });

  return (
    <div className="min-h-screen flex">
      {/* Left side - Branding */}
      <div className="hidden lg:flex lg:w-1/2 bg-dark-100 relative overflow-hidden">
        {/* Background effects */}
        <div className="absolute inset-0">
          <div className="absolute top-1/4 right-1/4 w-96 h-96 bg-accent/10 rounded-full blur-[100px]" />
          <div className="absolute bottom-1/4 left-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-[100px]" />
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
              Join the Community
            </h2>
            <p className="text-text-secondary mb-8">
              Create an account to unlock exclusive benefits and streamline your gaming purchases.
            </p>

            <div className="space-y-3 text-left">
              {BENEFITS.map((benefit) => (
                <div key={benefit} className="flex items-center gap-3">
                  <div className="w-6 h-6 rounded-full bg-accent/20 flex items-center justify-center shrink-0">
                    <Check className="w-3.5 h-3.5 text-accent" />
                  </div>
                  <span className="text-sm text-text-secondary">{benefit}</span>
                </div>
              ))}
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
            <h1 className="heading-md text-text-primary mb-2">Create Account</h1>
            <p className="text-text-secondary">
              Fill in your details to get started
            </p>
          </div>

          <form onSubmit={handleSubmit((data) => registerMutation.mutate(data))} className="space-y-5">
            <div>
              <Label htmlFor="register-name">Full Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="register-name"
                  type="text"
                  {...register('name')}
                  autoComplete="name"
                  placeholder="John Doe"
                  error={!!errors.name}
                  className="pl-11"
                />
              </div>
              {errors.name && (
                <p className="text-status-danger text-sm mt-1.5">{errors.name.message}</p>
              )}
            </div>

            <div>
              <Label htmlFor="register-email">Email Address</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="register-email"
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
              <Label htmlFor="register-password">Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="register-password"
                  type={showPassword ? 'text' : 'password'}
                  {...register('password')}
                  autoComplete="new-password"
                  placeholder="Create a strong password"
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

            <div>
              <Label htmlFor="register-password-confirm">Confirm Password</Label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted" />
                <Input
                  id="register-password-confirm"
                  type={showPassword ? 'text' : 'password'}
                  {...register('password_confirmation')}
                  autoComplete="new-password"
                  placeholder="Confirm your password"
                  error={!!errors.password_confirmation}
                  className="pl-11"
                />
              </div>
              {errors.password_confirmation && (
                <p className="text-status-danger text-sm mt-1.5">{errors.password_confirmation.message}</p>
              )}
            </div>

            {registerMutation.isError && (
              <ErrorMessage
                message={getApiErrorMessage(registerMutation.error, 'Registration failed')}
              />
            )}

            <Button
              type="submit"
              variant="primary"
              disabled={registerMutation.isPending}
              className="w-full btn-lg"
            >
              {registerMutation.isPending ? (
                'Creating account...'
              ) : (
                <>
                  Create Account
                  <ArrowRight className="w-5 h-5" />
                </>
              )}
            </Button>

            <p className="text-xs text-text-muted text-center">
              By creating an account, you agree to our{' '}
              <a href="#" className="link">Terms of Service</a> and{' '}
              <a href="#" className="link">Privacy Policy</a>.
            </p>
          </form>

          <div className="mt-8 text-center">
            <p className="text-text-secondary">
              Already have an account?{' '}
              <Link to="/login" className="link font-medium">
                Sign in
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
