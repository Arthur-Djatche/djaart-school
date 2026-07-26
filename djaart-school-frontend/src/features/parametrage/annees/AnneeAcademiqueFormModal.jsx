import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'

const STATUTS = [
  { value: 'en_preparation', label: 'En préparation' },
  { value: 'en_cours', label: 'En cours' },
  { value: 'cloturee', label: 'Clôturée' },
]

export default function AnneeAcademiqueFormModal({ annee, onClose, onSubmit }) {
  const [libelle, setLibelle] = useState(annee?.libelle ?? '')
  const [dateDebut, setDateDebut] = useState(annee?.date_debut ?? '')
  const [dateFin, setDateFin] = useState(annee?.date_fin ?? '')
  const [statut, setStatut] = useState(annee?.statut ?? STATUTS[0].value)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ libelle, date_debut: dateDebut, date_fin: dateFin, statut })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={annee ? "Modifier l'année académique" : 'Nouvelle année académique'} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="libelle" label="Libellé" value={libelle} onChange={(e) => setLibelle(e.target.value)} required placeholder="2025-2026" />
        <Input id="date_debut" type="date" label="Date de début" value={dateDebut} onChange={(e) => setDateDebut(e.target.value)} required />
        <Input id="date_fin" type="date" label="Date de fin" value={dateFin} onChange={(e) => setDateFin(e.target.value)} required />
        <Select id="statut" label="Statut" value={statut} onChange={(e) => setStatut(e.target.value)} options={STATUTS} />
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
