import MetricCard from './MetricCard'
import formatMontant from './formatMontant'

const LABELS_ROLES = {
  super_admin: 'Super admin',
  admin_etablissement: 'Admin établissement',
  secretaire: 'Secrétaire',
  comptable: 'Comptable',
  enseignant: 'Enseignant',
  apprenant: 'Apprenant',
}

export default function SuperAdminDashboard({ data }) {
  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard label="Établissements" value={data.nombre_etablissements} />
        <MetricCard label="Apprenants (toutes inscriptions actives)" value={data.total_apprenants} />
        <MetricCard label="Encaissé ce mois (tous établissements)" value={formatMontant(data.total_encaisse_mois)} accent />
        <MetricCard label="Utilisateurs" value={Object.values(data.utilisateurs_par_role).reduce((a, b) => a + b, 0)} />
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Utilisateurs par rôle</p>
        <div className="flex flex-wrap gap-3">
          {Object.entries(data.utilisateurs_par_role).map(([role, total]) => (
            <span key={role} className="rounded-full bg-slate-100 px-3 py-1 text-sm text-brand-navy">
              {LABELS_ROLES[role] ?? role} : <strong>{total}</strong>
            </span>
          ))}
        </div>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Par établissement</p>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-brand-navy">
              <tr>
                <th className="px-4 py-2 font-semibold">Établissement</th>
                <th className="px-4 py-2 font-semibold">Effectif</th>
                <th className="px-4 py-2 font-semibold">Encaissé ce mois</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.par_etablissement.map((ligne) => (
                <tr key={ligne.etablissement}>
                  <td className="px-4 py-2">{ligne.etablissement}</td>
                  <td className="px-4 py-2">{ligne.effectif}</td>
                  <td className="px-4 py-2">{formatMontant(ligne.encaisse_mois)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
