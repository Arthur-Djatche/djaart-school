import { Navigate, Outlet } from 'react-router-dom'
import useAuth from '../hooks/useAuth'

export default function ProtectedRoute({ roles, permission }) {
  const { user, loading } = useAuth()

  if (loading) {
    return <div className="flex min-h-screen items-center justify-center text-brand-navy">Chargement…</div>
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  const parRole = !roles || roles.some((role) => user.roles.includes(role))
  const parPermission = Boolean(permission) && (user.permissions ?? []).includes(permission)

  if (!parRole && !parPermission) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
