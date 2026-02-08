import { Navigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';

export function AdminRoute({ children }: { children: React.ReactNode }) {
  const user = useAuthStore((s) => s.user);
  const token = useAuthStore((s) => s.token);
  const isAdmin =
    user?.roles?.some(
      (r) => r.name === 'admin' || r.name === 'super_admin'
    ) ?? false;

  if (!token) {
    return <Navigate to="/login" replace />;
  }
  if (!isAdmin) {
    return <Navigate to="/" replace />;
  }
  return <>{children}</>;
}
