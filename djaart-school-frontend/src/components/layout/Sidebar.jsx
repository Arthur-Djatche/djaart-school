import { NavLink } from 'react-router-dom'
import { navigationForRoles } from '../../config/navigation'
import useAuth from '../../hooks/useAuth'

export default function Sidebar() {
  const { user } = useAuth()
  const items = navigationForRoles(user?.roles ?? [])

  return (
    <aside className="hidden w-60 shrink-0 bg-brand-navy text-white md:block">
      <nav className="flex flex-col gap-1 p-4 text-sm">
        {items.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            className={({ isActive }) =>
              `rounded-lg px-3 py-2 transition ${isActive ? 'bg-brand-blue text-white' : 'text-slate-300 hover:bg-white/10'}`
            }
          >
            {item.label}
          </NavLink>
        ))}
      </nav>
    </aside>
  )
}
