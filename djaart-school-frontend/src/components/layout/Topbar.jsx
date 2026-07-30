import { useState } from 'react'
import { Link } from 'react-router-dom'
import logo from '../../assets/logo.png'
import * as profilApi from '../../api/profilApi'
import useAuth from '../../hooks/useAuth'
import Button from '../ui/Button'

export default function Topbar({ onMenuClick }) {
  const { user, logout } = useAuth()
  const [basculant, setBasculant] = useState(false)

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
    <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-soft sm:px-6">
      <div className="flex items-center gap-2 sm:gap-3">
        <button
          type="button"
          onClick={onMenuClick}
          className="rounded-lg p-2 text-brand-navy transition hover:bg-slate-100 md:hidden"
          aria-label="Ouvrir le menu"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="20" y2="17" />
          </svg>
        </button>
        <img src={logo} alt="" className="h-8 w-auto sm:h-9" />
        <span className="text-base font-extrabold tracking-tight text-brand-navy sm:text-lg">
          DJAART <span className="text-brand-orange">SCHOOL</span>
        </span>
      </div>
      <div className="flex items-center gap-2 sm:gap-4">
        {user?.etablissements_geres?.length > 1 && (
          <select
            value={user.etablissement?.id ?? ''}
            onChange={handleBasculer}
            disabled={basculant}
            className="hidden rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-brand-navy outline-none transition focus:border-brand-blue sm:block"
            aria-label="Établissement actif"
          >
            {user.etablissements_geres.map((etablissement) => (
              <option key={etablissement.id} value={etablissement.id}>
                {etablissement.nom}
              </option>
            ))}
          </select>
        )}
        {user && (
          <Link to="/mon-profil" className="hidden items-center gap-2 text-sm text-brand-navy hover:underline sm:flex">
            {user.photo_url ? (
              <img src={user.photo_url} alt="" className="h-7 w-7 rounded-full object-cover" />
            ) : (
              <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-blue-tint text-xs font-semibold text-brand-blue">
                {user.name?.[0]?.toUpperCase()}
              </span>
            )}
            {user.civilite ? `${user.civilite} ${user.name}` : user.name}
          </Link>
        )}
        <Button variant="ghost" size="sm" onClick={logout}>
          Déconnexion
        </Button>
      </div>
    </header>
  )
}
