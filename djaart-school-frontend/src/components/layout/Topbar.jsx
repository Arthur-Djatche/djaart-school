import logo from '../../assets/logo.png'
import useAuth from '../../hooks/useAuth'
import Button from '../ui/Button'

export default function Topbar({ onMenuClick }) {
  const { user, logout } = useAuth()

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
        <img src={logo} alt="DJAART SCHOOL" className="h-8 w-auto sm:h-9" />
      </div>
      <div className="flex items-center gap-2 sm:gap-4">
        {user && <span className="hidden text-sm text-brand-navy sm:inline">{user.name}</span>}
        <Button variant="ghost" size="sm" onClick={logout}>
          Déconnexion
        </Button>
      </div>
    </header>
  )
}
