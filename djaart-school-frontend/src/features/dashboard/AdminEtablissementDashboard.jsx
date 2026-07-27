import Button from '../../components/ui/Button'
import MetricCard from './MetricCard'
import formatMontant from './formatMontant'
import * as dashboardApi from '../../api/dashboardApi'

export default function AdminEtablissementDashboard({ data }) {
  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <MetricCard label="Taux de recouvrement" value={`${data.taux_recouvrement} %`} accent />
        <MetricCard label="Affectations à saisir" value={data.affectations_incompletes.length} />
      </div>

      <div className="flex flex-wrap gap-3">
        <a href={dashboardApi.rapportImpayesUrl()} target="_blank" rel="noopener" className="inline-block">
          <Button variant="ghost">Rapport des impayés (PDF)</Button>
        </a>
        <a href={dashboardApi.rapportStatistiquesReussiteUrl()} target="_blank" rel="noopener" className="inline-block">
          <Button variant="ghost">Statistiques de réussite (PDF)</Button>
        </a>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Effectifs par classe</p>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-brand-navy">
              <tr>
                <th className="px-4 py-2 font-semibold">Classe</th>
                <th className="px-4 py-2 font-semibold">Effectif</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.effectifs_par_classe.length === 0 && (
                <tr><td colSpan={2} className="px-4 py-4 text-center text-slate-500">Aucune classe.</td></tr>
              )}
              {data.effectifs_par_classe.map((ligne) => (
                <tr key={ligne.classe}>
                  <td className="px-4 py-2">{ligne.classe}</td>
                  <td className="px-4 py-2">{ligne.effectif}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Affectations dont la dernière période n'est pas encore saisie</p>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-brand-navy">
              <tr>
                <th className="px-4 py-2 font-semibold">Classe</th>
                <th className="px-4 py-2 font-semibold">Matière</th>
                <th className="px-4 py-2 font-semibold">Enseignant</th>
                <th className="px-4 py-2 font-semibold">Période</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.affectations_incompletes.length === 0 && (
                <tr><td colSpan={4} className="px-4 py-4 text-center text-slate-500">Tout est à jour.</td></tr>
              )}
              {data.affectations_incompletes.map((ligne, index) => (
                <tr key={index}>
                  <td className="px-4 py-2">{ligne.classe}</td>
                  <td className="px-4 py-2">{ligne.matiere}</td>
                  <td className="px-4 py-2">{ligne.enseignant}</td>
                  <td className="px-4 py-2">{ligne.periode}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Top 5 des retards de paiement</p>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-brand-navy">
              <tr>
                <th className="px-4 py-2 font-semibold">Apprenant</th>
                <th className="px-4 py-2 font-semibold">Classe</th>
                <th className="px-4 py-2 font-semibold">Tranche</th>
                <th className="px-4 py-2 font-semibold">Solde</th>
                <th className="px-4 py-2 font-semibold">Retard</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.top_impayes.length === 0 && (
                <tr><td colSpan={5} className="px-4 py-4 text-center text-slate-500">Aucun impayé en retard.</td></tr>
              )}
              {data.top_impayes.map((ligne, index) => (
                <tr key={index}>
                  <td className="px-4 py-2">{ligne.apprenant}</td>
                  <td className="px-4 py-2">{ligne.classe}</td>
                  <td className="px-4 py-2">n° {ligne.tranche_numero}</td>
                  <td className="px-4 py-2">{formatMontant(ligne.solde)}</td>
                  <td className="px-4 py-2 font-medium text-red-600">{ligne.jours_retard} j</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
