import { useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import FiliereFormModal from './FiliereFormModal'

export default function FilieresPage() {
  const { showToast } = useToast()
  const navigate = useNavigate()
  const [filieres, setFilieres] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchFilieres({ search, page })
      setFilieres(data.data)
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
      await parametrageApi.updateFiliere(editing.id, payload)
      showToast('Filière mise à jour.', 'success')
    } else {
      await parametrageApi.createFiliere(payload)
      showToast('Filière créée.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (filiere) => {
    if (!window.confirm(`Supprimer ${filiere.nom} ?`)) return
    await parametrageApi.deleteFiliere(filiere.id)
    showToast('Filière supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'code', label: 'Code' },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <div className="flex gap-2">
          <Button variant="ghost" onClick={() => navigate(`/parametrage/filieres/${row.id}/niveaux`)}>
            Niveaux
          </Button>
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
        <h1 className="text-2xl font-semibold text-brand-navy">Filières</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle filière</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={filieres}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher une filière…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <FiliereFormModal filiere={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
