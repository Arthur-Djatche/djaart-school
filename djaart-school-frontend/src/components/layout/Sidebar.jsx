import { NavLink } from 'react-router-dom'
import { navigationForRoles } from '../../config/navigation'
import useAuth from '../../hooks/useAuth'

const linkClasses = ({ isActive }) =>
  `rounded-lg px-3 py-2 transition-colors ${isActive ? 'bg-brand-blue text-white shadow-soft' : 'text-slate-300 hover:bg-white/10 hover:text-white'}`

export default function Sidebar({ open = false, onClose }) {
  const { user } = useAuth()
  const items = navigationForRoles(user?.roles ?? [], user?.etablissement?.type_etablissement ?? null)

  return (
    <>
      {/* Fond assombri, mobile uniquement : ferme le tiroir au tap. */}
      <div
        className={`fixed inset-0 z-40 bg-brand-navy/50 transition-opacity duration-200 md:hidden ${
          open ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0'
        }`}
        onClick={onClose}
        aria-hidden="true"
      />

      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 shrink-0 overflow-y-auto bg-brand-navy text-white transition-transform duration-200 md:sticky md:top-0 md:z-auto md:h-screen md:w-60 md:translate-x-0 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex items-center justify-between border-b border-white/10 px-4 py-4 md:hidden">
          <span className="text-sm font-semibold uppercase tracking-wide text-slate-300">Menu</span>
          <button
            type="button"
            onClick={onClose}
            className="rounded-full p-1 text-slate-300 transition hover:bg-white/10 hover:text-white"
            aria-label="Fermer le menu"
          >
            ✕
          </button>
        </div>

        <nav className="flex flex-col gap-1 p-4 text-sm">
          {items.map((item) =>
            item.children ? (
              <div key={item.label} className="flex flex-col gap-1">
                <span className="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                  {item.label}
                </span>
                {item.children.map((child) => (
                  <NavLink key={child.to} to={child.to} className={linkClasses} onClick={onClose}>
                    {child.label}
                  </NavLink>
                ))}
              </div>
            ) : (
              <NavLink key={item.to} to={item.to} className={linkClasses} onClick={onClose}>
                {item.label}
              </NavLink>
            ),
          )}
        </nav>
      </aside>
    </>
  )
}
