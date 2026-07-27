import { useEffect, useMemo, useState } from 'react'
import Button from '../../../components/ui/Button'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'
import * as usersApi from '../../../api/usersApi'

export default function AffectationFormModal({ onClose, onSubmit }) {
  const [classes, setClasses] = useState([])
  const [matieres, setMatieres] = useState([])
  const [enseignants, setEnseignants] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)

  const [classeId, setClasseId] = useState('')
  const [matiereId, setMatiereId] = useState('')
  const [enseignantId, setEnseignantId] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    (async () => {
      const [classesResponse, matieresResponse, enseignantsResponse] = await Promise.all([
        parametrageApi.fetchClasses({ page: 1 }),
        parametrageApi.fetchMatieres({ page: 1 }),
        usersApi.fetchUsers({ role: 'enseignant', page: 1 }),
      ])
      setClasses(classesResponse.data.data)
      setMatieres(matieresResponse.data.data)
      setEnseignants(enseignantsResponse.data.data)
      setLoadingOptions(false)
    })()
  }, [])

  const classe = classes.find((c) => c.id === Number(classeId))
  const matieresDuNiveau = useMemo(
    () => matieres.filter((m) => !classe || m.niveau_id === classe.niveau_id),
    [matieres, classe],
  )

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        classe_id: Number(classeId),
        matiere_id: Number(matiereId),
        enseignant_id: Number(enseignantId),
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title="Nouvelle affectation" onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Select
            id="classe_id"
            label="Classe"
            value={classeId}
            onChange={(e) => { setClasseId(e.target.value); setMatiereId('') }}
            placeholder="Sélectionner une classe"
            options={classes.map((c) => ({ value: c.id, label: c.libelle }))}
            required
          />
          <Select
            id="matiere_id"
            label="Matière"
            value={matiereId}
            onChange={(e) => setMatiereId(e.target.value)}
            placeholder="Sélectionner une matière"
            options={matieresDuNiveau.map((m) => ({ value: m.id, label: m.nom }))}
            required
            disabled={!classeId}
          />
          <Select
            id="enseignant_id"
            label="Enseignant"
            value={enseignantId}
            onChange={(e) => setEnseignantId(e.target.value)}
            placeholder="Sélectionner un enseignant"
            options={enseignants.map((e) => ({ value: e.id, label: `${e.name} (${e.email})` }))}
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
