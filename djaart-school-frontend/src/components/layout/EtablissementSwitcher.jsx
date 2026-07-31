import { useState } from 'react'
import * as profilApi from '../../api/profilApi'
import useAuth from '../../hooks/useAuth'

// Partage entre Topbar (desktop, sm:block) et Sidebar (tiroir mobile,
// sm:hidden) pour eviter de dupliquer la logique de bascule.
export default function EtablissementSwitcher({ className = '' }) {
  const { user } = useAuth()
  const [basculant, setBasculant] = useState(false)

  if (!user?.etablissements_geres || user.etablissements_geres.length <= 1) {
    return null
  }

  const handleBasculer = async (event) => {
    const etablissementId = Number(event.target.value)
    if (!etablissementId || etablissementId === user.etablissement?.id) return

    setBasculant(true)
    try {
      await profilApi.basculerEtablissement(etablissementId)
      // Rechargement complet : toutes les pages du dashboard dependent de
      // l'etablissement actif, plus simple et plus sur que d'auditer chaque
      // ecran pour le rendre reactif a ce changement.
      window.location.href = '/dashboard'
    } finally {
      setBasculant(false)
    }
  }

  return (
    <select
      value={user.etablissement?.id ?? ''}
      onChange={handleBasculer}
      disabled={basculant}
      className={`rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-brand-navy outline-none transition focus:border-brand-blue ${className}`}
      aria-label="Établissement actif"
    >
      {user.etablissements_geres.map((etablissement) => (
        <option key={etablissement.id} value={etablissement.id}>
          {etablissement.nom}
          {etablissement.role ? ` (${etablissement.role})` : ''}
        </option>
      ))}
    </select>
  )
}
