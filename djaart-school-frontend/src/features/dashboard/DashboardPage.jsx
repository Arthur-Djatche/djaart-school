import { useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import useAuth from '../../hooks/useAuth'
import * as dashboardApi from '../../api/dashboardApi'
import SuperAdminDashboard from './SuperAdminDashboard'
import AdminEtablissementDashboard from './AdminEtablissementDashboard'
import ComptableDashboard from './ComptableDashboard'
import EnseignantDashboard from './EnseignantDashboard'

const DASHBOARDS_PAR_ROLE = {
  super_admin: SuperAdminDashboard,
  admin_etablissement: AdminEtablissementDashboard,
  comptable: ComptableDashboard,
  enseignant: EnseignantDashboard,
}

export default function DashboardPage() {
  const { user } = useAuth()
  const [dashboard, setDashboard] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    (async () => {
      const { data } = await dashboardApi.fetchDashboard()
      setDashboard(data.data)
      setLoading(false)
    })()
  }, [])

  const RoleDashboard = dashboard ? DASHBOARDS_PAR_ROLE[dashboard.role] : null

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">
          Bienvenue{user ? `, ${user.name}` : ''}
        </h1>
      </div>

      {loading && <p className="text-slate-500">Chargement…</p>}

      {!loading && RoleDashboard && <RoleDashboard data={dashboard.data} />}

      {!loading && !RoleDashboard && (
        <p className="text-slate-600">
          Aucun tableau de bord personnalisé n'est disponible pour votre rôle pour l'instant.
        </p>
      )}
    </DashboardLayout>
  )
}
