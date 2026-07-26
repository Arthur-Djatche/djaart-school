import { Navigate, Outlet } from 'react-router-dom'
import useAuth from '../hooks/useAuth'

export default function ProtectedRoute({ roles }) {
  const { user, loading } = useAuth()

  if (loading) {
    return <div className="flex min-h-screen items-center justify-center text-brand-navy">Chargement…</div>
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (roles && !roles.some((role) => user.roles.includes(role))) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
