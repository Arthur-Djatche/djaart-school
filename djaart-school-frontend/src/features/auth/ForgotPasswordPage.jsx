import { useState } from 'react'
import { Link } from 'react-router-dom'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import * as authApi from '../../api/authApi'
import AuthLayout from './AuthLayout'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setMessage('')
    setLoading(true)
    try {
      const { data } = await authApi.forgotPassword(email)
      setMessage(data.message)
    } catch (err) {
      setError(err.response?.data?.message ?? "Une erreur est survenue.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout title="Mot de passe oublié" subtitle="Recevez un lien de réinitialisation par e-mail.">
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <Input
          id="email"
          type="email"
          label="Adresse e-mail"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
        {message && <p className="text-sm text-brand-teal">{message}</p>}
        {error && <p className="text-sm text-red-600">{error}</p>}
        <Button type="submit" loading={loading} className="w-full justify-center">
          Envoyer le lien de réinitialisation
        </Button>
        <Link to="/login" className="text-center text-sm text-brand-blue hover:underline">
          Retour à la connexion
        </Link>
      </form>
    </AuthLayout>
  )
}
