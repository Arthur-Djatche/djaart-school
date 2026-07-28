import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Select from '../../../components/ui/Select'
import * as bulletinsApi from '../../../api/bulletinsApi'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'

export default function RelevesPage() {
  const { showToast } = useToast()
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [releves, setReleves] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingReleves, setLoadingReleves] = useState(false)
  const [generant, setGenerant] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchClasses({ page: 1 })
      setClasses(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  const chargerReleves = async (cId) => {
    setLoadingReleves(true)
    try {
      const { data } = await bulletinsApi.fetchReleves({ classe_id: cId })
      setReleves(data.data)
    } finally {
      setLoadingReleves(false)
    }
  }

  useEffect(() => {
    setReleves([])
    if (classeId) chargerReleves(classeId)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [classeId])

  const handleGenerer = async () => {
    setError('')
    setGenerant(true)
    try {
      await bulletinsApi.genererRelevesAnnuels(classeId)
      showToast('Relevés annuels générés.', 'success')
      await chargerReleves(classeId)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setGenerant(false)
    }
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Relevés de notes</h1>
        <p className="text-sm text-slate-500">
          Générez le relevé officiel annuel de toute une classe — 3 séquences (classique) ou 2 semestres combinés (LMD).
        </p>
      </div>

      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 md:flex-row md:items-end">
            <Select
              id="classe_id"
              label="Classe"
              value={classeId}
              onChange={(e) => setClasseId(e.target.value)}
              placeholder="Sélectionner une classe"
              options={classes.map((c) => ({ value: c.id, label: c.libelle }))}
              className="md:flex-1"
            />
            {classeId && (
              <Button onClick={handleGenerer} loading={generant}>
                Générer le relevé annuel
              </Button>
            )}
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          {loadingReleves ? (
            <p className="text-slate-500">Chargement…</p>
          ) : releves.length > 0 ? (
            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-brand-navy">
                  <tr>
                    <th className="px-4 py-3 font-semibold">Apprenant</th>
                    <th className="px-4 py-3 font-semibold">Moyenne</th>
                    <th className="px-4 py-3 font-semibold">Mention</th>
                    <th className="px-4 py-3 font-semibold">Relevé</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {releves.map((r) => (
                    <tr key={r.id}>
                      <td className="px-4 py-3">{r.apprenant.matricule} — {r.apprenant.prenom} {r.apprenant.nom}</td>
                      <td className="px-4 py-3">{r.moyenne_generale} / 20</td>
                      <td className="px-4 py-3">{r.mention}</td>
                      <td className="px-4 py-3">
                        <a
                          href={bulletinsApi.releveDownloadUrl(r.id)}
                          target="_blank"
                          rel="noopener"
                          className="text-brand-blue hover:underline"
                        >
                          Télécharger
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            classeId && <p className="text-sm text-slate-500">Aucun relevé généré pour l'instant.</p>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
