import logo from '../../assets/logo.png'
import useAuth from '../../hooks/useAuth'
import Button from '../ui/Button'

export default function Topbar() {
  const { user, logout } = useAuth()

  return (
    <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">
      <div className="flex items-center gap-3">
        <img src={logo} alt="DJAART SCHOOL" className="h-9 w-auto" />
      </div>
      <div className="flex items-center gap-4">
        {user && <span className="text-sm text-brand-navy">{user.name}</span>}
        <Button variant="ghost" onClick={logout}>
          Déconnexion
        </Button>
      </div>
    </header>
  )
}
