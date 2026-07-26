import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as parametrageApi from '../../../api/parametrageApi'
import useToast from '../../../hooks/useToast'
import NiveauFormModal from './NiveauFormModal'

const TYPE_LABELS = { classique: 'Classique', lmd: 'LMD' }

export default function NiveauxPage() {
  const { filiereId } = useParams()
  const { showToast } = useToast()
  const [niveaux, setNiveaux] = useState([])
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const { data } = await parametrageApi.fetchNiveauxByFiliere(filiereId)
      setNiveaux(data.data)
    } finally {
      setLoading(false)
    }
  }, [filiereId])

  useEffect(() => {
    load()
  }, [load])

  const handleSubmit = async (payload) => {
    if (editing) {
      await parametrageApi.updateNiveau(editing.id, payload)
      showToast('Niveau mis à jour.', 'success')
    } else {
      await parametrageApi.createNiveau(payload)
      showToast('Niveau créé.', 'success')
    }
    setShowForm(false)
    load()
  }

  const handleDelete = async (niveau) => {
    if (!window.confirm(`Supprimer ${niveau.libelle} ?`)) return
    await parametrageApi.deleteNiveau(niveau.id)
    showToast('Niveau supprimé.', 'success')
    load()
  }

  const columns = [
    { key: 'libelle', label: 'Libellé' },
    { key: 'ordre', label: 'Ordre' },
    { key: 'type_systeme', label: 'Système', render: (row) => TYPE_LABELS[row.type_systeme] },
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
      <Link to="/parametrage/filieres" className="mb-2 inline-block text-sm text-brand-blue hover:underline">
        ← Retour aux filières
      </Link>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-brand-navy">Niveaux</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouveau niveau</Button>
      </div>

      {loading ? <p className="text-slate-500">Chargement…</p> : <Table columns={columns} rows={niveaux} />}

      {showForm && (
        <NiveauFormModal
          niveau={editing}
          filiereId={filiereId}
          onClose={() => setShowForm(false)}
          onSubmit={handleSubmit}
        />
      )}
    </DashboardLayout>
  )
}
