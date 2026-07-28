const VARIANTS = {
  primary: 'bg-brand-blue text-white shadow-soft hover:bg-brand-navy hover:shadow-brand',
  accent: 'bg-brand-orange text-white shadow-soft hover:brightness-95 hover:shadow-glow-orange',
  outline: 'border border-brand-blue text-brand-blue bg-white hover:bg-brand-blue-tint',
  // Pour un bouton "outline" pose sur un fond sombre (hero, bandeau de marque) —
  // ne pas reutiliser `outline` + des classes de surcharge : les classes
  // Tailwind ont une specificite egale, la derniere definie dans la feuille de
  // style compilee l'emporte (pas forcement celle ecrite en dernier dans le
  // className), ce qui rendait le texte invisible tant que bg-white gagnait.
  outlineOnDark: 'border border-white/70 text-white bg-transparent hover:bg-white/10 hover:border-white',
  ghost: 'bg-transparent text-brand-navy hover:bg-slate-100',
}

const SIZES = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2',
  lg: 'px-6 py-3 text-base',
}

export default function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled,
  children,
  className = '',
  ...props
}) {
  return (
    <button
      disabled={disabled || loading}
      className={`inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-150 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue-light/50 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:shadow-none disabled:active:scale-100 ${VARIANTS[variant]} ${SIZES[size]} ${className}`}
      {...props}
    >
      {loading && (
        <span className="h-4 w-4 animate-spin rounded-full border-2 border-current/40 border-t-current" />
      )}
      {children}
    </button>
  )
}
