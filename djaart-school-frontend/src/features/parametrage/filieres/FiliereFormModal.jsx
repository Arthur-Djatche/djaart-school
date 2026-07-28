import { useEffect, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'
import useAuth from '../../../hooks/useAuth'

export default function FiliereFormModal({ filiere, onClose, onSubmit }) {
  const { user } = useAuth()
  const estUniversitaire = user?.etablissement?.type_etablissement === 'universitaire'

  const [nom, setNom] = useState(filiere?.nom ?? '')
  const [code, setCode] = useState(filiere?.code ?? '')
  const [departementId, setDepartementId] = useState(filiere?.departement_id ?? '')
  const [departements, setDepartements] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(estUniversitaire)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (!estUniversitaire) return
    (async () => {
      const { data } = await parametrageApi.fetchDepartements({ page: 1 })
      setDepartements(data.data)
      setLoadingOptions(false)
    })()
  }, [estUniversitaire])

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ nom, code, departement_id: departementId ? Number(departementId) : null })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={filiere ? 'Modifier la filière' : 'Nouvelle filière'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Input id="nom" label="Nom (spécialité)" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input id="code" label="Code" value={code} onChange={(e) => setCode(e.target.value)} required />
          {estUniversitaire && (
            <Select
              id="departement_id"
              label="Département"
              value={departementId}
              onChange={(e) => setDepartementId(e.target.value)}
              placeholder="Sélectionner un département"
              options={departements.map((d) => ({ value: d.id, label: d.nom }))}
              required
            />
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
