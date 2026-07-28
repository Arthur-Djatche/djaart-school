import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import useAuth from '../../hooks/useAuth'
import useToast from '../../hooks/useToast'
import AuthLayout from './AuthLayout'

export default function LoginPage() {
  const { login } = useAuth()
  const { showToast } = useToast()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email, password)
      showToast('Connexion réussie', 'success')
      navigate('/dashboard')
    } catch {
      setError('Identifiants incorrects.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout title="Connexion" subtitle="Accédez à votre espace DJAART SCHOOL.">
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
          label="Mot de passe"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
        {error && <p className="text-sm text-red-600">{error}</p>}
        <Button type="submit" loading={loading} className="w-full justify-center">
          Se connecter
        </Button>
        <Link to="/forgot-password" className="text-center text-sm text-brand-blue hover:underline">
          Mot de passe oublié ?
        </Link>
      </form>
    </AuthLayout>
  )
}
