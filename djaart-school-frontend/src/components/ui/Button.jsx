const VARIANTS = {
  primary: 'bg-brand-blue text-white hover:bg-brand-navy',
  accent: 'bg-brand-orange text-white hover:brightness-95',
  ghost: 'bg-transparent text-brand-navy hover:bg-slate-100',
}

export default function Button({ variant = 'primary', loading = false, disabled, children, className = '', ...props }) {
  return (
    <button
      disabled={disabled || loading}
      className={`inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 font-medium transition disabled:cursor-not-allowed disabled:opacity-60 ${VARIANTS[variant]} ${className}`}
      {...props}
    >
      {loading && (
        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
      )}
      {children}
    </button>
  )
}
