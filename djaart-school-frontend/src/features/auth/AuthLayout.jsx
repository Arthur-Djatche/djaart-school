import logo from '../../assets/logo.png'

const HIGHLIGHTS = [
  'Inscriptions, matricules et frais de scolarité centralisés',
  'Bulletins, relevés et cartes scolaires générés automatiquement',
  'Un tableau de bord dédié pour chaque rôle de votre équipe',
]

export default function AuthLayout({ title, subtitle, children }) {
  return (
    <div className="flex min-h-screen flex-col md:flex-row">
      <div className="brand-mesh relative hidden flex-col justify-between bg-gradient-to-br from-brand-navy via-brand-navy-soft to-brand-blue p-10 text-white md:flex md:w-1/2 lg:w-2/5">
        <div className="inline-flex w-fit items-center rounded-2xl bg-white p-3 shadow-2xl">
          <img src={logo} alt="DJAART SCHOOL" className="h-10 w-auto" />
        </div>

        <div>
          <h1 className="text-3xl font-bold leading-tight tracking-tight">
            La gestion scolaire, simplifiée du bout des doigts.
          </h1>
          <ul className="mt-6 flex flex-col gap-3">
            {HIGHLIGHTS.map((highlight) => (
              <li key={highlight} className="flex items-start gap-3 text-sm text-white/85">
                <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-orange text-xs font-bold text-white">
                  ✓
                </span>
                {highlight}
              </li>
            ))}
          </ul>
        </div>

        <p className="text-xs text-white/50">© {new Date().getFullYear()} DJAART SCHOOL</p>
      </div>

      <div className="flex flex-1 items-center justify-center bg-slate-50 px-4 py-12">
        <div className="w-full max-w-sm">
          <div className="mb-8 flex justify-center md:hidden">
            <img src={logo} alt="DJAART SCHOOL" className="h-16 w-auto" />
          </div>

          <div className="rounded-2xl bg-white p-8 shadow-soft">
            {title && <h2 className="text-xl font-semibold text-brand-navy">{title}</h2>}
            {subtitle && <p className="mt-1 mb-6 text-sm text-slate-500">{subtitle}</p>}
            {!subtitle && title && <div className="mb-6" />}
            {children}
          </div>
        </div>
      </div>
    </div>
  )
}
