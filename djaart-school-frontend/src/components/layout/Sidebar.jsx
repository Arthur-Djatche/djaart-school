import { NavLink } from 'react-router-dom'
import { navigationForRoles } from '../../config/navigation'
import useAuth from '../../hooks/useAuth'

const linkClasses = ({ isActive }) =>
  `rounded-lg px-3 py-2 transition ${isActive ? 'bg-brand-blue text-white' : 'text-slate-300 hover:bg-white/10'}`

export default function Sidebar() {
  const { user } = useAuth()
  const items = navigationForRoles(user?.roles ?? [], user?.etablissement?.type_etablissement ?? null)

  return (
    <aside className="hidden w-60 shrink-0 bg-brand-navy text-white md:block">
      <nav className="flex flex-col gap-1 p-4 text-sm">
        {items.map((item) =>
          item.children ? (
            <div key={item.label} className="flex flex-col gap-1">
              <span className="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                {item.label}
              </span>
              {item.children.map((child) => (
                <NavLink key={child.to} to={child.to} className={linkClasses}>
                  {child.label}
                </NavLink>
              ))}
            </div>
          ) : (
            <NavLink key={item.to} to={item.to} className={linkClasses}>
              {item.label}
            </NavLink>
          ),
        )}
      </nav>
    </aside>
  )
}
