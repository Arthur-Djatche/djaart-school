import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import DepartementFormModal from './DepartementFormModal'

export default function DepartementsPage() {
  const { showToast } = useToast()
  const [departements, setDepartements] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchDepartements({ search, page })
      setDepartements(data.data)
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
      await parametrageApi.updateDepartement(editing.id, payload)
      showToast('Département mis à jour.', 'success')
    } else {
      await parametrageApi.createDepartement(payload)
      showToast('Département créé.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (departement) => {
    if (!window.confirm(`Supprimer ${departement.nom} ?`)) return
    await parametrageApi.deleteDepartement(departement.id)
    showToast('Département supprimé.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'code', label: 'Code' },
    { key: 'chef_departement', label: 'Chef de département', render: (row) => row.chef_departement?.name ?? '—' },
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
        <h1 className="text-2xl font-semibold text-brand-navy">Départements</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouveau département</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={departements}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher un département…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <DepartementFormModal departement={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
