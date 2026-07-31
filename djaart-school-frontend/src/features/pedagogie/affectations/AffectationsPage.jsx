import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import HelpBanner from '../../../components/ui/HelpBanner'
import Table from '../../../components/ui/Table'
import * as pedagogieApi from '../../../api/pedagogieApi'
import useToast from '../../../hooks/useToast'
import AffectationFormModal from './AffectationFormModal'

export default function AffectationsPage() {
  const { showToast } = useToast()
  const [affectations, setAffectations] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await pedagogieApi.fetchAffectations({ page })
      setAffectations(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(1)
  }, [load])

  const handleSubmit = async (payload) => {
    await pedagogieApi.createAffectation(payload)
    showToast('Affectation créée.', 'success')
    setShowForm(false)
    load(1)
  }

  const handleDelete = async (affectation) => {
    if (!window.confirm(`Supprimer cette affectation (${affectation.matiere?.nom} — ${affectation.classe?.libelle}) ?`)) return
    await pedagogieApi.deleteAffectation(affectation.id)
    showToast('Affectation supprimée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'classe', label: 'Classe', render: (row) => row.classe?.libelle ?? '—' },
    { key: 'matiere', label: 'Matière', render: (row) => row.matiere?.nom ?? '—' },
    { key: 'enseignant', label: 'Enseignant', render: (row) => row.enseignant?.name ?? '—' },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <Button variant="ghost" onClick={() => handleDelete(row)}>
          Supprimer
        </Button>
      ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-brand-navy">Affectations enseignants</h1>
        <Button onClick={() => setShowForm(true)}>Nouvelle affectation</Button>
      </div>

      <HelpBanner>
        Une affectation relie un enseignant à une classe et une matière pour l'année en cours — la classe et la
        matière doivent déjà exister (Paramétrage). Sans affectation, l'enseignant ne peut saisir aucune note pour
        cette matière, et elle bloquera la clôture de séquence tant qu'aucune note n'y est saisie.
      </HelpBanner>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={affectations}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && <AffectationFormModal onClose={() => setShowForm(false)} onSubmit={handleSubmit} />}
    </DashboardLayout>
  )
}
