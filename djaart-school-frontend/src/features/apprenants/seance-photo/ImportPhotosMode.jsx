import { useMemo, useRef, useState } from 'react'
import Button from '../../../components/ui/Button'
import * as apprenantsApi from '../../../api/apprenantsApi'
import useToast from '../../../hooks/useToast'

function splitAlphaNumeric(value) {
  return value.match(/\d+|\D+/g) ?? []
}

// Tri naturel : "IMG_2.jpg" doit passer avant "IMG_10.jpg" (un tri texte brut
// les classerait dans le mauvais ordre, "1" < "2" caractere par caractere).
function comparerNaturellement(a, b) {
  const tokensA = splitAlphaNumeric(a)
  const tokensB = splitAlphaNumeric(b)
  const longueur = Math.max(tokensA.length, tokensB.length)

  for (let i = 0; i < longueur; i += 1) {
    const tokenA = tokensA[i] ?? ''
    const tokenB = tokensB[i] ?? ''
    const nombreA = Number(tokenA)
    const nombreB = Number(tokenB)
    const tousDeuxNumeriques = tokenA !== '' && tokenB !== '' && !Number.isNaN(nombreA) && !Number.isNaN(nombreB)

    const comparaison = tousDeuxNumeriques ? nombreA - nombreB : tokenA.localeCompare(tokenB)
    if (comparaison !== 0) return comparaison
  }

  return 0
}

export default function ImportPhotosMode({ classeId, roster, onImporte }) {
  const { showToast } = useToast()
  const dossierInputRef = useRef(null)
  const fichiersInputRef = useRef(null)
  const [fichiers, setFichiers] = useState([])
  const [importing, setImporting] = useState(false)
  const [resultats, setResultats] = useState(null)

  const handleFichiers = (event) => {
    const liste = Array.from(event.target.files ?? []).sort((a, b) => comparerNaturellement(a.name, b.name))
    setFichiers(liste)
    setResultats(null)
    event.target.value = ''
  }

  const apercu = useMemo(
    () =>
      fichiers.map((file, index) => ({
        file,
        url: URL.createObjectURL(file),
        apprenant: roster[index]?.apprenant,
      })),
    [fichiers, roster],
  )

  const comptesEgaux = fichiers.length > 0 && fichiers.length === roster.length

  const handleConfirmer = async () => {
    setImporting(true)
    try {
      const { data } = await apprenantsApi.importerPhotosMasse(classeId, fichiers)
      setResultats(data.data)
      showToast('Import terminé.', 'success')
      onImporte?.()
    } catch (err) {
      showToast(err.response?.data?.message ?? "Échec de l'import.", 'error')
    } finally {
      setImporting(false)
    }
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
        <input
          ref={dossierInputRef}
          type="file"
          webkitdirectory=""
          directory=""
          multiple
          accept="image/*"
          className="hidden"
          onChange={handleFichiers}
        />
        <Button type="button" variant="outline" onClick={() => dossierInputRef.current?.click()}>
          Choisir un dossier
        </Button>

        <input
          ref={fichiersInputRef}
          type="file"
          multiple
          accept="image/*"
          className="hidden"
          onChange={handleFichiers}
        />
        <Button type="button" variant="ghost" onClick={() => fichiersInputRef.current?.click()}>
          Ou sélectionner plusieurs fichiers
        </Button>

        <p className="text-sm text-slate-500">
          Les photos sont triées automatiquement par nom de fichier (ordre de prise de vue).
        </p>
      </div>

      {fichiers.length > 0 && (
        <div
          className={`rounded-xl border p-4 text-sm ${
            comptesEgaux ? 'border-brand-teal/30 bg-brand-teal-tint text-brand-navy' : 'border-red-200 bg-red-50 text-red-600'
          }`}
        >
          {comptesEgaux
            ? `${fichiers.length} photos importées pour ${roster.length} apprenants — les comptes correspondent.`
            : `${fichiers.length} photos importées pour ${roster.length} apprenants — les comptes doivent être égaux avant de continuer.`}
        </div>
      )}

      {comptesEgaux && !resultats && (
        <div className="flex flex-col gap-3">
          <div className="max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white">
            <table className="w-full text-left text-sm">
              <thead className="bg-brand-blue-tint text-brand-navy">
                <tr>
                  <th className="px-3 py-2 font-semibold">#</th>
                  <th className="px-3 py-2 font-semibold">Photo</th>
                  <th className="px-3 py-2 font-semibold">Fichier</th>
                  <th className="px-3 py-2 font-semibold">Apprenant assigné</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {apercu.map((ligne, index) => (
                  <tr key={ligne.file.name + index}>
                    <td className="px-3 py-2">{index + 1}</td>
                    <td className="px-3 py-2">
                      <img src={ligne.url} alt="" className="h-12 w-12 rounded-lg object-cover" />
                    </td>
                    <td className="px-3 py-2 text-slate-500">{ligne.file.name}</td>
                    <td className="px-3 py-2 font-medium">
                      {ligne.apprenant ? `${ligne.apprenant.prenom} ${ligne.apprenant.nom}` : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Button onClick={handleConfirmer} loading={importing} className="self-end">
            Confirmer l'import
          </Button>
        </div>
      )}

      {resultats && (
        <div className="rounded-xl border border-slate-200 bg-white p-4">
          <p className="mb-3 font-medium text-brand-navy">
            Résultats — {resultats.filter((r) => r.success).length} / {resultats.length} photos importées avec succès
          </p>
          <ul className="flex flex-col divide-y divide-slate-100 text-sm">
            {resultats.map((r) => (
              <li key={r.apprenant_id} className="flex items-center justify-between py-2">
                <span>{r.nom}</span>
                {r.success ? (
                  <span className="rounded-full bg-brand-teal/10 px-2 py-0.5 text-xs font-medium text-brand-teal">Importée</span>
                ) : (
                  <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600">{r.message}</span>
                )}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
