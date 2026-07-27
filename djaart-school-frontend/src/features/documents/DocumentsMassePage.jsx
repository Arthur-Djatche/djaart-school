import { useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Select from '../../components/ui/Select'
import * as apprenantsApi from '../../api/apprenantsApi'
import * as inscriptionApi from '../../api/inscriptionApi'
import * as parametrageApi from '../../api/parametrageApi'
import useToast from '../../hooks/useToast'

const TYPES_ATTESTATION = [
  { value: 'scolarite', label: 'Attestation de scolarité' },
  { value: 'frequentation', label: 'Attestation de fréquentation' },
  { value: 'reussite', label: 'Attestation de réussite' },
]

async function fetchToutesLesInscriptions(classeId) {
  let page = 1
  let toutes = []
  // La liste des inscriptions est paginee (15/page) ; une classe peut depasser
  // cette taille, on boucle donc jusqu'a la derniere page pour obtenir tout
  // l'effectif d'un coup — necessaire pour que la selection multiple porte
  // bien sur la classe entiere, pas seulement sur le premier ecran.
  for (;;) {
    const { data } = await inscriptionApi.fetchInscriptions({ classe_id: classeId, page })
    toutes = toutes.concat(data.data)
    if (page >= data.meta.last_page) break
    page += 1
  }
  return toutes
}

export default function DocumentsMassePage() {
  const { showToast } = useToast()
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [inscriptions, setInscriptions] = useState([])
  const [selectionnes, setSelectionnes] = useState(new Set())
  const [typeAttestation, setTypeAttestation] = useState(TYPES_ATTESTATION[0].value)
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingInscriptions, setLoadingInscriptions] = useState(false)
  const [generating, setGenerating] = useState(false)
  const [resultats, setResultats] = useState(null)
  const [typeDocumentGenere, setTypeDocumentGenere] = useState(null)

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchClasses({ page: 1 })
      setClasses(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  useEffect(() => {
    setSelectionnes(new Set())
    setResultats(null)
    if (!classeId) {
      setInscriptions([])
      return
    }
    (async () => {
      setLoadingInscriptions(true)
      try {
        const toutes = await fetchToutesLesInscriptions(classeId)
        setInscriptions(toutes.filter((i) => i.statut !== 'annulee'))
      } finally {
        setLoadingInscriptions(false)
      }
    })()
  }, [classeId])

  const toggleSelection = (apprenantId) => {
    setSelectionnes((current) => {
      const next = new Set(current)
      if (next.has(apprenantId)) next.delete(apprenantId)
      else next.add(apprenantId)
      return next
    })
  }

  const toggleTout = () => {
    if (selectionnes.size === inscriptions.length) {
      setSelectionnes(new Set())
    } else {
      setSelectionnes(new Set(inscriptions.map((i) => i.apprenant.id)))
    }
  }

  const apprenantIdsSelectionnes = () => (selectionnes.size > 0 ? Array.from(selectionnes) : undefined)

  const handleGenererAttestations = async () => {
    setGenerating(true)
    setResultats(null)
    try {
      const { data } = await apprenantsApi.genererAttestationsMasse(classeId, typeAttestation, apprenantIdsSelectionnes())
      setResultats(data.data)
      setTypeDocumentGenere('attestation')
      showToast('Génération terminée.', 'success')
    } catch (err) {
      showToast(err.response?.data?.message ?? 'Une erreur est survenue.', 'error')
    } finally {
      setGenerating(false)
    }
  }

  const handleGenererCartes = async () => {
    setGenerating(true)
    setResultats(null)
    try {
      const { data } = await apprenantsApi.genererCartesScolairesMasse(classeId, apprenantIdsSelectionnes())
      setResultats(data.data)
      setTypeDocumentGenere('carte')
      showToast('Génération terminée.', 'success')
    } catch (err) {
      showToast(err.response?.data?.message ?? 'Une erreur est survenue.', 'error')
    } finally {
      setGenerating(false)
    }
  }

  const idsReussis = () =>
    (resultats ?? [])
      .filter((r) => r.success)
      .map((r) => (typeDocumentGenere === 'attestation' ? r.attestation_id : r.carte_id))

  const zipUrl = () =>
    typeDocumentGenere === 'attestation'
      ? apprenantsApi.attestationsZipUrl(idsReussis())
      : apprenantsApi.cartesScolairesZipUrl(idsReussis())

  const nombreReussis = (resultats ?? []).filter((r) => r.success).length

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Documents en masse</h1>
        <p className="text-sm text-slate-500">
          Générez des attestations ou des cartes scolaires pour toute une classe (ou une sélection) en un seul clic.
        </p>
      </div>

      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <Select
              id="classe_id"
              label="Classe"
              value={classeId}
              onChange={(e) => setClasseId(e.target.value)}
              placeholder="Sélectionner une classe"
              options={classes.map((c) => ({ value: c.id, label: c.libelle }))}
            />
          </div>

          {loadingInscriptions && <p className="text-slate-500">Chargement de l'effectif…</p>}

          {classeId && !loadingInscriptions && (
            <div className="rounded-lg border border-slate-200 bg-white p-4">
              <div className="mb-3 flex flex-wrap items-end gap-3">
                <Button type="button" variant="ghost" onClick={toggleTout}>
                  {selectionnes.size === inscriptions.length ? 'Tout désélectionner' : 'Tout sélectionner'}
                </Button>
                <span className="text-sm text-slate-500">
                  {selectionnes.size > 0 ? `${selectionnes.size} sélectionné(s)` : `Tous les ${inscriptions.length} apprenants (aucune sélection)`}
                </span>
              </div>

              <div className="mb-4 max-h-64 overflow-y-auto rounded border border-slate-100">
                {inscriptions.map((inscription) => (
                  <label key={inscription.id} className="flex items-center gap-2 border-b border-slate-50 px-3 py-2 text-sm last:border-b-0">
                    <input
                      type="checkbox"
                      checked={selectionnes.has(inscription.apprenant.id)}
                      onChange={() => toggleSelection(inscription.apprenant.id)}
                    />
                    <span>
                      {inscription.apprenant.matricule} — {inscription.apprenant.prenom} {inscription.apprenant.nom}
                    </span>
                    {inscription.statut !== 'validee' && (
                      <span className="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">
                        Inscription {inscription.statut}
                      </span>
                    )}
                  </label>
                ))}
              </div>

              <div className="flex flex-wrap items-end gap-3">
                <Select
                  id="type_attestation"
                  label="Type d'attestation"
                  value={typeAttestation}
                  onChange={(e) => setTypeAttestation(e.target.value)}
                  options={TYPES_ATTESTATION}
                />
                <Button onClick={handleGenererAttestations} loading={generating}>
                  Générer les attestations
                </Button>
                <Button variant="accent" onClick={handleGenererCartes} loading={generating}>
                  Générer les cartes scolaires
                </Button>
              </div>
            </div>
          )}

          {resultats && (
            <div className="rounded-lg border border-slate-200 bg-white p-4">
              <div className="mb-3 flex items-center justify-between">
                <p className="font-medium text-brand-navy">
                  Résultats — {nombreReussis} / {resultats.length} générés avec succès
                </p>
                {nombreReussis > 0 && (
                  <a href={zipUrl()} target="_blank" rel="noopener">
                    <Button variant="ghost">Télécharger tout (ZIP)</Button>
                  </a>
                )}
              </div>
              <ul className="flex flex-col divide-y divide-slate-100 text-sm">
                {resultats.map((r) => (
                  <li key={r.apprenant_id} className="flex items-center justify-between py-2">
                    <span>{r.nom}</span>
                    {r.success ? (
                      <span className="rounded-full bg-brand-teal/10 px-2 py-0.5 text-xs font-medium text-brand-teal">Généré</span>
                    ) : (
                      <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600">{r.message}</span>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
