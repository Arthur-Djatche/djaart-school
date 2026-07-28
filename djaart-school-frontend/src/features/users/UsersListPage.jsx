import { useCallback, useEffect, useState } from 'react'
import DashboardLayout from '../../components/layout/DashboardLayout'
import Button from '../../components/ui/Button'
import Table from '../../components/ui/Table'
import * as usersApi from '../../api/usersApi'
import useToast from '../../hooks/useToast'
import UserFormModal from './UserFormModal'
import UserPermissionsModal from './UserPermissionsModal'

export default function UsersListPage() {
  const { showToast } = useToast()
  const [users, setUsers] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [editingUser, setEditingUser] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [permissionsUser, setPermissionsUser] = useState(null)

  const loadUsers = useCallback(async (page = 1, searchTerm = search) => {
    setLoading(true)
    try {
      const { data } = await usersApi.fetchUsers({ search: searchTerm, page })
      setUsers(data.data);
      setMeta({ current_page: data.meta.current_page, last_page: data.meta.last_page })
    } finally {
      setLoading(false)
    }
  }, [search])

  useEffect(() => {
    loadUsers(1, search)
  }, [search]) // eslint-disable-line react-hooks/exhaustive-deps

  const handleCreate = () => {
    setEditingUser(null)
    setShowForm(true)
  }

  const handleEdit = (user) => {
    setEditingUser(user)
    setShowForm(true)
  }

  const handleDelete = async (user) => {
    if (!window.confirm(`Supprimer ${user.name} ?`)) return
    await usersApi.deleteUser(user.id)
    showToast('Utilisateur supprimé.', 'success')
    loadUsers(meta.current_page)
  }

  const handleSubmitPermissions = async (permissions) => {
    await usersApi.updateUserPermissions(permissionsUser.id, permissions)
    showToast("Droits d'accès mis à jour.", 'success')
    setPermissionsUser(null)
    loadUsers(meta.current_page)
  }

  const handleSubmit = async (payload) => {
    if (editingUser) {
      await usersApi.updateUser(editingUser.id, payload)
      showToast('Utilisateur mis à jour.', 'success')
    } else {
      await usersApi.createUser(payload)
      showToast('Utilisateur créé.', 'success')
    }
    setShowForm(false)
    loadUsers(meta.current_page)
  }

  const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'email', label: 'E-mail' },
    { key: 'roles', label: 'Rôle', render: (row) => row.roles.join(', ') },
    {
      key: 'etablissement',
      label: 'Établissement',
      render: (row) => row.etablissement?.nom ?? '—',
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (row) => (
        <div className="flex gap-2">
          <Button variant="ghost" onClick={() => handleEdit(row)}>
            Modifier
          </Button>
          <Button variant="ghost" onClick={() => setPermissionsUser(row)}>
            Droits d'accès
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
        <h1 className="text-2xl font-semibold text-brand-navy">Comptes utilisateurs</h1>
        <Button onClick={handleCreate}>Nouvel utilisateur</Button>
      </div>

      {loading ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <Table
          columns={columns}
          rows={users}
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Rechercher un nom ou un e-mail…"
          page={meta.current_page}
          totalPages={meta.last_page}
          onPageChange={(page) => loadUsers(page)}
        />
      )}

      {showForm && (
        <UserFormModal user={editingUser} onClose={() => setShowForm(false)} onSubmit={handleSubmit} />
      )}

      {permissionsUser && (
        <UserPermissionsModal
          user={permissionsUser}
          onClose={() => setPermissionsUser(null)}
          onSubmit={handleSubmitPermissions}
        />
      )}
    </DashboardLayout>
  )
}
