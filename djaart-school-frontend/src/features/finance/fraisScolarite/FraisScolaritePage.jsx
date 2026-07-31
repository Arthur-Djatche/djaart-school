import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import HelpBanner from '../../../components/ui/HelpBanner'
import Table from '../../../components/ui/Table'
import * as financeApi from '../../../api/financeApi'
import useToast from '../../../hooks/useToast'
import FraisScolariteFormModal from './FraisScolariteFormModal'

const MODE_LABELS = { comptant: 'Comptant', tranches: 'Par tranches' }

export default function FraisScolaritePage() {
  const { showToast } = useToast()
  const [items, setItems] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)

  const load = useCallback(async (page = 1) => {
    setLoading(true)
    try {
      const { data } = await financeApi.fetchFraisScolarite({ page })
      setItems(data.data)
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
      await financeApi.updateFraisScolarite(editing.id, payload)
      showToast('Frais de scolarité mis à jour.', 'success')
    } else {
      await financeApi.createFraisScolarite(payload)
      showToast('Frais de scolarité créés.', 'success')
    }
    setShowForm(false)
    load(meta.current_page)
  }

  const handleDelete = async (item) => {
    if (!window.confirm(`Supprimer les frais de ${item.niveau?.libelle} ?`)) return
    await financeApi.deleteFraisScolarite(item.id)
    showToast('Frais de scolarité supprimés.', 'success')
    load(meta.current_page)
  }

  const columns = [
    { key: 'niveau', label: 'Niveau', render: (row) => row.niveau?.libelle ?? '—' },
    { key: 'annee_academique', label: 'Année', render: (row) => row.annee_academique?.libelle ?? '—' },
    { key: 'montant_total', label: 'Montant total' },
    { key: 'frais_inscription', label: "Frais d'inscription" },
    { key: 'mode', label: 'Mode', render: (row) => MODE_LABELS[row.mode] },
    { key: 'nombre_tranches', label: 'Tranches' },
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
        <h1 className="text-2xl font-semibold text-brand-navy">Frais de scolarité</h1>
        <Button onClick={() => { setEditing(null); setShowForm(true) }}>Nouveaux frais</Button>
      </div>

      <HelpBanner>
        À configurer pour chaque niveau de l'année en cours, avant toute inscription. « Frais d'inscription » est
        inclus dans le montant total (pas un supplément) : c'est ce montant précis qui valide une inscription une
        fois encaissé, même si la tranche à laquelle il appartient n'est pas soldée. Mode comptant = 1 seule
        échéance ; mode tranches = plusieurs échéances dont la somme doit égaler le montant total.
      </HelpBanner>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={items}
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => load(page)}
        />
      )}

      {showForm && (
        <FraisScolariteFormModal fraisScolarite={editing} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}
    </DashboardLayout>
  )
}
