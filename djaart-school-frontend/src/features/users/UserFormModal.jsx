import { useState } from 'react'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Modal from '../../components/ui/Modal'
import useAuth from '../../hooks/useAuth'

const ALL_ROLES = ['super_admin', 'admin_etablissement', 'secretaire', 'comptable', 'enseignant', 'apprenant']

export default function UserFormModal({ user, onClose, onSubmit }) {
  const { user: currentUser } = useAuth()
  const isSuperAdmin = currentUser?.roles.includes('super_admin')
  const assignableRoles = isSuperAdmin ? ALL_ROLES : ALL_ROLES.filter((role) => role !== 'super_admin')
  const modificationDeSoiMeme = Boolean(user) && user.id === currentUser?.id

  const [name, setName] = useState(user?.name ?? '')
  const [email, setEmail] = useState(user?.email ?? '')
  const [role, setRole] = useState(user?.roles?.[0] ?? assignableRoles[0])
  const [reinitialiserMotDePasse, setReinitialiserMotDePasse] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      const payload = { name, email }
      if (!modificationDeSoiMeme) payload.role = role
      if (user && reinitialiserMotDePasse) payload.reinitialiser_mot_de_passe = true
      await onSubmit(payload)
    } catch (err) {
      setError(err.response?.data?.message ?? "Une erreur est survenue.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={user ? "Modifier l'utilisateur" : 'Nouvel utilisateur'} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input id="name" label="Nom" value={name} onChange={(e) => setName(e.target.value)} required />
        <Input id="email" type="email" label="E-mail" value={email} onChange={(e) => setEmail(e.target.value)} required />

        {!user && (
          <p className="text-sm text-slate-500">
            Le mot de passe sera généré automatiquement et envoyé par e-mail à cette adresse.
          </p>
        )}
        {user && (
          <label className="flex items-center gap-2 text-sm text-brand-navy">
            <input
              type="checkbox"
              checked={reinitialiserMotDePasse}
              onChange={(e) => setReinitialiserMotDePasse(e.target.checked)}
              className="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue-light/40"
            />
            Réinitialiser le mot de passe (un nouveau sera généré et envoyé par e-mail)
          </label>
        )}

        {modificationDeSoiMeme ? (
          <p className="text-sm text-slate-500">Rôle : {role} (vous ne pouvez pas changer votre propre rôle)</p>
        ) : (
          <div className="flex flex-col gap-1">
            <label htmlFor="role" className="text-sm font-medium text-brand-navy">
              Rôle
            </label>
            <select
              id="role"
              value={role}
              onChange={(e) => setRole(e.target.value)}
              className="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-brand-blue focus:ring-2 focus:ring-brand-blue-light/40"
            >
              {assignableRoles.map((r) => (
                <option key={r} value={r}>
                  {r}
                </option>
              ))}
            </select>
          </div>
        )}
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
