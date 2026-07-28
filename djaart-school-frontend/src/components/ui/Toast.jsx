const STYLES = {
  success: 'bg-brand-teal text-white',
  error: 'bg-red-500 text-white',
  warning: 'bg-brand-orange text-white',
  info: 'bg-brand-blue text-white',
}

const ICONS = {
  success: '✓',
  error: '✕',
  warning: '!',
  info: 'ℹ',
}

export default function Toast({ toasts, onDismiss }) {
  if (!toasts.length) return null

  return (
    <div className="fixed inset-x-4 top-4 z-50 flex flex-col gap-2 sm:inset-x-auto sm:right-4">
      {toasts.map((toast) => (
        <div
          key={toast.id}
          className={`flex items-start gap-3 rounded-xl px-4 py-3 shadow-2xl animate-toast-in ${
            STYLES[toast.type] ?? STYLES.info
          }`}
        >
          <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold">
            {ICONS[toast.type] ?? ICONS.info}
          </span>
          <p className="flex-1 text-sm">{toast.message}</p>
          <button
            type="button"
            onClick={() => onDismiss?.(toast.id)}
            className="text-white/70 transition hover:text-white"
            aria-label="Fermer la notification"
          >
            ✕
          </button>
        </div>
      ))}
    </div>
  )
}
