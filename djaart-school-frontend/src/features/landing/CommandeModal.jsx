import { useState } from 'react'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Modal from '../../components/ui/Modal'
import * as landingApi from '../../api/landingApi'

export default function CommandeModal({ formule, onClose }) {
  const [nom, setNom] = useState('')
  const [ville, setVille] = useState('')
  const [nombreApprenants, setNombreApprenants] = useState('')
  const [telephone, setTelephone] = useState('')
  const [email, setEmail] = useState('')
  const [nomEtablissement, setNomEtablissement] = useState('')
  const [envoyee, setEnvoyee] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await landingApi.commander({
        nom,
        ville,
        nombre_apprenants: Number(nombreApprenants),
        telephone,
        email,
        nom_etablissement: nomEtablissement,
      })
      setEnvoyee(true)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue. Réessayez.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={formule ? `Commander la formule ${formule}` : 'Commander maintenant'} onClose={onClose}>
      {envoyee ? (
        <div className="flex flex-col items-center gap-3 py-4 text-center">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-teal-tint text-2xl text-brand-teal">✓</span>
          <p className="font-medium text-brand-navy">Merci ! Votre commande a bien été envoyée.</p>
          <p className="text-sm text-slate-500">
            Notre équipe l'active rapidement et vous enverra vos identifiants de connexion par e-mail.
          </p>
          <Button variant="ghost" onClick={onClose}>
            Fermer
          </Button>
        </div>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Input id="nom" label="Votre nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input id="ville" label="Ville" value={ville} onChange={(e) => setVille(e.target.value)} required />
          <Input
            id="nombre_apprenants"
            type="number"
            min="1"
            label="Nombre d'apprenants"
            value={nombreApprenants}
            onChange={(e) => setNombreApprenants(e.target.value)}
            required
          />
          <Input id="telephone" label="Téléphone" value={telephone} onChange={(e) => setTelephone(e.target.value)} required />
          <Input id="email" type="email" label="Adresse e-mail" value={email} onChange={(e) => setEmail(e.target.value)} required />
          <Input
            id="nom_etablissement"
            label="Nom de l'établissement"
            value={nomEtablissement}
            onChange={(e) => setNomEtablissement(e.target.value)}
            required
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={onClose}>
              Annuler
            </Button>
            <Button type="submit" loading={loading}>
              Envoyer ma commande
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
