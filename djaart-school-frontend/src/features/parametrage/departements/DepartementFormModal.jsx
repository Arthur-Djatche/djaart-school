import { useEffect, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as usersApi from '../../../api/usersApi'

export default function DepartementFormModal({ departement, onClose, onSubmit }) {
  const [nom, setNom] = useState(departement?.nom ?? '')
  const [code, setCode] = useState(departement?.code ?? '')
  const [chefDepartementId, setChefDepartementId] = useState(departement?.chef_departement_id ?? '')
  const [enseignants, setEnseignants] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    (async () => {
      const { data } = await usersApi.fetchUsers({ role: 'enseignant', page: 1 })
      setEnseignants(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ nom, code, chef_departement_id: chefDepartementId ? Number(chefDepartementId) : null })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={departement ? 'Modifier le département' : 'Nouveau département'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Input id="nom" label="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input id="code" label="Code" value={code} onChange={(e) => setCode(e.target.value)} required />
          <Select
            id="chef_departement_id"
            label="Chef de département (optionnel)"
            value={chefDepartementId}
            onChange={(e) => setChefDepartementId(e.target.value)}
            placeholder="Aucun"
            options={enseignants.map((e) => ({ value: e.id, label: e.name }))}
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
