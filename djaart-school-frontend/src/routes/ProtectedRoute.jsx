import { Navigate, Outlet } from 'react-router-dom'
import useAuth from '../hooks/useAuth'

export default function ProtectedRoute({ roles, permission, allowWhileMustChangePassword = false }) {
  const { user, loading } = useAuth()

  if (loading) {
    return <div className="flex min-h-screen items-center justify-center text-brand-navy">Chargement…</div>
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (user.must_change_password && !allowWhileMustChangePassword) {
    return <Navigate to="/changer-mot-de-passe" replace />
  }

  const parRole = !roles || roles.some((role) => user.roles.includes(role))
  const parPermission = Boolean(permission) && (user.permissions ?? []).includes(permission)

  if (!parRole && !parPermission) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
