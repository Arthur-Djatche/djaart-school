import { useState } from 'react'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Modal from '../../components/ui/Modal'
import * as landingApi from '../../api/landingApi'

export default function DemandeDemoModal({ onClose }) {
  const [nom, setNom] = useState('')
  const [email, setEmail] = useState('')
  const [telephone, setTelephone] = useState('')
  const [nomEtablissement, setNomEtablissement] = useState('')
  const [effectifEstime, setEffectifEstime] = useState('')
  const [message, setMessage] = useState('')
  const [envoye, setEnvoye] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await landingApi.demanderDemo({
        nom,
        email,
        telephone: telephone || null,
        nom_etablissement: nomEtablissement,
        effectif_estime: effectifEstime ? Number(effectifEstime) : null,
        message: message || null,
      })
      setEnvoye(true)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue. Réessayez.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title="Demander une démo gratuite" onClose={onClose}>
      {envoye ? (
        <div className="flex flex-col items-center gap-3 py-4 text-center">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-teal-tint text-2xl text-brand-teal">✓</span>
          <p className="font-medium text-brand-navy">Merci ! Votre demande a bien été envoyée.</p>
          <p className="text-sm text-slate-500">Notre équipe vous recontactera très rapidement.</p>
          <Button variant="ghost" onClick={onClose}>
            Fermer
          </Button>
        </div>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Input id="nom" label="Votre nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input id="email" type="email" label="Adresse e-mail" value={email} onChange={(e) => setEmail(e.target.value)} required />
          <Input id="telephone" label="Téléphone (optionnel)" value={telephone} onChange={(e) => setTelephone(e.target.value)} />
          <Input
            id="nom_etablissement"
            label="Nom de l'établissement"
            value={nomEtablissement}
            onChange={(e) => setNomEtablissement(e.target.value)}
            required
          />
          <Input
            id="effectif_estime"
            type="number"
            min="1"
            label="Effectif estimé (nombre d'apprenants)"
            value={effectifEstime}
            onChange={(e) => setEffectifEstime(e.target.value)}
          />
          <div className="flex flex-col gap-1">
            <label htmlFor="message" className="text-sm font-medium text-brand-navy">
              Message (optionnel)
            </label>
            <textarea
              id="message"
              rows={3}
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              className="rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-brand-blue focus:ring-2 focus:ring-brand-blue-light/40"
            />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={onClose}>
              Annuler
            </Button>
            <Button type="submit" loading={loading}>
              Envoyer ma demande
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
