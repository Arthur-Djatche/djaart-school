import { useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Select from '../../../components/ui/Select'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'
import UniteEnseignementFormModal from './UniteEnseignementFormModal'

export default function UnitesEnseignementPage() {
  const { showToast } = useToast()
  const [filieres, setFilieres] = useState([])
  const [filiereId, setFiliereId] = useState('')
  const [niveaux, setNiveaux] = useState([])
  const [niveauId, setNiveauId] = useState('')
  const [semestres, setSemestres] = useState([])
  const [semestreId, setSemestreId] = useState('')
  const [unites, setUnites] = useState(null)
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [loadingUnites, setLoadingUnites] = useState(false)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchFilieres({ page: 1 })
      setFilieres(data.data)
      setLoadingOptions(false)
    })()
  }, [])

  useEffect(() => {
    setNiveauId('')
    setSemestreId('')
    setSemestres([])
    setUnites(null)
    if (!filiereId) {
      setNiveaux([])
      return
    }
    (async () => {
      const { data } = await parametrageApi.fetchNiveauxByFiliere(filiereId)
      setNiveaux(data.data.filter((n) => n.type_systeme === 'lmd'))
    })()
  }, [filiereId])

  useEffect(() => {
    setSemestreId('')
    setUnites(null)
    if (!niveauId) {
      setSemestres([])
      return
    }
    (async () => {
      const { data } = await pedagogieApi.fetchSemestres({ niveau_id: niveauId })
      setSemestres(data.data)
    })()
  }, [niveauId])

  const chargerUnites = async () => {
    if (!semestreId) return
    setUnites(null)
    setLoadingUnites(true)
    try {
      const { data } = await parametrageApi.fetchUnitesEnseignementBySemestre(semestreId)
      setUnites(data.data)
    } finally {
      setLoadingUnites(false)
    }
  }

  useEffect(() => {
    if (semestreId) chargerUnites()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [semestreId])

  const handleSubmit = async (payload) => {
    if (editing) {
      await parametrageApi.updateUniteEnseignement(editing.id, payload)
      showToast("Unité d'enseignement mise à jour.", 'success')
    } else {
      await parametrageApi.createUniteEnseignement(payload)
      showToast("Unité d'enseignement créée.", 'success')
    }
    setShowForm(false)
    chargerUnites()
  }

  const handleDelete = async (unite) => {
    if (!window.confirm(`Supprimer ${unite.nom} ?`)) return
    await parametrageApi.deleteUniteEnseignement(unite.id)
    showToast("Unité d'enseignement supprimée.", 'success')
    chargerUnites()
  }

  const columns = [
    { key: 'code', label: 'Code' },
    { key: 'nom', label: 'Intitulé' },
    { key: 'type', label: 'Type' },
    { key: 'credits_ects', label: 'Crédits (somme des EC)', render: (row) => row.credits_ects ?? 0 },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <div className="flex gap-2">
          <Button variant="ghost" onClick={() => { setEditing(row); setShowForm(true) }}>
            Modifier
          </Button>
          <Button variant="ghost" onClick={() => handleDelete(row)}>
            Supprimer
          </Button>
        </div>
      ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Unités d'enseignement</h1>
        <p className="text-sm text-slate-500">
          Chaque UE appartient à un semestre précis (niveaux LMD uniquement) et regroupe les EC (matières) qui la composent.
        </p>
      </div>

      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 md:flex-row md:items-end">
            <Select
              id="filiere_id"
              label="Filière"
              value={filiereId}
              onChange={(e) => setFiliereId(e.target.value)}
              placeholder="Sélectionner une filière"
              options={filieres.map((f) => ({ value: f.id, label: f.nom }))}
              className="md:flex-1"
            />
            {filiereId && (
              <Select
                id="niveau_id"
                label="Niveau"
                value={niveauId}
                onChange={(e) => setNiveauId(e.target.value)}
                placeholder="Sélectionner un niveau"
                options={niveaux.map((n) => ({ value: n.id, label: n.libelle }))}
                className="md:flex-1"
              />
            )}
            {niveauId && (
              <Select
                id="semestre_id"
                label="Semestre"
                value={semestreId}
                onChange={(e) => setSemestreId(e.target.value)}
                placeholder="Sélectionner un semestre"
                options={semestres.map((s) => ({ value: s.id, label: s.libelle }))}
                className="md:flex-1"
              />
            )}
          </div>

          {loadingUnites && <p className="text-slate-500">Chargement…</p>}

          {unites && !loadingUnites && (
            <>
              <div className="flex justify-end">
                <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle UE</Button>
              </div>
              <Table columns={columns} rows={unites} />
            </>
          )}
        </div>
      )}

      {showForm && (
        <UniteEnseignementFormModal
          uniteEnseignement={editing}
          semestreId={semestreId}
          onClose={() => setShowForm(false)}
          onSubmit={handleSubmit}
        />
      )}
    </DashboardLayout>
  )
}
