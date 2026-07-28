import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Table from '../../components/ui/Table'
import * as landingApi from '../../api/landingApi'

export default function DemandesDemoPage() {
  const [demandes, setDemandes] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await landingApi.fetchDemandesDemo({ page })
      setDemandes(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(1)
  }, [load])

  const columns = [
    { key: 'created_at', label: 'Reçue le', render: (row) => new Date(row.created_at).toLocaleDateString('fr-FR') },
    { key: 'nom', label: 'Contact' },
    { key: 'nom_etablissement', label: 'Établissement' },
    { key: 'email', label: 'E-mail' },
    { key: 'telephone', label: 'Téléphone', render: (row) => row.telephone ?? '—' },
    { key: 'effectif_estime', label: 'Effectif estimé', render: (row) => row.effectif_estime ?? '—' },
    { key: 'message', label: 'Message', render: (row) => row.message ?? '—' },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Demandes de démo</h1>
        <p className="text-sm text-slate-500">Prospects ayant soumis le formulaire "Demander une démo" sur la landing page.</p>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={demandes}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
          emptyMessage="Aucune demande de démo pour l'instant."
        />
      )}
    </DashboardLayout>
  )
}
