import { useState } from 'react'
import Modal from '../../../components/ui/Modal'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'

export default function EtablissementBrandingModal({ etablissement, onClose, onUpdated }) {
  const { showToast } = useToast()
  const [current, setCurrent] = useState(etablissement)
  const [uploadingLogo, setUploadingLogo] = useState(false)
  const [uploadingSignature, setUploadingSignature] = useState(false)
  const [error, setError] = useState('')

  const handleUpload = async (field, file) => {
    if (!file) return
    setError('')
    const setUploading = field === 'logo' ? setUploadingLogo : setUploadingSignature
    const upload = field === 'logo' ? parametrageApi.uploadEtablissementLogo : parametrageApi.uploadEtablissementSignature
    setUploading(true)
    try {
      const { data } = await upload(current.id, file)
      setCurrent(data.data)
      onUpdated?.(data.data)
      showToast(field === 'logo' ? 'Logo mis à jour.' : 'Signature mise à jour.', 'success')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setUploading(false)
    }
  }

  return (
    <Modal title={`Logo & signature — ${current.nom}`} onClose={onClose}>
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <p className="mb-2 text-sm font-medium text-brand-navy">Logo de l'établissement</p>
          {current.logo_url ? (
            <img src={current.logo_url} alt="Logo" className="mb-3 h-24 w-24 rounded object-contain border border-slate-200" />
          ) : (
            <div className="mb-3 flex h-24 w-24 items-center justify-center rounded bg-slate-100 text-xs text-slate-400">
              Aucun logo
            </div>
          )}
          <label className="inline-block cursor-pointer rounded-lg bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy">
            {uploadingLogo ? 'Téléversement…' : 'Téléverser un logo'}
            <input
              type="file"
              accept="image/png,image/jpeg"
              className="hidden"
              onChange={(e) => handleUpload('logo', e.target.files?.[0])}
              disabled={uploadingLogo}
            />
          </label>
        </div>

        <div>
          <p className="mb-2 text-sm font-medium text-brand-navy">Signature (image)</p>
          {current.signature_url ? (
            <img src={current.signature_url} alt="Signature" className="mb-3 h-24 w-24 rounded object-contain border border-slate-200" />
          ) : (
            <div className="mb-3 flex h-24 w-24 items-center justify-center rounded bg-slate-100 text-xs text-slate-400">
              Aucune signature
            </div>
          )}
          <label className="inline-block cursor-pointer rounded-lg bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy">
            {uploadingSignature ? 'Téléversement…' : 'Téléverser une signature'}
            <input
              type="file"
              accept="image/png,image/jpeg"
              className="hidden"
              onChange={(e) => handleUpload('signature', e.target.files?.[0])}
              disabled={uploadingSignature}
            />
          </label>
        </div>
      </div>

      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}

      <p className="mt-4 text-xs text-slate-500">
        Le logo et la signature apparaîtront sur tous les documents générés (bulletins, relevés, reçus, attestations, cartes scolaires).
      </p>
    </Modal>
  )
}
