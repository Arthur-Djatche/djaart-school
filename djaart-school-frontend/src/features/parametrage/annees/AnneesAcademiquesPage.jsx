import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import AnneeAcademiqueFormModal from './AnneeAcademiqueFormModal'

const STATUT_LABELS = {
  en_preparation: 'En préparation',
  en_cours: 'En cours',
  cloturee: 'Clôturée',
}

export default function AnneesAcademiquesPage() {
  const { showToast } = useToast()
  const [annees, setAnnees] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchAnneesAcademiques({ search, page })
      setAnnees(data.data)
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
      await parametrageApi.updateAnneeAcademique(editing.id, payload)
      showToast('Année académique mise à jour.', 'success')
    } else {
      await parametrageApi.createAnneeAcademique(payload)
      showToast('Année académique créée.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (annee) => {
    if (!window.confirm(`Supprimer ${annee.libelle} ?`)) return
    await parametrageApi.deleteAnneeAcademique(annee.id)
    showToast('Année académique supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'libelle', label: 'Libellé' },
    { key: 'date_debut', label: 'Début' },
    { key: 'date_fin', label: 'Fin' },
    { key: 'statut', label: 'Statut', render: (row) => STATUT_LABELS[row.statut] },
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
        <h1 className="text-2xl font-semibold text-brand-navy">Années académiques</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle année académique</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={annees}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher une année…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <AnneeAcademiqueFormModal annee={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
