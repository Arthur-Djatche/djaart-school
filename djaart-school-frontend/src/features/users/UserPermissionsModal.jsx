import { useState } from 'react'
import Button from '../../components/ui/Button'
import Modal from '../../components/ui/Modal'
import { PERMISSIONS_CATALOG } from '../../config/permissionsCatalog'

export default function UserPermissionsModal({ user, onClose, onSubmit }) {
  const [selected, setSelected] = useState(new Set(user.permissions ?? []))
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const toggle = (cle) => {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(cle)) {
        next.delete(cle)
      } else {
        next.add(cle)
      }
      return next
    })
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit(Array.from(selected))
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={`Droits d'accès — ${user.name}`} onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <p className="text-sm text-slate-500">
          Droits supplémentaires accordés en complément du rôle « {user.roles?.join(', ')} ».
        </p>

        <div className="flex max-h-[50vh] flex-col gap-4 overflow-y-auto">
          {PERMISSIONS_CATALOG.map((groupe) => (
            <div key={groupe.domaine}>
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{groupe.domaine}</p>
              <div className="flex flex-col gap-2">
                {groupe.droits.map((droit) => (
                  <label key={droit.cle} className="flex items-center gap-2 text-sm text-brand-navy">
                    <input
                      type="checkbox"
                      checked={selected.has(droit.cle)}
                      onChange={() => toggle(droit.cle)}
                      className="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue-light/40"
                    />
                    {droit.label}
                  </label>
                ))}
              </div>
            </div>
          ))}
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" loading={loading}>
            Enregistrer
          </Button>
        </div>
      </form>
    </Modal>
  )
}
