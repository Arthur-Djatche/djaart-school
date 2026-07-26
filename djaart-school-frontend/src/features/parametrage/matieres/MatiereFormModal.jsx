import { useEffect, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'

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

export default function MatiereFormModal({ matiere, onClose, onSubmit }) {
  const [niveaux, setNiveaux] = useState([])
  const [niveauId, setNiveauId] = useState(matiere?.niveau_id ?? '')
  const [nom, setNom] = useState(matiere?.nom ?? '')
  const [coefficient, setCoefficient] = useState(matiere?.coefficient ?? 1)
  const [creditsEcts, setCreditsEcts] = useState(matiere?.credits_ects ?? '')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [loadingOptions, setLoadingOptions] = useState(true)

  useEffect(() => {
    (async () => {
      setNiveaux(await loadAllNiveaux())
      setLoadingOptions(false)
    })()
  }, [])

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        niveau_id: Number(niveauId),
        nom,
        coefficient: Number(coefficient),
        credits_ects: creditsEcts ? Number(creditsEcts) : null,
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={matiere ? 'Modifier la matière' : 'Nouvelle matière'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Select
            id="niveau_id"
            label="Niveau"
            value={niveauId}
            onChange={(e) => setNiveauId(e.target.value)}
            placeholder="Sélectionner un niveau"
            options={niveaux.map((n) => ({ value: n.id, label: `${n.filiereNom} — ${n.libelle}` }))}
            required
          />
          <Input id="nom" label="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input
            id="coefficient"
            type="number"
            step="0.5"
            min="0.5"
            label="Coefficient"
            value={coefficient}
            onChange={(e) => setCoefficient(e.target.value)}
            required
          />
          <Input
            id="credits_ects"
            type="number"
            min="1"
            label="Crédits ECTS (LMD uniquement)"
            value={creditsEcts}
            onChange={(e) => setCreditsEcts(e.target.value)}
          />
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
