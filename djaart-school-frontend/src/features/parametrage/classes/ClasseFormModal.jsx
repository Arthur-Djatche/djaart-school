import { useEffect, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'
import * as pedagogieApi from '../../../api/pedagogieApi'

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

export default function ClasseFormModal({ classe, onClose, onSubmit }) {
  const [niveaux, setNiveaux] = useState([])
  const [annees, setAnnees] = useState([])
  const [enseignants, setEnseignants] = useState([])
  const [niveauId, setNiveauId] = useState(classe?.niveau_id ?? '')
  const [anneeAcademiqueId, setAnneeAcademiqueId] = useState(classe?.annee_academique_id ?? '')
  const [professeurPrincipalId, setProfesseurPrincipalId] = useState(classe?.professeur_principal_id ?? '')
  const [libelle, setLibelle] = useState(classe?.libelle ?? '')
  const [effectifMax, setEffectifMax] = useState(classe?.effectif_max ?? 40)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [loadingOptions, setLoadingOptions] = useState(true)

  useEffect(() => {
    (async () => {
      const [niveauxList, anneesResponse, enseignantsResponse] = await Promise.all([
        loadAllNiveaux(),
        parametrageApi.fetchAnneesAcademiques({ page: 1 }),
        pedagogieApi.fetchEnseignantsDisponibles(),
      ])
      setNiveaux(niveauxList)
      setAnnees(anneesResponse.data.data)
      setEnseignants(enseignantsResponse.data.data)
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
        annee_academique_id: Number(anneeAcademiqueId),
        professeur_principal_id: professeurPrincipalId ? Number(professeurPrincipalId) : null,
        libelle,
        effectif_max: Number(effectifMax),
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={classe ? 'Modifier la classe' : 'Nouvelle classe'} onClose={onClose}>
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
          <Select
            id="annee_academique_id"
            label="Année académique"
            value={anneeAcademiqueId}
            onChange={(e) => setAnneeAcademiqueId(e.target.value)}
            placeholder="Sélectionner une année"
            options={annees.map((a) => ({ value: a.id, label: a.libelle }))}
            required
          />
          <Select
            id="professeur_principal_id"
            label="Professeur principal (optionnel)"
            value={professeurPrincipalId}
            onChange={(e) => setProfesseurPrincipalId(e.target.value)}
            placeholder="Aucun"
            options={enseignants.map((e) => ({ value: e.id, label: e.name }))}
          />
          <Input id="libelle" label="Libellé" value={libelle} onChange={(e) => setLibelle(e.target.value)} required placeholder="6ème A" />
          <Input
            id="effectif_max"
            type="number"
            min="1"
            label="Effectif maximum"
            value={effectifMax}
            onChange={(e) => setEffectifMax(e.target.value)}
            required
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
