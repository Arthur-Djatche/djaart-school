import { useState } from 'react'
import Button from '../../components/ui/Button'
import Modal from '../../components/ui/Modal'
import Select from '../../components/ui/Select'

const TYPES_ETABLISSEMENT = [
  { value: 'primaire', label: 'Primaire' },
  { value: 'secondaire', label: 'Secondaire' },
  { value: 'universitaire', label: 'Universitaire' },
  { value: 'centre_formation', label: 'Centre de formation' },
]

export default function ValiderDemandeDemoModal({ demande, onClose, onSubmit }) {
  const [typeEtablissement, setTypeEtablissement] = useState('secondaire')
  const [typeEtablissementSecondaire, setTypeEtablissementSecondaire] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        type_etablissement: typeEtablissement,
        type_etablissement_secondaire: typeEtablissementSecondaire || null,
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={`Valider la demande de démo — ${demande.nom_etablissement}`} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <p className="text-sm text-slate-500">
          Contact : {demande.nom} — {demande.email} — {demande.effectif_estime ?? '—'} apprenants estimés
        </p>
        <p className="rounded-lg bg-brand-blue-tint px-3 py-2 text-sm text-brand-navy">
          Un accès de démonstration <strong>limité à 48 heures</strong> sera créé automatiquement, avec un compte
          administrateur envoyé par e-mail — pas de durée ni de droits à choisir ici, contrairement à une commande payante.
        </p>

        <Select
          id="type_etablissement"
          label="Type d'établissement"
          value={typeEtablissement}
          onChange={(e) => {
            setTypeEtablissement(e.target.value)
            if (e.target.value === typeEtablissementSecondaire) setTypeEtablissementSecondaire('')
          }}
          options={TYPES_ETABLISSEMENT}
          required
        />
        <Select
          id="type_etablissement_secondaire"
          label="2e établissement (optionnel) — même admin, à basculer depuis le dashboard"
          value={typeEtablissementSecondaire}
          onChange={(e) => setTypeEtablissementSecondaire(e.target.value)}
          placeholder="Aucun"
          options={TYPES_ETABLISSEMENT.filter((t) => t.value !== typeEtablissement)}
        />

        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" loading={loading}>
            Valider (accès 48h) et créer le compte
          </Button>
        </div>
      </form>
    </Modal>
  )
}
