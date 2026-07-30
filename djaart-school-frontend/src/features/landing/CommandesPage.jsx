import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Table from '../../components/ui/Table'
import * as landingApi from '../../api/landingApi'
import useToast from '../../hooks/useToast'
import ValiderCommandeModal from './ValiderCommandeModal'

const STATUT_LABELS = {
  en_attente: { label: 'En attente', className: 'bg-brand-orange-tint text-brand-orange' },
  validee: { label: 'Validée', className: 'bg-brand-teal-tint text-brand-teal' },
  rejetee: { label: 'Rejetée', className: 'bg-slate-100 text-slate-500' },
}

export default function CommandesPage() {
  const { showToast } = useToast()
  const [commandes, setCommandes] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [commandeAValider, setCommandeAValider] = useState(null)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await landingApi.fetchCommandes({ page })
      setCommandes(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(1)
  }, [load])

  const handleValider = async (payload) => {
    await landingApi.validerCommande(commandeAValider.id, payload)
    showToast('Commande validée, identifiants envoyés par e-mail.', 'success')
    setCommandeAValider(null)
    load(meta.current_page)
  }

  const columns = [
    { key: 'created_at', label: 'Reçue le', render: (row) => new Date(row.created_at).toLocaleDateString('fr-FR') },
    { key: 'nom', label: 'Contact' },
    { key: 'nom_etablissement', label: 'Établissement' },
    { key: 'ville', label: 'Ville' },
    { key: 'nombre_apprenants', label: 'Apprenants' },
    { key: 'email', label: 'E-mail' },
    { key: 'telephone', label: 'Téléphone' },
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
      key: 'actions',
      label: 'Actions',
      render: (row) =>
        row.statut === 'en_attente' ? (
          <Button variant="ghost" onClick={() => setCommandeAValider(row)}>
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
        <h1 className="text-2xl font-semibold text-brand-navy">Commandes</h1>
        <p className="text-sm text-slate-500">
          Commandes soumises depuis la page publique — valider crée l'établissement et son compte administrateur.
        </p>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={commandes}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
          emptyMessage="Aucune commande pour l'instant."
        />
      )}

      {commandeAValider && (
        <ValiderCommandeModal
          commande={commandeAValider}
          onClose={() => setCommandeAValider(null)}
          onSubmit={handleValider}
        />
      )}
    </DashboardLayout>
  )
}
