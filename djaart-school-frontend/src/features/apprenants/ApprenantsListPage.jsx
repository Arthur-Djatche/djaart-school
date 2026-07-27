import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Table from '../../components/ui/Table'
import * as apprenantsApi from '../../api/apprenantsApi'

export default function ApprenantsListPage() {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [apprenants, setApprenants] = useState([])
  const [loading, setLoading] = useState(false)
  const [searched, setSearched] = useState(false)

  const handleSearch = async (value) => {
    setSearch(value)
    if (!value.trim()) {
      setApprenants([])
      setSearched(false)
      return
    }
    setLoading(true)
    try {
      const { data } = await apprenantsApi.searchApprenants(value)
      setApprenants(data.data)
      setSearched(true)
    } finally {
      setLoading(false)
    }
  }

  const columns = [
    { key: 'matricule', label: 'Matricule' },
    { key: 'nom', label: 'Nom' },
    { key: 'prenom', label: 'Prénom' },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <Button variant="ghost" onClick={() => navigate(`/apprenants/${row.id}`)}>
          Voir la fiche
        </Button>
      ),
    },
  ]

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Apprenants</h1>
        <p className="text-sm text-slate-500">Recherchez un apprenant par nom ou matricule pour accéder à sa fiche.</p>
      </div>

      <Table
        columns={columns}
        rows={apprenants}
        searchValue={search}
        onSearchChange={handleSearch}
        searchPlaceholder="Rechercher un apprenant…"
        emptyMessage={loading ? 'Recherche…' : searched ? 'Aucun apprenant trouvé.' : 'Commencez à taper pour rechercher.'}
      />
    </DashboardLayout>
  )
}
