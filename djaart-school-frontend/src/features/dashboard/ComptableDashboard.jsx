import Button from '../../components/ui/Button'
import MetricCard from './MetricCard'
import formatMontant from './formatMontant'
import * as dashboardApi from '../../api/dashboardApi'

export default function ComptableDashboard({ data }) {
  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <MetricCard label="Encaissé aujourd'hui" value={formatMontant(data.encaisse_aujourdhui)} accent />
        <MetricCard label="Encaissé ce mois" value={formatMontant(data.encaisse_mois)} />
      </div>

      <div>
        <a href={dashboardApi.rapportImpayesUrl()} target="_blank" rel="noopener" className="inline-block">
          <Button variant="ghost">Rapport des impayés (PDF)</Button>
        </a>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Impayés en retard</p>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 text-brand-navy">
              <tr>
                <th className="px-4 py-2 font-semibold">Apprenant</th>
                <th className="px-4 py-2 font-semibold">Matricule</th>
                <th className="px-4 py-2 font-semibold">Classe</th>
                <th className="px-4 py-2 font-semibold">Tranche</th>
                <th className="px-4 py-2 font-semibold">Solde</th>
                <th className="px-4 py-2 font-semibold">Retard</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {data.impayes.length === 0 && (
                <tr><td colSpan={6} className="px-4 py-4 text-center text-slate-500">Aucun impayé en retard.</td></tr>
              )}
              {data.impayes.map((ligne, index) => (
                <tr key={index}>
                  <td className="px-4 py-2">{ligne.apprenant}</td>
                  <td className="px-4 py-2">{ligne.matricule}</td>
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
