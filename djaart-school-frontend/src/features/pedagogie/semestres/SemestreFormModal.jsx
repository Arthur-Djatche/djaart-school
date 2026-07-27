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

export default function SemestreFormModal({ semestre, onClose, onSubmit }) {
  const [niveaux, setNiveaux] = useState([])
  const [annees, setAnnees] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)

  const [niveauId, setNiveauId] = useState(semestre?.niveau_id ?? '')
  const [anneeAcademiqueId, setAnneeAcademiqueId] = useState(semestre?.annee_academique_id ?? '')
  const [numero, setNumero] = useState(semestre?.numero ?? 1)
  const [libelle, setLibelle] = useState(semestre?.libelle ?? '')
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

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        niveau_id: Number(niveauId),
        annee_academique_id: Number(anneeAcademiqueId),
        numero: Number(numero),
        libelle,
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={semestre ? 'Modifier le semestre' : 'Nouveau semestre'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          {!semestre && (
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

          <Input id="numero" type="number" min="1" label="Numéro" value={numero} onChange={(e) => setNumero(e.target.value)} required />
          <Input id="libelle" label="Libellé" value={libelle} onChange={(e) => setLibelle(e.target.value)} required placeholder="Semestre 1" />

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
