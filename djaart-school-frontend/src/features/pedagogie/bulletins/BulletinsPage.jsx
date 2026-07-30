import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Select from '../../../components/ui/Select'
import * as bulletinsApi from '../../../api/bulletinsApi'
import * as parametrageApi from '../../../api/parametrageApi'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'

export default function BulletinsPage() {
  const { showToast } = useToast()
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [sequences, setSequences] = useState([])
  const [sequenceId, setSequenceId] = useState('')
  const [bulletins, setBulletins] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingBulletins, setLoadingBulletins] = useState(false)
  const [cloturant, setCloturant] = useState(false)
  const [error, setError] = useState('')

  const classe = classes.find((c) => c.id === Number(classeId))

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchClasses({ page: 1 })
      setClasses(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  useEffect(() => {
    setSequenceId('')
    setBulletins([])
    if (!classe) {
      setSequences([])
      return
    }
    (async () => {
      const { data } = await pedagogieApi.fetchSequences({
        niveau_id: classe.niveau_id,
        annee_academique_id: classe.annee_academique_id,
      })
      setSequences(data.data)
    })()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [classeId])

  const chargerBulletins = async (cId, sId) => {
    setLoadingBulletins(true)
    try {
      const { data } = await bulletinsApi.fetchBulletins({ classe_id: cId, sequence_id: sId })
      setBulletins(data.data)
    } finally {
      setLoadingBulletins(false)
    }
  }

  useEffect(() => {
    if (classeId && sequenceId) chargerBulletins(classeId, sequenceId)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [classeId, sequenceId])

  const handleCloturer = async () => {
    setError('')
    setCloturant(true)
    try {
      await bulletinsApi.cloturerSequence(classeId, sequenceId)
      showToast('Séquence clôturée, bulletins générés.', 'success')
      await chargerBulletins(classeId, sequenceId)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setCloturant(false)
    }
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Bulletins</h1>
        <p className="text-sm text-slate-500">Clôturez une séquence pour générer les bulletins de toute la classe.</p>
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
            {classe && (
              <Select
                id="sequence_id"
                label="Séquence"
                value={sequenceId}
                onChange={(e) => setSequenceId(e.target.value)}
                placeholder="Sélectionner une séquence"
                options={sequences.map((s) => ({ value: s.id, label: s.libelle }))}
                className="md:flex-1"
              />
            )}
            {classeId && sequenceId && (
              <Button onClick={handleCloturer} loading={cloturant}>
                Clôturer la séquence
              </Button>
            )}
            {classeId && (
              <a href={bulletinsApi.bulletinAnnuelDetailleUrl(classeId)} target="_blank" rel="noopener">
                <Button variant="outline">Bulletin annuel détaillé (classe)</Button>
              </a>
            )}
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          {loadingBulletins ? (
            <p className="text-slate-500">Chargement…</p>
          ) : bulletins.length > 0 ? (
            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-brand-navy">
                  <tr>
                    <th className="px-4 py-3 font-semibold">Apprenant</th>
                    <th className="px-4 py-3 font-semibold">Moyenne</th>
                    <th className="px-4 py-3 font-semibold">Rang</th>
                    <th className="px-4 py-3 font-semibold">Bulletin</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {bulletins.map((b) => (
                    <tr key={b.id}>
                      <td className="px-4 py-3">{b.apprenant.matricule} — {b.apprenant.prenom} {b.apprenant.nom}</td>
                      <td className="px-4 py-3">{b.moyenne_generale} / 20</td>
                      <td className="px-4 py-3">{b.rang}</td>
                      <td className="px-4 py-3">
                        <a
                          href={bulletinsApi.bulletinDownloadUrl(b.id)}
                          target="_blank"
                          rel="noopener"
                          className="text-brand-blue hover:underline"
                        >
                          Télécharger
                        </a>
                        <span className="mx-1 text-slate-300">·</span>
                        <a
                          href={bulletinsApi.bulletinJumeleUrl(b.id)}
                          target="_blank"
                          rel="noopener"
                          className="text-brand-blue hover:underline"
                        >
                          Jumelé
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            classeId && sequenceId && <p className="text-sm text-slate-500">Aucun bulletin généré pour l'instant.</p>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
