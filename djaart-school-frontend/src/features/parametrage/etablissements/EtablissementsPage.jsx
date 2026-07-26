import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useAuth from '../../../hooks/useAuth'
import useToast from '../../../hooks/useToast'
import EtablissementFormModal from './EtablissementFormModal'

const TYPE_LABELS = {
  primaire: 'Primaire',
  secondaire: 'Secondaire',
  universitaire: 'Universitaire',
  centre_formation: 'Centre de formation',
}

export default function EtablissementsPage() {
  const { user } = useAuth()
  const { showToast } = useToast()
  const isSuperAdmin = user?.roles.includes('super_admin')

  const [etablissements, setEtablissements] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchEtablissements({ search, page })
      setEtablissements(data.data)
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
      await parametrageApi.updateEtablissement(editing.id, payload)
      showToast('Établissement mis à jour.', 'success')
    } else {
      await parametrageApi.createEtablissement(payload)
      showToast('Établissement créé.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (etablissement) => {
    if (!window.confirm(`Supprimer ${etablissement.nom} ?`)) return
    await parametrageApi.deleteEtablissement(etablissement.id)
    showToast('Établissement supprimé.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'nom', label: 'Nom' },
    { key: 'type_etablissement', label: 'Type', render: (row) => TYPE_LABELS[row.type_etablissement] },
    { key: 'sigle', label: 'Sigle' },
    { key: 'adresse', label: 'Adresse' },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <div className="flex gap-2">
          <Button variant="ghost" onClick={() => { setEditing(row); setShowForm(true) }}>
            Modifier
          </Button>
          {isSuperAdmin && (
            <Button variant="ghost" onClick={() => handleDelete(row)}>
              Supprimer
            </Button>
          )}
        </div>
      ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-brand-navy">Établissement{isSuperAdmin ? 's' : ''}</h1>
        {isSuperAdmin && (
          <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvel établissement</Button>
        )}
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={etablissements}
          searchValue={search}
          onSearchChange={isSuperAdmin ? setSearch : undefined}
          searchPlaceholder="Rechercher un établissement…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <EtablissementFormModal etablissement={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
