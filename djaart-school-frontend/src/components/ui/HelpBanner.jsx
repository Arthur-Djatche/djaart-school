import { useState } from 'react'

// Bandeau d'aide contextuelle ("avant de faire X, il faut avoir fait Y") —
// repliable, l'etat n'est pas persiste : reapparait a chaque visite de la
// page, volontairement (utile aussi pour un acteur experimente qui veut le
// re-consulter, pas seulement un debutant a onboarder une seule fois).
export default function HelpBanner({ children }) {
  const [ouvert, setOuvert] = useState(true)

  if (!ouvert) {
    return (
      <button
        type="button"
        onClick={() => setOuvert(true)}
        className="mb-4 flex items-center gap-1.5 text-sm text-brand-blue hover:underline"
      >
        ⓘ Afficher l'aide de cet écran
      </button>
    )
  }

  return (
    <div className="mb-4 flex items-start justify-between gap-3 rounded-xl border border-brand-blue-light/40 bg-brand-blue-tint px-4 py-3 text-sm text-brand-navy">
      <div className="flex gap-2">
        <span aria-hidden="true">ⓘ</span>
        <div>{children}</div>
      </div>
      <button
        type="button"
        onClick={() => setOuvert(false)}
        className="shrink-0 rounded-full p-1 text-brand-navy/60 transition hover:bg-white/60 hover:text-brand-navy"
        aria-label="Masquer l'aide"
      >
        ✕
      </button>
    </div>
  )
}
