import { useEffect, useMemo, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'

const MODES = [
  { value: 'comptant', label: 'Comptant' },
  { value: 'tranches', label: 'Par tranches' },
]

async function loadAllNiveaux() {
  const { data: filieresResponse } = await parametrageApi.fetchFilieres({ page: 1 })
  const niveauxLists = await Promise.all(
    filieresResponse.data.map(async (filiere) => {
      const { data } = await parametrageApi.fetchNiveauxByFiliere(filiere.id)
      return data.data.map((niveau) => ({ ...niveau, filiereNom: filiere.nom }))
    }),
  )
  return niveauxLists.flat()
}

function addMonths(dateString, months) {
  const date = new Date(dateString)
  date.setMonth(date.getMonth() + months)
  return date.toISOString().slice(0, 10)
}

export default function FraisScolariteFormModal({ fraisScolarite, onClose, onSubmit }) {
  const [niveaux, setNiveaux] = useState([])
  const [annees, setAnnees] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)

  const [niveauId, setNiveauId] = useState(fraisScolarite?.niveau_id ?? '')
  const [anneeAcademiqueId, setAnneeAcademiqueId] = useState(fraisScolarite?.annee_academique_id ?? '')
  const [montantTotal, setMontantTotal] = useState(fraisScolarite?.montant_total ?? '')
  const [mode, setMode] = useState(fraisScolarite?.mode ?? 'comptant')
  const [nombreTranches, setNombreTranches] = useState(fraisScolarite?.nombre_tranches ?? 2)
  const [tranches, setTranches] = useState(
    fraisScolarite?.tranches?.map((t) => ({ numero: t.numero, montant: t.montant, date_echeance: t.date_echeance })) ?? [],
  )
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    (async () => {
      const [niveauxList, anneesResponse] = await Promise.all([
        loadAllNiveaux(),
        parametrageApi.fetchAnneesAcademiques({ page: 1 }),
      ])
      setNiveaux(niveauxList)
      setAnnees(anneesResponse.data.data)
      setLoadingOptions(false)
    })()
  }, [])

  const sommeTranches = useMemo(
    () => tranches.reduce((sum, t) => sum + Number(t.montant || 0), 0),
    [tranches],
  )
  const sommeOk = mode !== 'tranches' || Math.abs(sommeTranches - Number(montantTotal || 0)) < 0.01

  const handleRepartir = () => {
    const annee = annees.find((a) => a.id === Number(anneeAcademiqueId))
    const dateDebut = annee?.date_debut ?? new Date().toISOString().slice(0, 10)
    const n = Number(nombreTranches) || 1
    const montantParTranche = Math.round((Number(montantTotal || 0) / n) * 100) / 100
    const lignes = Array.from({ length: n }, (_, i) => ({
      numero: i + 1,
      montant: i === n - 1 ? Number(montantTotal || 0) - montantParTranche * (n - 1) : montantParTranche,
      date_echeance: addMonths(dateDebut, i),
    }))
    setTranches(lignes)
  }

  const updateTranche = (index, field, value) => {
    setTranches((current) => current.map((t, i) => (i === index ? { ...t, [field]: value } : t)))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    if (!sommeOk) {
      setError('La somme des tranches doit être égale au montant total.')
      return
    }
    setLoading(true)
    try {
      const payload = {
        niveau_id: Number(niveauId),
        annee_academique_id: Number(anneeAcademiqueId),
        montant_total: Number(montantTotal),
        mode,
      }
      if (mode === 'tranches') {
        payload.tranches = tranches.map((t) => ({
          numero: Number(t.numero),
          montant: Number(t.montant),
          date_echeance: t.date_echeance,
        }))
      }
      await onSubmit(payload)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={fraisScolarite ? 'Modifier les frais de scolarité' : 'Nouveaux frais de scolarité'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          {!fraisScolarite && (
            <>
              <Select
                id="niveau_id"
                label="Niveau"
                value={niveauId}
                onChange={(e) => setNiveauId(e.target.value)}
                placeholder="Sélectionner un niveau"
                options={niveaux.map((n) => ({ value: n.id, label: `${n.filiereNom} — ${n.libelle}` }))}
                required
              />
              <Select
                id="annee_academique_id"
                label="Année académique"
                value={anneeAcademiqueId}
                onChange={(e) => setAnneeAcademiqueId(e.target.value)}
                placeholder="Sélectionner une année"
                options={annees.map((a) => ({ value: a.id, label: a.libelle }))}
                required
              />
            </>
          )}

          <Input
            id="montant_total"
            type="number"
            min="0.01"
            step="0.01"
            label="Montant total"
            value={montantTotal}
            onChange={(e) => setMontantTotal(e.target.value)}
            required
          />
          <Select id="mode" label="Mode de paiement" value={mode} onChange={(e) => setMode(e.target.value)} options={MODES} />

          {mode === 'tranches' && (
            <div className="flex flex-col gap-3 rounded-lg border border-slate-200 p-3">
              <div className="flex items-end gap-2">
                <Input
                  id="nombre_tranches"
                  type="number"
                  min="1"
                  label="Nombre de tranches"
                  value={nombreTranches}
                  onChange={(e) => setNombreTranches(e.target.value)}
                />
                <Button type="button" variant="ghost" onClick={handleRepartir}>
                  Répartir automatiquement
                </Button>
              </div>

              {tranches.length > 0 && (
                <div className="flex flex-col gap-2">
                  {tranches.map((tranche, index) => (
                    <div key={tranche.numero} className="flex items-center gap-2">
                      <span className="w-6 text-sm text-slate-500">#{tranche.numero}</span>
                      <Input
                        type="number"
                        step="0.01"
                        aria-label={`Montant tranche ${tranche.numero}`}
                        value={tranche.montant}
                        onChange={(e) => updateTranche(index, 'montant', e.target.value)}
                        className="flex-1"
                      />
                      <Input
                        type="date"
                        aria-label={`Échéance tranche ${tranche.numero}`}
                        value={tranche.date_echeance}
                        onChange={(e) => updateTranche(index, 'date_echeance', e.target.value)}
                        className="flex-1"
                      />
                    </div>
                  ))}
                </div>
              )}

              <p className={`text-sm ${sommeOk ? 'text-brand-teal' : 'text-red-600'}`}>
                Somme des tranches : {sommeTranches.toFixed(2)} / {Number(montantTotal || 0).toFixed(2)}
              </p>
            </div>
          )}

          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={onClose}>
              Annuler
            </Button>
            <Button type="submit" loading={loading}>
              Enregistrer
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
