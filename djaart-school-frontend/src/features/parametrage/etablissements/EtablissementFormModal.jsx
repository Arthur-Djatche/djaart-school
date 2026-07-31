import { useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import useAuth from '../../../hooks/useAuth'

const TYPES = [
  { value: 'primaire', label: 'Primaire' },
  { value: 'secondaire', label: 'Secondaire' },
  { value: 'universitaire', label: 'Universitaire' },
  { value: 'centre_formation', label: 'Centre de formation' },
]

export default function EtablissementFormModal({ etablissement, onClose, onSubmit }) {
  const { user } = useAuth()
  const estSuperAdmin = user?.roles.includes('super_admin')

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
      const payload = { nom, sigle, adresse }
      // Le type est reserve au super_admin (choisi a la validation de la
      // commande) : un admin_etablissement ne l'envoie jamais, meme
      // inchange, sinon le backend rejette toute la requete.
      if (estSuperAdmin) {
        payload.type_etablissement = typeEtablissement
      }
      await onSubmit(payload)
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
        {estSuperAdmin ? (
          <>
            <Select
              id="type_etablissement"
              label="Type"
              value={typeEtablissement}
              onChange={(e) => setTypeEtablissement(e.target.value)}
              options={TYPES}
            />
            <p className="text-xs text-slate-400">
              Pour un 2e type, créez un nouvel établissement dédié puis rattachez-y le même administrateur
              depuis Comptes utilisateurs (même e-mail, nouveau rôle) — il pourra basculer entre les deux.
            </p>
          </>
        ) : (
          <p className="text-sm text-slate-500">
            Type : {TYPES.find((t) => t.value === typeEtablissement)?.label} (modifiable uniquement par DJAART SCHOOL)
          </p>
        )}
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
