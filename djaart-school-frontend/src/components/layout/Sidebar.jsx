import { useEffect, useState } from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import { navigationForRoles } from '../../config/navigation'
import useAuth from '../../hooks/useAuth'
import EtablissementSwitcher from './EtablissementSwitcher'

const linkClasses = ({ isActive }) =>
  `rounded-lg px-3 py-2 transition-colors ${isActive ? 'bg-brand-blue text-white shadow-soft' : 'text-slate-300 hover:bg-white/10 hover:text-white'}`

export default function Sidebar({ open = false, onClose }) {
  const { user } = useAuth()
  const location = useLocation()
  const etablissementTypes = [user?.etablissement?.type_etablissement].filter(Boolean)
  const items = navigationForRoles(user?.roles ?? [], etablissementTypes, user?.permissions ?? [])

  const [openGroups, setOpenGroups] = useState(() => new Set())

  useEffect(() => {
    const groupeActif = items.find((item) => item.children?.some((child) => location.pathname.startsWith(child.to)))
    if (groupeActif) {
      setOpenGroups((prev) => (prev.has(groupeActif.label) ? prev : new Set(prev).add(groupeActif.label)))
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname])

  const toggleGroup = (label) => {
    setOpenGroups((prev) => {
      const next = new Set(prev)
      if (next.has(label)) {
        next.delete(label)
      } else {
        next.add(label)
      }
      return next
    })
  }

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

        {/* Repris ici pour mobile : pas assez de place dans la Topbar a cote
            du logo/hamburger (cf. Topbar, qui la montre a partir de sm). */}
        {user?.etablissements_geres?.length > 1 && (
          <div className="border-b border-white/10 px-4 py-4 sm:hidden">
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Établissement actif</p>
            <EtablissementSwitcher className="w-full bg-white" />
          </div>
        )}

        <nav className="flex flex-col gap-1 p-4 text-sm">
          {items.map((item) =>
            item.children ? (
              <div key={item.label} className="flex flex-col gap-1">
                <button
                  type="button"
                  onClick={() => toggleGroup(item.label)}
                  className="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400 transition hover:bg-white/5 hover:text-slate-200"
                  aria-expanded={openGroups.has(item.label)}
                >
                  {item.label}
                  <svg
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    className={`h-3.5 w-3.5 transition-transform duration-150 ${openGroups.has(item.label) ? 'rotate-180' : ''}`}
                  >
                    <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                  </svg>
                </button>
                {openGroups.has(item.label) && (
                  <div className="flex flex-col gap-1">
                    {item.children.map((child) => (
                      <NavLink key={child.to} to={child.to} className={linkClasses} onClick={onClose}>
                        {child.label}
                      </NavLink>
                    ))}
                  </div>
                )}
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
