import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Table from '../../components/ui/Table'
import * as landingApi from '../../api/landingApi'
import useToast from '../../hooks/useToast'
import ValiderDemandeDemoModal from './ValiderDemandeDemoModal'

const STATUT_LABELS = {
  en_attente: { label: 'En attente', className: 'bg-brand-orange-tint text-brand-orange' },
  validee: { label: 'Validée', className: 'bg-brand-teal-tint text-brand-teal' },
  rejetee: { label: 'Rejetée', className: 'bg-slate-100 text-slate-500' },
}

export default function DemandesDemoPage() {
  const { showToast } = useToast()
  const [demandes, setDemandes] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [demandeAValider, setDemandeAValider] = useState(null)

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

  const handleValider = async (payload) => {
    const { data } = await landingApi.validerDemandeDemo(demandeAValider.id, payload)
    showToast(data.message || 'Demande validée, identifiants envoyés par e-mail.', 'success')
    setDemandeAValider(null)
    load(meta.current_page)
  }

  const columns = [
    { key: 'created_at', label: 'Reçue le', render: (row) => new Date(row.created_at).toLocaleDateString('fr-FR') },
    { key: 'nom', label: 'Contact' },
    { key: 'nom_etablissement', label: 'Établissement' },
    { key: 'email', label: 'E-mail' },
    { key: 'telephone', label: 'Téléphone', render: (row) => row.telephone ?? '—' },
    { key: 'effectif_estime', label: 'Effectif estimé', render: (row) => row.effectif_estime ?? '—' },
    {
      key: 'statut',
      label: 'Statut',
      render: (row) => (
        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUT_LABELS[row.statut]?.className}`}>
          {STATUT_LABELS[row.statut]?.label ?? row.statut}
        </span>
      ),
    },
    {
      key: 'expire',
      label: 'Accès démo',
      render: (row) =>
        row.etablissement?.abonnement_expire_le
          ? new Date(row.etablissement.abonnement_expire_le).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })
          : '—',
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) =>
        row.statut === 'en_attente' ? (
          <Button variant="ghost" onClick={() => setDemandeAValider(row)}>
            Valider
          </Button>
        ) : (
          '—'
        ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Demandes de démo</h1>
        <p className="text-sm text-slate-500">
          Prospects ayant soumis le formulaire "Demander une démo" — valider crée un accès de démonstration limité à 48h.
        </p>
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

      {demandeAValider && (
        <ValiderDemandeDemoModal
          demande={demandeAValider}
          onClose={() => setDemandeAValider(null)}
          onSubmit={handleValider}
        />
      )}
    </DashboardLayout>
  )
}
