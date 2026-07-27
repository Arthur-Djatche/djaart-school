import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Select from '../../../components/ui/Select'
import * as conduiteApi from '../../../api/conduiteApi'
import * as parametrageApi from '../../../api/parametrageApi'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'

const MENTIONS = [
  { value: '', label: 'Aucune' },
  { value: 'tableau_honneur', label: "Tableau d'honneur" },
  { value: 'encouragements', label: 'Encouragements' },
  { value: 'avertissement', label: 'Avertissement' },
  { value: 'blame', label: 'Blâme' },
]

export default function SaisieConduitePage() {
  const { showToast } = useToast()
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [sequences, setSequences] = useState([])
  const [sequenceId, setSequenceId] = useState('')
  const [lignes, setLignes] = useState(null)
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingGrille, setLoadingGrille] = useState(false)
  const [submitting, setSubmitting] = useState(false)
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
    setLignes(null)
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

  const chargerGrille = async () => {
    if (!classeId || !sequenceId) return
    setLignes(null)
    setError('')
    setLoadingGrille(true)
    try {
      const { data } = await conduiteApi.fetchConduite(classeId, sequenceId)
      setLignes(data.data)
    } finally {
      setLoadingGrille(false)
    }
  }

  useEffect(() => {
    if (sequenceId) chargerGrille()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sequenceId])

  const updateLigne = (inscriptionId, field, value) => {
    setLignes((current) =>
      current.map((ligne) => (ligne.inscription_id === inscriptionId ? { ...ligne, [field]: value } : ligne)),
    )
  }

  const handleSubmit = async () => {
    setSubmitting(true)
    setError('')
    try {
      const payload = lignes.map((ligne) => ({
        inscription_id: ligne.inscription_id,
        absences: Number(ligne.absences) || 0,
        absences_non_justifiees: Number(ligne.absences_non_justifiees) || 0,
        retards: Number(ligne.retards) || 0,
        mention_travail: ligne.mention_travail || null,
        mention_conduite: ligne.mention_conduite || null,
      }))
      await conduiteApi.enregistrerConduite(classeId, sequenceId, payload)
      showToast('Conduite enregistrée.', 'success')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Saisie de la conduite</h1>
        <p className="text-sm text-slate-500">
          Absences, retards et mentions (travail/conduite) par apprenant — figés sur le bulletin à la clôture de la séquence.
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
          </div>

          {error && <p className="text-sm text-red-600">{error}</p>}

          {loadingGrille && <p className="text-slate-500">Chargement de la grille…</p>}

          {lignes && !loadingGrille && (
            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-brand-navy">
                  <tr>
                    <th className="px-3 py-2 font-semibold">Apprenant</th>
                    <th className="px-3 py-2 font-semibold">Absences</th>
                    <th className="px-3 py-2 font-semibold">Dont NJ</th>
                    <th className="px-3 py-2 font-semibold">Retards</th>
                    <th className="px-3 py-2 font-semibold">Mention travail</th>
                    <th className="px-3 py-2 font-semibold">Mention conduite</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {lignes.map((ligne) => (
                    <tr key={ligne.inscription_id}>
                      <td className="px-3 py-2">
                        {ligne.apprenant.matricule} — {ligne.apprenant.prenom} {ligne.apprenant.nom}
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          min="0"
                          aria-label={`Absences ${ligne.apprenant.nom}`}
                          value={ligne.absences}
                          onChange={(e) => updateLigne(ligne.inscription_id, 'absences', e.target.value)}
                          className="w-20"
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          min="0"
                          aria-label={`Absences non justifiées ${ligne.apprenant.nom}`}
                          value={ligne.absences_non_justifiees}
                          onChange={(e) => updateLigne(ligne.inscription_id, 'absences_non_justifiees', e.target.value)}
                          className="w-20"
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          min="0"
                          aria-label={`Retards ${ligne.apprenant.nom}`}
                          value={ligne.retards}
                          onChange={(e) => updateLigne(ligne.inscription_id, 'retards', e.target.value)}
                          className="w-20"
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Select
                          aria-label={`Mention travail ${ligne.apprenant.nom}`}
                          value={ligne.mention_travail ?? ''}
                          onChange={(e) => updateLigne(ligne.inscription_id, 'mention_travail', e.target.value)}
                          options={MENTIONS}
                          className="w-44"
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Select
                          aria-label={`Mention conduite ${ligne.apprenant.nom}`}
                          value={ligne.mention_conduite ?? ''}
                          onChange={(e) => updateLigne(ligne.inscription_id, 'mention_conduite', e.target.value)}
                          options={MENTIONS}
                          className="w-44"
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              <div className="flex justify-end p-4">
                <Button onClick={handleSubmit} loading={submitting}>
                  Enregistrer
                </Button>
              </div>
            </div>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
