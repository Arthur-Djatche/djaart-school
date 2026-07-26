import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'

const TYPES = [
  { value: 'primaire', label: 'Primaire' },
  { value: 'secondaire', label: 'Secondaire' },
  { value: 'universitaire', label: 'Universitaire' },
  { value: 'centre_formation', label: 'Centre de formation' },
]

export default function EtablissementFormModal({ etablissement, onClose, onSubmit }) {
  const [nom, setNom] = useState(etablissement?.nom ?? '')
  const [typeEtablissement, setTypeEtablissement] = useState(etablissement?.type_etablissement ?? TYPES[0].value)
  const [sigle, setSigle] = useState(etablissement?.sigle ?? '')
  const [adresse, setAdresse] = useState(etablissement?.adresse ?? '')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ nom, type_etablissement: typeEtablissement, sigle, adresse })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={etablissement ? "Modifier l'établissement" : 'Nouvel établissement'} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="nom" label="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
        <Select
          id="type_etablissement"
          label="Type"
          value={typeEtablissement}
          onChange={(e) => setTypeEtablissement(e.target.value)}
          options={TYPES}
        />
        <Input id="sigle" label="Sigle" value={sigle} onChange={(e) => setSigle(e.target.value)} />
        <Input id="adresse" label="Adresse" value={adresse} onChange={(e) => setAdresse(e.target.value)} />
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
