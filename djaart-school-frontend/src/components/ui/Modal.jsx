import { useEffect, useState } from 'react'

export default function Modal({ title, onClose, children }) {
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    // Anime l'entree apres le premier rendu (l'etat part a false pour que la
    // transition CSS ait un point de depart, sinon elle ne joue pas).
    const frame = requestAnimationFrame(() => setVisible(true))
    return () => cancelAnimationFrame(frame)
  }, [])

  return (
    <div
      className={`fixed inset-0 z-40 flex items-end justify-center bg-brand-navy/50 backdrop-blur-[2px] transition-opacity duration-200 sm:items-center sm:px-4 ${
        visible ? 'opacity-100' : 'opacity-0'
      }`}
    >
      <div
        className={`flex max-h-[90vh] w-full max-w-md flex-col rounded-t-2xl bg-white p-6 shadow-2xl transition-all duration-200 sm:rounded-2xl ${
          visible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0 sm:translate-y-2'
        }`}
      >
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-brand-navy">{title}</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            aria-label="Fermer"
          >
            ✕
          </button>
        </div>
        <div className="overflow-y-auto">{children}</div>
      </div>
    </div>
  )
}
