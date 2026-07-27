import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Select from '../../components/ui/Select'
import * as apprenantsApi from '../../api/apprenantsApi'
import useToast from '../../hooks/useToast'

const TYPES_ATTESTATION = [
  { value: 'scolarite', label: 'Attestation de scolarité' },
  { value: 'frequentation', label: 'Attestation de fréquentation' },
  { value: 'reussite', label: 'Attestation de réussite' },
]

export default function ApprenantFichePage() {
  const { id } = useParams()
  const { showToast } = useToast()
  const [apprenant, setApprenant] = useState(null)
  const [attestations, setAttestations] = useState([])
  const [cartes, setCartes] = useState([])
  const [loading, setLoading] = useState(true)

  const [typeAttestation, setTypeAttestation] = useState(TYPES_ATTESTATION[0].value)
  const [uploadingPhoto, setUploadingPhoto] = useState(false)
  const [generatingAttestation, setGeneratingAttestation] = useState(false)
  const [generatingCarte, setGeneratingCarte] = useState(false)
  const [error, setError] = useState('')

  const chargerTout = async () => {
    const [apprenantResponse, attestationsResponse, cartesResponse] = await Promise.all([
      apprenantsApi.fetchApprenant(id),
      apprenantsApi.fetchAttestations(id),
      apprenantsApi.fetchCartesScolaires(id),
    ])
    setApprenant(apprenantResponse.data.data)
    setAttestations(attestationsResponse.data.data)
    setCartes(cartesResponse.data.data)
  }

  useEffect(() => {
    (async () => {
      setLoading(true)
      await chargerTout()
      setLoading(false)
    })()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id])

  const handlePhotoChange = async (event) => {
    const file = event.target.files?.[0]
    if (!file) return
    setError('')
    setUploadingPhoto(true)
    try {
      await apprenantsApi.uploadPhoto(id, file)
      showToast('Photo mise à jour.', 'success')
      await chargerTout()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setUploadingPhoto(false)
    }
  }

  const handleGenererAttestation = async () => {
    setError('')
    setGeneratingAttestation(true)
    try {
      await apprenantsApi.createAttestation(id, typeAttestation)
      showToast('Attestation générée.', 'success')
      await chargerTout()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setGeneratingAttestation(false)
    }
  }

  const handleGenererCarte = async () => {
    setError('')
    setGeneratingCarte(true)
    try {
      await apprenantsApi.createCarteScolaire(id)
      showToast('Carte scolaire générée.', 'success')
      await chargerTout()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setGeneratingCarte(false)
    }
  }

  if (loading || !apprenant) {
    return (
      <DashboardLayout>
        <p className="text-slate-500">Chargement…</p>
      </DashboardLayout>
    )
  }

  const typeLabels = { scolarite: 'Scolarité', frequentation: 'Fréquentation', reussite: 'Réussite' }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">
          {apprenant.prenom} {apprenant.nom}
        </h1>
        <p className="text-sm text-slate-500">Matricule {apprenant.matricule}</p>
      </div>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <p className="mb-3 font-medium text-brand-navy">Photo</p>
          {apprenant.photo_url ? (
            <img src={apprenant.photo_url} alt="Photo de l'apprenant" className="mb-3 h-40 w-32 rounded object-cover" />
          ) : (
            <div className="mb-3 flex h-40 w-32 items-center justify-center rounded bg-slate-100 text-xs text-slate-400">
              Aucune photo
            </div>
          )}
          <label className="inline-block cursor-pointer rounded-lg bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy">
            {uploadingPhoto ? 'Téléversement…' : 'Téléverser une photo'}
            <input type="file" accept="image/png,image/jpeg" className="hidden" onChange={handlePhotoChange} disabled={uploadingPhoto} />
          </label>
        </div>

        <div className="rounded-lg border border-slate-200 bg-white p-4 md:col-span-2">
          <p className="mb-3 font-medium text-brand-navy">Effets académiques</p>
          <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
            <Select
              id="type_attestation"
              label="Type d'attestation"
              value={typeAttestation}
              onChange={(e) => setTypeAttestation(e.target.value)}
              options={TYPES_ATTESTATION}
              className="sm:flex-1"
            />
            <Button onClick={handleGenererAttestation} loading={generatingAttestation}>
              Générer une attestation
            </Button>
          </div>
          <Button variant="accent" onClick={handleGenererCarte} loading={generatingCarte}>
            Générer la carte scolaire
          </Button>
          {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
        </div>
      </div>

      <div className="mt-6 rounded-lg border border-slate-200 bg-white p-4">
        <p className="mb-3 font-medium text-brand-navy">Historique des documents</p>
        {attestations.length === 0 && cartes.length === 0 ? (
          <p className="text-sm text-slate-500">Aucun document généré pour l'instant.</p>
        ) : (
          <ul className="flex flex-col divide-y divide-slate-100 text-sm">
            {attestations.map((a) => (
              <li key={`attestation-${a.id}`} className="flex items-center justify-between py-2">
                <span>Attestation de {typeLabels[a.type]} — n° {a.numero}</span>
                <a href={apprenantsApi.attestationDownloadUrl(a.id)} target="_blank" rel="noopener" className="text-brand-blue hover:underline">
                  Télécharger
                </a>
              </li>
            ))}
            {cartes.map((c) => (
              <li key={`carte-${c.id}`} className="flex items-center justify-between py-2">
                <span>
                  Carte scolaire — n° {c.numero}
                  {c.numero_duplicata > 0 && (
                    <span className="ml-2 rounded-full bg-brand-orange/10 px-2 py-0.5 text-xs font-medium text-brand-orange">
                      Duplicata {c.numero_duplicata}
                    </span>
                  )}
                  {' '}— valable jusqu'au {c.date_expiration}
                </span>
                <a href={apprenantsApi.carteScolaireDownloadUrl(c.id)} target="_blank" rel="noopener" className="text-brand-blue hover:underline">
                  Télécharger
                </a>
              </li>
            ))}
          </ul>
        )}
      </div>
    </DashboardLayout>
  )
}
