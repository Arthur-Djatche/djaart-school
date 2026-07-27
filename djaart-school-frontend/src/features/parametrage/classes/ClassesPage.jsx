import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import ClasseFormModal from './ClasseFormModal'

export default function ClassesPage() {
  const { showToast } = useToast()
  const [classes, setClasses] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchClasses({ search, page })
      setClasses(data.data)
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
      await parametrageApi.updateClasse(editing.id, payload)
      showToast('Classe mise à jour.', 'success')
    } else {
      await parametrageApi.createClasse(payload)
      showToast('Classe créée.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (classe) => {
    if (!window.confirm(`Supprimer ${classe.libelle} ?`)) return
    await parametrageApi.deleteClasse(classe.id)
    showToast('Classe supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'libelle', label: 'Libellé' },
    { key: 'niveau', label: 'Niveau', render: (row) => row.niveau?.libelle ?? '—' },
    { key: 'annee_academique', label: 'Année', render: (row) => row.annee_academique?.libelle ?? '—' },
    { key: 'professeur_principal', label: 'Professeur principal', render: (row) => row.professeur_principal?.name ?? '—' },
    { key: 'effectif_max', label: 'Effectif max' },
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
        <h1 className="text-2xl font-semibold text-brand-navy">Classes</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle classe</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={classes}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher une classe…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <ClasseFormModal classe={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
