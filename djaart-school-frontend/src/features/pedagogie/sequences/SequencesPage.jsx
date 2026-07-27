import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Table from '../../../components/ui/Table'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'
import SequenceFormModal from './SequenceFormModal'

export default function SequencesPage() {
  const { showToast } = useToast()
  const [sequences, setSequences] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await pedagogieApi.fetchSequences({ page })
      setSequences(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(1)
  }, [load])

  const handleSubmit = async (payload) => {
    if (editing) {
      await pedagogieApi.updateSequence(editing.id, payload)
      showToast('Séquence mise à jour.', 'success')
    } else {
      await pedagogieApi.createSequence(payload)
      showToast('Séquence créée.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (sequence) => {
    if (!window.confirm(`Supprimer ${sequence.libelle} ?`)) return
    await pedagogieApi.deleteSequence(sequence.id)
    showToast('Séquence supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'niveau', label: 'Niveau', render: (row) => row.niveau?.libelle ?? '—' },
    { key: 'annee_academique', label: 'Année', render: (row) => row.annee_academique?.libelle ?? '—' },
    { key: 'numero', label: 'Numéro' },
    { key: 'libelle', label: 'Libellé' },
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
        <h1 className="text-2xl font-semibold text-brand-navy">Séquences</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouvelle séquence</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={sequences}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <SequenceFormModal sequence={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
