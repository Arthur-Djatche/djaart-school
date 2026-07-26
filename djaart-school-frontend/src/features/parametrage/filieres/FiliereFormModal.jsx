import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'

export default function FiliereFormModal({ filiere, onClose, onSubmit }) {
  const [nom, setNom] = useState(filiere?.nom ?? '')
  const [code, setCode] = useState(filiere?.code ?? '')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ nom, code })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={filiere ? 'Modifier la filière' : 'Nouvelle filière'} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="nom" label="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
        <Input id="code" label="Code" value={code} onChange={(e) => setCode(e.target.value)} required />
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
