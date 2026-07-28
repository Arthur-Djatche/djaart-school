import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import MatiereFormModal from './MatiereFormModal'

export default function MatieresPage() {
  const { showToast } = useToast()
  const [matieres, setMatieres] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchMatieres({ search, page })
      setMatieres(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [search])

  useEffect(() => {
    load(1)
  }, [load])

  const handleSubmit = async (payload) => {
    if (editing) {
      await parametrageApi.updateMatiere(editing.id, payload)
      showToast('Matière mise à jour.', 'success')
    } else {
      await parametrageApi.createMatiere(payload)
      showToast('Matière créée.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (matiere) => {
    if (!window.confirm(`Supprimer ${matiere.nom} ?`)) return
    await parametrageApi.deleteMatiere(matiere.id)
    showToast('Matière supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'niveau', label: 'Niveau', render: (row) => row.niveau?.libelle ?? '—' },
    { key: 'semestre', label: 'Semestre', render: (row) => row.semestre?.libelle ?? '—' },
    { key: 'unite_enseignement', label: 'UE', render: (row) => row.unite_enseignement ? `${row.unite_enseignement.code} — ${row.unite_enseignement.nom}` : '—' },
    { key: 'groupe', label: 'Groupe', render: (row) => row.groupe ?? '—' },
    { key: 'coefficient', label: 'Coefficient' },
    { key: 'credits_ects', label: 'Crédits ECTS', render: (row) => row.credits_ects ?? '—' },
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
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-brand-navy">Matières</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle matière</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={matieres}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher une matière…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <MatiereFormModal matiere={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
