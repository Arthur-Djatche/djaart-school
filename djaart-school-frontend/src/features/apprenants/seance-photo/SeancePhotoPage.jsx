import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Select from '../../../components/ui/Select'
import Spinner from '../../../components/ui/Spinner'
import * as apprenantsApi from '../../../api/apprenantsApi'
import * as inscriptionApi from '../../../api/inscriptionApi'
import * as parametrageApi from '../../../api/parametrageApi'
import CameraCaptureMode from './CameraCaptureMode'
import ImportPhotosMode from './ImportPhotosMode'

async function fetchToutesLesInscriptions(classeId) {
  let page = 1
  let toutes = []
  for (;;) {
    const { data } = await inscriptionApi.fetchInscriptions({ classe_id: classeId, page })
    toutes = toutes.concat(data.data)
    if (page >= data.meta.last_page) break
    page += 1
  }
  return toutes
}

export default function SeancePhotoPage() {
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [roster, setRoster] = useState([])
  const [mode, setMode] = useState('camera')
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingRoster, setLoadingRoster] = useState(false)

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchClasses({ page: 1 })
      setClasses(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  const chargerRoster = async (id) => {
    setLoadingRoster(true)
    try {
      const toutes = await fetchToutesLesInscriptions(id)
      const actives = toutes
        .filter((i) => i.statut !== 'annulee')
        .sort((a, b) => a.apprenant.nom.localeCompare(b.apprenant.nom) || a.apprenant.prenom.localeCompare(b.apprenant.prenom))
      setRoster(actives)
    } finally {
      setLoadingRoster(false)
    }
  }

  useEffect(() => {
    setRoster([])
    if (classeId) chargerRoster(classeId)
  }, [classeId])

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Séance photo</h1>
        <p className="text-sm text-slate-500">
          Photographiez une classe directement depuis le navigateur, ou importez un dossier de photos déjà prises dans
          l'ordre de la liste (nom, prénom).
        </p>
      </div>

      {loadingOptions ? (
        <Spinner />
      ) : (
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-soft sm:flex-row sm:items-end sm:justify-between">
            <Select
              id="classe_id"
              label="Classe"
              value={classeId}
              onChange={(e) => setClasseId(e.target.value)}
              placeholder="Sélectionner une classe"
              options={classes.map((c) => ({ value: c.id, label: c.libelle }))}
              className="sm:max-w-xs"
            />
            {classeId && (
              <a href={apprenantsApi.listePhotosSeanceUrl(classeId)} target="_blank" rel="noopener">
                <Button type="button" variant="outline">
                  Télécharger la liste de la classe (PDF)
                </Button>
              </a>
            )}
          </div>

          {loadingRoster && <Spinner label="Chargement de l'effectif…" />}

          {classeId && !loadingRoster && roster.length === 0 && (
            <p className="text-sm text-slate-500">Aucun apprenant actif dans cette classe.</p>
          )}

          {classeId && !loadingRoster && roster.length > 0 && (
            <>
              <div className="flex gap-2 rounded-xl border border-slate-200 bg-white p-1.5 shadow-soft">
                <button
                  type="button"
                  onClick={() => setMode('camera')}
                  className={`flex-1 rounded-lg px-4 py-2 text-sm font-medium transition ${
                    mode === 'camera' ? 'bg-brand-blue text-white shadow-soft' : 'text-brand-navy hover:bg-slate-100'
                  }`}
                >
                  Caméra
                </button>
                <button
                  type="button"
                  onClick={() => setMode('import')}
                  className={`flex-1 rounded-lg px-4 py-2 text-sm font-medium transition ${
                    mode === 'import' ? 'bg-brand-blue text-white shadow-soft' : 'text-brand-navy hover:bg-slate-100'
                  }`}
                >
                  Importer des photos
                </button>
              </div>

              {mode === 'camera' ? (
                <CameraCaptureMode key={classeId} roster={roster} />
              ) : (
                <ImportPhotosMode
                  key={classeId}
                  classeId={classeId}
                  roster={roster}
                  onImporte={() => chargerRoster(classeId)}
                />
              )}
            </>
          )}
        </div>
      )}
    </DashboardLayout>
  )
}
