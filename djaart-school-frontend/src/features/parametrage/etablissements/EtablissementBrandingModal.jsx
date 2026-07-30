import { useState } from 'react'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'

const CHAMPS = [
  {
    key: 'entete',
    label: "En-tête complet (logo + nom + adresse déjà composés)",
    urlKey: 'entete_url',
    upload: (id, file) => parametrageApi.uploadEtablissementEntete(id, file),
    messageOk: 'En-tête mis à jour.',
    vide: 'Aucun en-tête',
    wide: true,
  },
  {
    key: 'logo',
    label: 'Logo (utilisé en filigrane sur les documents)',
    urlKey: 'logo_url',
    upload: (id, file) => parametrageApi.uploadEtablissementLogo(id, file),
    messageOk: 'Logo mis à jour.',
    vide: 'Aucun logo',
  },
  {
    key: 'signature',
    label: 'Signature (image, pour les documents officiels)',
    urlKey: 'signature_url',
    upload: (id, file, titre) => parametrageApi.uploadEtablissementSignature(id, file, titre),
    messageOk: 'Signature mise à jour.',
    vide: 'Aucune signature',
  },
]

const SUGGESTIONS_TITRE = ['Le Directeur', 'La Directrice', 'Le Fondateur', 'La Fondatrice', 'Le Proviseur', 'La Proviseure']

export default function EtablissementBrandingModal({ etablissement, onClose, onUpdated }) {
  const { showToast } = useToast()
  const [current, setCurrent] = useState(etablissement)
  const [signatureTitre, setSignatureTitre] = useState(etablissement.signature_titre ?? '')
  const [uploadingKey, setUploadingKey] = useState(null)
  const [error, setError] = useState('')

  const handleUpload = async (champ, file) => {
    if (!file) return
    setError('')
    setUploadingKey(champ.key)
    try {
      const { data } = await champ.upload(current.id, file, champ.key === 'signature' ? signatureTitre : undefined)
      setCurrent(data.data)
      onUpdated?.(data.data)
      showToast(champ.messageOk, 'success')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setUploadingKey(null)
    }
  }

  return (
    <Modal title={`Image de marque — ${current.nom}`} onClose={onClose}>
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
        {CHAMPS.map((champ) => (
          <div key={champ.key} className={champ.wide ? 'sm:col-span-2' : undefined}>
            <p className="mb-2 text-sm font-medium text-brand-navy">{champ.label}</p>
            {current[champ.urlKey] ? (
              <img
                src={current[champ.urlKey]}
                alt={champ.label}
                className={`mb-3 rounded border border-slate-200 object-contain ${champ.wide ? 'h-16 w-full' : 'h-24 w-24'}`}
              />
            ) : (
              <div
                className={`mb-3 flex items-center justify-center rounded bg-slate-100 text-xs text-slate-400 ${champ.wide ? 'h-16 w-full' : 'h-24 w-24'}`}
              >
                {champ.vide}
              </div>
            )}
            {champ.key === 'signature' && (
              <Input
                id="signature_titre"
                label="Grade / titre du signataire"
                placeholder="Le Directeur, La Directrice, Le Fondateur…"
                list="suggestions-titre-signature"
                value={signatureTitre}
                onChange={(e) => setSignatureTitre(e.target.value)}
                className="mb-3"
              />
            )}
            <label className="inline-block cursor-pointer rounded-lg bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-navy">
              {uploadingKey === champ.key ? 'Téléversement…' : 'Téléverser'}
              <input
                type="file"
                accept="image/png,image/jpeg"
                className="hidden"
                onChange={(e) => handleUpload(champ, e.target.files?.[0])}
                disabled={uploadingKey === champ.key}
              />
            </label>
          </div>
        ))}
      </div>

      <datalist id="suggestions-titre-signature">
        {SUGGESTIONS_TITRE.map((titre) => (
          <option key={titre} value={titre} />
        ))}
      </datalist>

      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}

      <p className="mt-4 text-xs text-slate-500">
        Si un en-tête complet est importé, il remplace le bloc logo + nom généré automatiquement en haut des documents.
        Le logo apparaît en filigrane en fond de tous les documents (bulletins, relevés, reçus, attestations, cartes scolaires).
        Le grade/titre du signataire est saisi une fois puis réutilisé à chaque nouveau téléversement de la signature.
      </p>
    </Modal>
  )
}
