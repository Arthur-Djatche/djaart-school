import DashboardLayout from '../../components/layout/DashboardLayout'
import useAuth from '../../hooks/useAuth'

export default function DashboardPage() {
  const { user } = useAuth()

  return (
    <DashboardLayout>
      <h1 className="text-2xl font-semibold text-brand-navy">
        Bienvenue{user ? `, ${user.name}` : ''}
      </h1>
      <p className="mt-2 text-slate-600">
        Votre tableau de bord DJAART SCHOOL sera personnalisé selon votre rôle dans les prochaines phases.
      </p>
    </DashboardLayout>
  )
}
