const STYLES = {
  success: 'bg-brand-teal text-white',
  error: 'bg-red-500 text-white',
  info: 'bg-brand-blue text-white',
}

export default function Toast({ toasts }) {
  if (!toasts.length) return null

  return (
    <div className="fixed right-4 top-4 z-50 flex flex-col gap-2">
      {toasts.map((toast) => (
        <div key={toast.id} className={`rounded-lg px-4 py-3 shadow-lg ${STYLES[toast.type] ?? STYLES.info}`}>
          {toast.message}
        </div>
      ))}
    </div>
  )
}
