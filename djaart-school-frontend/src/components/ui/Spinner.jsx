const SIZES = {
  sm: 'h-4 w-4 border-2',
  md: 'h-8 w-8 border-[3px]',
  lg: 'h-12 w-12 border-4',
}

export default function Spinner({ size = 'md', label = 'Chargement…', className = '' }) {
  return (
    <div className={`flex flex-col items-center justify-center gap-3 py-6 text-slate-500 ${className}`}>
      <span
        className={`animate-spin rounded-full border-brand-blue-tint border-t-brand-blue ${SIZES[size]}`}
        role="status"
        aria-label={label}
      />
      {label && <span className="text-sm">{label}</span>}
    </div>
  )
}
