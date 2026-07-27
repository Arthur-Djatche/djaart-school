export default function EnseignantDashboard({ data }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4">
      <p className="mb-3 font-medium text-brand-navy">Mes affectations</p>
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50 text-brand-navy">
            <tr>
              <th className="px-4 py-2 font-semibold">Classe</th>
              <th className="px-4 py-2 font-semibold">Matière</th>
              <th className="px-4 py-2 font-semibold">Dernière période</th>
              <th className="px-4 py-2 font-semibold">Statut</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {data.affectations.length === 0 && (
              <tr><td colSpan={4} className="px-4 py-4 text-center text-slate-500">Aucune affectation.</td></tr>
            )}
            {data.affectations.map((ligne, index) => (
              <tr key={index}>
                <td className="px-4 py-2">{ligne.classe}</td>
                <td className="px-4 py-2">{ligne.matiere}</td>
                <td className="px-4 py-2">{ligne.periode ?? '—'}</td>
                <td className="px-4 py-2">
                  {ligne.periode === null ? (
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Aucune période configurée</span>
                  ) : ligne.soumis ? (
                    <span className="rounded-full bg-brand-teal/10 px-2 py-0.5 text-xs font-medium text-brand-teal">Soumis</span>
                  ) : (
                    <span className="rounded-full bg-brand-orange/10 px-2 py-0.5 text-xs font-medium text-brand-orange">À saisir</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
