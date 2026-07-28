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

export default function PaiementFormModal({ inscription, onClose, onSubmit }) {
  const [montant, setMontant] = useState(inscription.solde_total_pension)
  const [modePaiement, setModePaiement] = useState('especes')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')

    const montantSaisi = Number(montant)
    if (montantSaisi > inscription.solde_total_pension) {
      setError(`Le montant saisi (${montantSaisi}) dépasse le solde restant de la pension (${inscription.solde_total_pension}).`)
      return
    }

    setLoading(true)
    try {
      await onSubmit({ montant: montantSaisi, mode_paiement: modePaiement })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={`Encaisser — ${inscription.classe.libelle}`} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <div className="rounded-lg bg-slate-50 p-3 text-sm">
          <p className="font-medium text-brand-navy">Solde restant sur la pension : {inscription.solde_total_pension}</p>
          <p className="text-slate-500">Le montant peut couvrir plusieurs tranches ou une seule partiellement, tant qu'il ne dépasse pas ce solde.</p>
        </div>

        <Input
          id="montant"
          type="number"
          step="0.01"
          min="0.01"
          max={inscription.solde_total_pension}
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
