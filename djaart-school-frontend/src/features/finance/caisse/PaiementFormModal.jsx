import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'

const MODES = [
  { value: 'especes', label: 'Espèces' },
  { value: 'mobile_money', label: 'Mobile Money' },
  { value: 'virement', label: 'Virement' },
  { value: 'cheque', label: 'Chèque' },
]

export default function PaiementFormModal({ tranche, onClose, onSubmit }) {
  const [montant, setMontant] = useState(tranche.solde)
  const [modePaiement, setModePaiement] = useState('especes')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({ montant: Number(montant), mode_paiement: modePaiement })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={`Encaisser — Tranche ${tranche.numero}`} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <div className="rounded-lg bg-slate-50 p-3 text-sm">
          <p>Montant de la tranche : {tranche.montant}</p>
          <p>Déjà versé : {tranche.montant_paye}</p>
          <p className="font-medium text-brand-navy">Solde dû : {tranche.solde}</p>
        </div>

        <Input
          id="montant"
          type="number"
          step="0.01"
          min="0.01"
          label="Montant encaissé"
          value={montant}
          onChange={(e) => setMontant(e.target.value)}
          required
        />
        <Select
          id="mode_paiement"
          label="Mode de paiement"
          value={modePaiement}
          onChange={(e) => setModePaiement(e.target.value)}
          options={MODES}
        />

        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" loading={loading}>
            Encaisser
          </Button>
        </div>
      </form>
    </Modal>
  )
}
