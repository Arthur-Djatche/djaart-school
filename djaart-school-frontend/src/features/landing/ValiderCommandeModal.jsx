import { useState } from 'react'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Modal from '../../components/ui/Modal'
import Select from '../../components/ui/Select'
import { PERMISSIONS_CATALOG } from '../../config/permissionsCatalog'

const TYPES_ETABLISSEMENT = [
  { value: 'primaire', label: 'Primaire' },
  { value: 'secondaire', label: 'Secondaire' },
  { value: 'universitaire', label: 'Universitaire' },
  { value: 'centre_formation', label: 'Centre de formation' },
]

export default function ValiderCommandeModal({ commande, onClose, onSubmit }) {
  const [typeEtablissement, setTypeEtablissement] = useState('secondaire')
  const [typeEtablissementSecondaire, setTypeEtablissementSecondaire] = useState('')
  const [dureeMois, setDureeMois] = useState('12')
  const [selected, setSelected] = useState(new Set())
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const toggle = (cle) => {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(cle)) {
        next.delete(cle)
      } else {
        next.add(cle)
      }
      return next
    })
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        type_etablissement: typeEtablissement,
        type_etablissement_secondaire: typeEtablissementSecondaire || null,
        duree_mois: Number(dureeMois),
        permissions: Array.from(selected),
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={`Valider la commande — ${commande.nom_etablissement}`} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <p className="text-sm text-slate-500">
          Contact : {commande.nom} — {commande.email} — {commande.ville} — {commande.nombre_apprenants} apprenants
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
          label="2e type (optionnel — max 2 types)"
          value={typeEtablissementSecondaire}
          onChange={(e) => setTypeEtablissementSecondaire(e.target.value)}
          placeholder="Aucun"
          options={TYPES_ETABLISSEMENT.filter((t) => t.value !== typeEtablissement)}
        />
        <Input
          id="duree_mois"
          type="number"
          min="1"
          max="60"
          label="Durée d'accès (mois)"
          value={dureeMois}
          onChange={(e) => setDureeMois(e.target.value)}
          required
        />

        <div>
          <p className="mb-2 text-sm font-medium text-brand-navy">Fonctionnalités incluses</p>
          <div className="flex max-h-[40vh] flex-col gap-4 overflow-y-auto">
            {PERMISSIONS_CATALOG.map((groupe) => (
              <div key={groupe.domaine}>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{groupe.domaine}</p>
                <div className="flex flex-col gap-2">
                  {groupe.droits.map((droit) => (
                    <label key={droit.cle} className="flex items-center gap-2 text-sm text-brand-navy">
                      <input
                        type="checkbox"
                        checked={selected.has(droit.cle)}
                        onChange={() => toggle(droit.cle)}
                        className="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue-light/40"
                      />
                      {droit.label}
                    </label>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" loading={loading}>
            Valider et créer le compte
          </Button>
        </div>
      </form>
    </Modal>
  )
}
