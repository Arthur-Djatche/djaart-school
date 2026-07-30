import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import * as profilApi from '../../api/profilApi'
import useAuth from '../../hooks/useAuth'
import useToast from '../../hooks/useToast'
import AuthLayout from './AuthLayout'

export default function ChangerMotDePassePage() {
  const navigate = useNavigate()
  const { updateUser } = useAuth()
  const { showToast } = useToast()
  const [motDePasseActuel, setMotDePasseActuel] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      const { data } = await profilApi.changerMotDePasse({
        mot_de_passe_actuel: motDePasseActuel,
        password,
        password_confirmation: passwordConfirmation,
      })
      updateUser(data.data)
      showToast('Mot de passe mis à jour.', 'success')
      navigate('/dashboard')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout
      title="Changement de mot de passe requis"
      subtitle="Pour la sécurité de votre compte, choisissez un nouveau mot de passe avant de continuer."
    >
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input
          id="mot_de_passe_actuel"
          type="password"
          label="Mot de passe provisoire"
          value={motDePasseActuel}
          onChange={(event) => setMotDePasseActuel(event.target.value)}
          required
        />
        <Input
          id="password"
          type="password"
          label="Nouveau mot de passe"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
        <Input
          id="password_confirmation"
          type="password"
          label="Confirmer le nouveau mot de passe"
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          required
        />
        {error && <p className="text-sm text-red-600">{error}</p>}
        <Button type="submit" loading={loading} className="w-full justify-center">
          Mettre à jour mon mot de passe
        </Button>
      </form>
    </AuthLayout>
  )
}
