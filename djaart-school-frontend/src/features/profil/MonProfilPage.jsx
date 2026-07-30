import { useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Select from '../../components/ui/Select'
import * as profilApi from '../../api/profilApi'
import useAuth from '../../hooks/useAuth'
import useToast from '../../hooks/useToast'

const CIVILITES = [
  { value: 'M.', label: 'M.' },
  { value: 'Mme', label: 'Mme' },
  { value: 'Mlle', label: 'Mlle' },
]

export default function MonProfilPage() {
  const { user, updateUser } = useAuth()
  const { showToast } = useToast()

  const [name, setName] = useState(user?.name ?? '')
  const [civilite, setCivilite] = useState(user?.civilite ?? '')
  const [savingProfil, setSavingProfil] = useState(false)
  const [erreurProfil, setErreurProfil] = useState('')

  const [uploadingPhoto, setUploadingPhoto] = useState(false)

  const [motDePasseActuel, setMotDePasseActuel] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [savingPassword, setSavingPassword] = useState(false)
  const [erreurPassword, setErreurPassword] = useState('')

  const handleSubmitProfil = async (event) => {
    event.preventDefault()
    setErreurProfil('')
    setSavingProfil(true)
    try {
      const { data } = await profilApi.mettreAJourProfil({ name, civilite: civilite || null })
      updateUser(data.data)
      showToast('Profil mis à jour.', 'success')
    } catch (err) {
      setErreurProfil(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setSavingProfil(false)
    }
  }

  const handlePhotoChange = async (event) => {
    const file = event.target.files?.[0]
    if (!file) return

    setUploadingPhoto(true)
    try {
      const { data } = await profilApi.mettreAJourPhoto(file)
      updateUser(data.data)
      showToast('Photo de profil mise à jour.', 'success')
    } finally {
      setUploadingPhoto(false)
      event.target.value = ''
    }
  }

  const handleSubmitPassword = async (event) => {
    event.preventDefault()
    setErreurPassword('')
    setSavingPassword(true)
    try {
      const { data } = await profilApi.changerMotDePasse({
        mot_de_passe_actuel: motDePasseActuel,
        password,
        password_confirmation: passwordConfirmation,
      })
      updateUser(data.data)
      setMotDePasseActuel('')
      setPassword('')
      setPasswordConfirmation('')
      showToast('Mot de passe mis à jour.', 'success')
    } catch (err) {
      setErreurPassword(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setSavingPassword(false)
    }
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Mon profil</h1>
        <p className="text-sm text-slate-500">Gérez vos informations personnelles et votre sécurité de connexion.</p>
      </div>

      <div className="flex flex-col gap-6 lg:flex-row">
        <div className="flex flex-col gap-6 lg:w-2/3">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-soft">
            <h2 className="mb-4 text-lg font-semibold text-brand-navy">Informations personnelles</h2>
            <form className="flex flex-col gap-4" onSubmit={handleSubmitProfil}>
              <Select
                id="civilite"
                label="Civilité"
                value={civilite}
                onChange={(e) => setCivilite(e.target.value)}
                placeholder="Non renseignée"
                options={CIVILITES}
              />
              <Input id="name" label="Nom complet" value={name} onChange={(e) => setName(e.target.value)} required />
              <Input id="email" label="E-mail" value={user?.email ?? ''} disabled />
              {erreurProfil && <p className="text-sm text-red-600">{erreurProfil}</p>}
              <div className="flex justify-end">
                <Button type="submit" loading={savingProfil}>
                  Enregistrer
                </Button>
              </div>
            </form>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-soft">
            <h2 className="mb-4 text-lg font-semibold text-brand-navy">Changer mon mot de passe</h2>
            <form className="flex flex-col gap-4" onSubmit={handleSubmitPassword}>
              <Input
                id="mot_de_passe_actuel"
                type="password"
                label="Mot de passe actuel"
                value={motDePasseActuel}
                onChange={(e) => setMotDePasseActuel(e.target.value)}
                required
              />
              <Input
                id="password"
                type="password"
                label="Nouveau mot de passe"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
              <Input
                id="password_confirmation"
                type="password"
                label="Confirmer le nouveau mot de passe"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                required
              />
              {erreurPassword && <p className="text-sm text-red-600">{erreurPassword}</p>}
              <div className="flex justify-end">
                <Button type="submit" loading={savingPassword}>
                  Mettre à jour le mot de passe
                </Button>
              </div>
            </form>
          </div>
        </div>

        <div className="lg:w-1/3">
          <div className="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-soft">
            <h2 className="mb-4 text-lg font-semibold text-brand-navy">Photo de profil</h2>
            {user?.photo_url ? (
              <img src={user.photo_url} alt="Photo de profil" className="mx-auto mb-4 h-32 w-32 rounded-full object-cover" />
            ) : (
              <div className="mx-auto mb-4 flex h-32 w-32 items-center justify-center rounded-full bg-slate-100 text-sm text-slate-400">
                Aucune photo
              </div>
            )}
            <label className="inline-block cursor-pointer rounded-lg bg-brand-blue px-4 py-2 text-sm font-medium text-white shadow-soft transition hover:bg-brand-navy">
              {uploadingPhoto ? 'Téléversement…' : 'Changer la photo'}
              <input type="file" accept="image/png,image/jpeg" className="hidden" onChange={handlePhotoChange} disabled={uploadingPhoto} />
            </label>
          </div>
        </div>
      </div>
    </DashboardLayout>
  )
}
