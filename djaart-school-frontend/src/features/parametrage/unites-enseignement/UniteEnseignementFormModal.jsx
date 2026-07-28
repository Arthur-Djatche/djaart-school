import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'

const TYPES_UE = [
  { value: 'fondamentale', label: 'Fondamentale' },
  { value: 'professionnelle', label: 'Professionnelle' },
  { value: 'transversale', label: 'Transversale' },
]

export default function UniteEnseignementFormModal({ uniteEnseignement, semestreId, onClose, onSubmit }) {
  const [code, setCode] = useState(uniteEnseignement?.code ?? '')
  const [nom, setNom] = useState(uniteEnseignement?.nom ?? '')
  const [type, setType] = useState(uniteEnseignement?.type ?? TYPES_UE[0].value)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ semestre_id: Number(semestreId), code, nom, type })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={uniteEnseignement ? "Modifier l'unité d'enseignement" : "Nouvelle unité d'enseignement"} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="code" label="Code (ex. UE111)" value={code} onChange={(e) => setCode(e.target.value)} required />
        <Input id="nom" label="Intitulé" value={nom} onChange={(e) => setNom(e.target.value)} required />
        <Select id="type" label="Type" value={type} onChange={(e) => setType(e.target.value)} options={TYPES_UE} />
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
    </Modal>
  )
}
