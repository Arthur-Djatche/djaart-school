import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Table from '../../components/ui/Table'
import * as inscriptionApi from '../../api/inscriptionApi'
import useToast from '../../hooks/useToast'
import InscriptionFormModal from './InscriptionFormModal'

const STATUT_LABELS = {
  en_cours: 'En cours',
  validee: 'Validée',
  suspendue: 'Suspendue',
  annulee: 'Annulée',
  cloturee: 'Clôturée',
}

export default function InscriptionsListPage() {
  const { showToast } = useToast()
  const [inscriptions, setInscriptions] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await inscriptionApi.fetchInscriptions({ page })
      setInscriptions(data.data)
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(1)
  }, [load])

  const handleSubmit = async (payload) => {
    await inscriptionApi.createInscription(payload)
    showToast('Inscription enregistrée.', 'success')
    setShowForm(false)
    load(1)
  }

  const handleCancel = async (inscription) => {
    if (!window.confirm(`Annuler l'inscription de ${inscription.apprenant.prenom} ${inscription.apprenant.nom} ?`)) return
    await inscriptionApi.cancelInscription(inscription.id)
    showToast('Inscription annulée.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'matricule', label: 'Matricule', render: (row) => row.apprenant.matricule },
    { key: 'apprenant', label: 'Apprenant', render: (row) => `${row.apprenant.prenom} ${row.apprenant.nom}` },
    { key: 'classe', label: 'Classe', render: (row) => row.classe?.libelle ?? '—' },
    { key: 'annee', label: 'Année', render: (row) => row.annee_academique?.libelle ?? '—' },
    { key: 'statut', label: 'Statut', render: (row) => STATUT_LABELS[row.statut] },
    { key: 'type_inscription', label: 'Type', render: (row) => (row.type_inscription === 'reinscription' ? 'Réinscription' : 'Nouvelle') },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        row.statut === 'en_cours' && (
          <Button variant="ghost" onClick={() => handleCancel(row)}>
            Annuler
          </Button>
        )
      ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-brand-navy">Inscriptions</h1>
        <Button onClick={() => setShowForm(true)}>Nouvelle inscription</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={inscriptions}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && <InscriptionFormModal onClose={() => setShowForm(false)} onSubmit={handleSubmit} />}
    </DashboardLayout>
  )
}
