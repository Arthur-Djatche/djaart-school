import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'

const TYPES_SYSTEME = [
  { value: 'classique', label: 'Classique (primaire/secondaire)' },
  { value: 'lmd', label: 'LMD (universitaire)' },
]

export default function NiveauFormModal({ niveau, filiereId, onClose, onSubmit }) {
  const [libelle, setLibelle] = useState(niveau?.libelle ?? '')
  const [ordre, setOrdre] = useState(niveau?.ordre ?? 1)
  const [typeSysteme, setTypeSysteme] = useState(niveau?.type_systeme ?? TYPES_SYSTEME[0].value)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ filiere_id: filiereId, libelle, ordre: Number(ordre), type_systeme: typeSysteme })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={niveau ? 'Modifier le niveau' : 'Nouveau niveau'} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="libelle" label="Libellé" value={libelle} onChange={(e) => setLibelle(e.target.value)} required />
        <Input id="ordre" type="number" min="1" label="Ordre" value={ordre} onChange={(e) => setOrdre(e.target.value)} required />
        <Select
          id="type_systeme"
          label="Système"
          value={typeSysteme}
          onChange={(e) => setTypeSysteme(e.target.value)}
          options={TYPES_SYSTEME}
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
    </Modal>
  )
}
