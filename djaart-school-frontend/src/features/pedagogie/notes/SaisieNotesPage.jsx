import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Select from '../../../components/ui/Select'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'

const TYPES_EVALUATION_LMD = [
  { value: 'cc', label: 'Contrôle Continu (CC)' },
  { value: 'session_normale', label: 'Session Normale (SN)' },
  { value: 'rattrapage', label: 'Rattrapage (SN)' },
]

export default function SaisieNotesPage() {
  const { showToast } = useToast()
  const [affectations, setAffectations] = useState([])
  const [loadingAffectations, setLoadingAffectations] = useState(true)
  const [affectationId, setAffectationId] = useState('')

  const [decoupages, setDecoupages] = useState([])
  const [decoupageId, setDecoupageId] = useState('')
  const [typeEvaluation, setTypeEvaluation] = useState('cc')

  const [grille, setGrille] = useState(null)
  const [saisie, setSaisie] = useState({})
  const [loadingGrille, setLoadingGrille] = useState(false)
  const [submitting, setSubmitting] = useState(false)

  const affectation = affectations.find((a) => a.id === Number(affectationId))
  const estLmd = affectation?.classe?.type_systeme === 'lmd'

  useEffect(() => {
    (async () => {
      const { data } = await pedagogieApi.fetchAffectations({ page: 1 })
      setAffectations(data.data)
      setLoadingAffectations(false)
    })()
  }, [])

  useEffect(() => {
    setDecoupageId('')
    setGrille(null)
    if (!affectation) {
      setDecoupages([])
      return
    }
    (async () => {
      const params = { niveau_id: affectation.classe.niveau_id, annee_academique_id: affectation.annee_academique_id }
      const { data } = estLmd
        ? await pedagogieApi.fetchSemestres(params)
        : await pedagogieApi.fetchSequences(params)
      setDecoupages(data.data)
    })()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [affectationId])

  const chargerGrille = async () => {
    if (!decoupageId) return
    setGrille(null)
    setLoadingGrille(true)
    try {
      const params = estLmd
        ? { semestre_id: decoupageId, type_evaluation: typeEvaluation }
        : { sequence_id: decoupageId, type_evaluation: 'sequence' }
      const { data } = await pedagogieApi.fetchNotes(affectationId, params)
      setGrille(data.data)
      const initial = {}
      data.data.notes.forEach((ligne) => {
        initial[ligne.apprenant.id] = { valeur: ligne.valeur ?? '', absent: ligne.absent }
      })
      setSaisie(initial)
    } finally {
      setLoadingGrille(false)
    }
  }

  useEffect(() => {
    if (decoupageId) chargerGrille()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [decoupageId, typeEvaluation])

  const updateSaisie = (apprenantId, field, value) => {
    setSaisie((current) => ({ ...current, [apprenantId]: { ...current[apprenantId], [field]: value } }))
  }

  const handleSubmit = async () => {
    setSubmitting(true)
    try {
      const notes = Object.entries(saisie).map(([apprenantId, ligne]) => ({
        apprenant_id: Number(apprenantId),
        absent: !!ligne.absent,
        valeur: ligne.absent ? null : ligne.valeur === '' ? null : Number(ligne.valeur),
      }))
      const payload = estLmd
        ? { semestre_id: Number(decoupageId), type_evaluation: typeEvaluation, notes }
        : { sequence_id: Number(decoupageId), type_evaluation: 'sequence', notes }

      await pedagogieApi.soumettreNotes(affectationId, payload)
      showToast('Notes soumises et verrouillées.', 'success')
      chargerGrille()
    } catch (err) {
      showToast(err.response?.data?.message ?? 'Une erreur est survenue.', 'error')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Saisie des notes</h1>
        <p className="text-sm text-slate-500">Sélectionnez une affectation puis la période à noter.</p>
      </div>

      {loadingAffectations ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 md:flex-row">
            <Select
              id="affectation_id"
              label="Affectation"
              value={affectationId}
              onChange={(e) => setAffectationId(e.target.value)}
              placeholder="Sélectionner une affectation"
              options={affectations.map((a) => ({ value: a.id, label: `${a.matiere.nom} — ${a.classe.libelle}` }))}
              className="md:flex-1"
            />
            {affectation && (
              <Select
                id="decoupage_id"
                label={estLmd ? 'Semestre' : 'Séquence'}
                value={decoupageId}
                onChange={(e) => setDecoupageId(e.target.value)}
                placeholder={estLmd ? 'Sélectionner un semestre' : 'Sélectionner une séquence'}
                options={decoupages.map((d) => ({ value: d.id, label: d.libelle }))}
                className="md:flex-1"
              />
            )}
            {affectation && decoupageId && (
              <a
                href={estLmd ? pedagogieApi.pvSemestreUrl(affectationId, decoupageId) : pedagogieApi.pvSequenceUrl(affectationId, decoupageId)}
                target="_blank"
                rel="noopener"
                className="whitespace-nowrap rounded-lg border border-brand-blue px-4 py-2 text-sm font-medium text-brand-blue hover:bg-brand-blue/5"
              >
                Imprimer le PV
              </a>
            )}
            {affectation && estLmd && (
              <Select
                id="type_evaluation"
                label="Type d'évaluation"
                value={typeEvaluation}
                onChange={(e) => setTypeEvaluation(e.target.value)}
                options={TYPES_EVALUATION_LMD}
                className="md:flex-1"
              />
            )}
          </div>

          {affectation && estLmd && typeEvaluation === 'rattrapage' && (
            <p className="text-sm text-brand-orange">
              Le rattrapage n'est saisissable que pour un apprenant dont la moyenne CC/SN est inférieure à 10, une fois CC et SN verrouillés.
            </p>
          )}

          {loadingGrille && <p className="text-slate-500">Chargement de la grille…</p>}

          {grille && !loadingGrille && (
            <div className="rounded-lg border border-slate-200 bg-white p-4">
              {grille.verrouille && (
                <p className="mb-3 rounded bg-slate-50 px-3 py-2 text-sm text-brand-navy">
                  Cette saisie est verrouillée. Contactez un administrateur pour la modifier.
                </p>
              )}

              <div className="flex flex-col divide-y divide-slate-100">
                {grille.notes.map((ligne) => (
                  <div key={ligne.apprenant.id} className="flex items-center gap-4 py-2">
                    <span className="flex-1 text-sm">
                      {ligne.apprenant.matricule} — {ligne.apprenant.prenom} {ligne.apprenant.nom}
                    </span>
                    <Input
                      type="number"
                      min="0"
                      max="20"
                      step="0.5"
                      aria-label={`Note ${ligne.apprenant.nom}`}
                      value={saisie[ligne.apprenant.id]?.valeur ?? ''}
                      onChange={(e) => updateSaisie(ligne.apprenant.id, 'valeur', e.target.value)}
                      disabled={grille.verrouille || saisie[ligne.apprenant.id]?.absent}
                      className="w-24"
                    />
                    <label className="flex items-center gap-1 text-sm text-slate-600">
                      <input
                        type="checkbox"
                        checked={!!saisie[ligne.apprenant.id]?.absent}
                        onChange={(e) => updateSaisie(ligne.apprenant.id, 'absent', e.target.checked)}
                        disabled={grille.verrouille}
                      />
                      Absent
                    </label>
                  </div>
                ))}
              </div>

              {!grille.verrouille && (
                <div className="mt-4 flex justify-end">
                  <Button onClick={handleSubmit} loading={submitting}>
                    Soumettre
                  </Button>
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
