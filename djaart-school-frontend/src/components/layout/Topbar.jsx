import { useState } from 'react'
import { Link } from 'react-router-dom'
import logo from '../../assets/logo.png'
import useAuth from '../../hooks/useAuth'
import Button from '../ui/Button'
import EtablissementSwitcher from './EtablissementSwitcher'
import GuideModal from './GuideModal'

export default function Topbar({ onMenuClick }) {
  const { user, logout } = useAuth()
  const [showGuide, setShowGuide] = useState(false)

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
        <button
          type="button"
          onClick={() => setShowGuide(true)}
          className="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-sm font-semibold text-brand-navy transition hover:bg-slate-100"
          aria-label="Guide d'utilisation"
          title="Guide d'utilisation"
        >
          ?
        </button>
        {/* Sur mobile, la bascule d'etablissement est dans le tiroir du menu
            (cf. Sidebar) — pas assez de place ici a cote du logo/hamburger. */}
        <EtablissementSwitcher className="hidden sm:block" />
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

      {showGuide && <GuideModal onClose={() => setShowGuide(false)} />}
    </header>
  )
}
