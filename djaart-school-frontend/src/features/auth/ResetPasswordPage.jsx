import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import logo from '../../assets/logo.png'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import * as authApi from '../../api/authApi'
import useToast from '../../hooks/useToast'

export default function ResetPasswordPage() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { showToast } = useToast()
  const token = searchParams.get('token') ?? ''
  const [email, setEmail] = useState(searchParams.get('email') ?? '')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await authApi.resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      showToast('Mot de passe réinitialisé, vous pouvez vous connecter.', 'success')
      navigate('/login')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Lien invalide ou expiré.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-sm rounded-2xl bg-white p-8 shadow-lg">
        <div className="mb-8 flex flex-col items-center gap-2">
          <img src={logo} alt="DJAART SCHOOL" className="h-20 w-auto" />
        </div>

        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Input
            id="email"
            type="email"
            label="Adresse e-mail"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
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
            label="Confirmer le mot de passe"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            required
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          <Button type="submit" loading={loading} className="w-full justify-center">
            Réinitialiser le mot de passe
          </Button>
        </form>
      </div>
    </div>
  )
}
