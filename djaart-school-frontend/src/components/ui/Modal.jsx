export default function Modal({ title, onClose, children }) {
  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-brand-navy">{title}</h2>
          <button type="button" onClick={onClose} className="text-slate-400 hover:text-slate-600" aria-label="Fermer">
            ✕
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}
