import { Navigate, Outlet } from 'react-router-dom'
import useAuth from '../hooks/useAuth'

export default function ProtectedRoute({ roles, permission, etablissementTypes, allowWhileMustChangePassword = false }) {
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

  // Un ecran reserve a un type d'etablissement (ex. Sequences = classique
  // uniquement) reste inaccessible par URL directe meme si le role/droit
  // correspond — pas seulement masque dans le menu. super_admin exempte
  // (support/depannage, ne depend d'aucun etablissement precis).
  const typesUtilisateur = [user.etablissement?.type_etablissement].filter(Boolean)
  const parType = !etablissementTypes || user.roles.includes('super_admin') || etablissementTypes.some((type) => typesUtilisateur.includes(type))

  if ((!parRole && !parPermission) || !parType) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
