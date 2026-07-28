import { createContext, useCallback, useState } from 'react'
import Toast from '../components/ui/Toast'

export const ToastContext = createContext(null)

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const dismissToast = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const showToast = useCallback((message, type = 'info', duration = 3000) => {
    const id = crypto.randomUUID()
    setToasts((current) => [...current, { id, message, type }])
    setTimeout(() => dismissToast(id), duration)
  }, [dismissToast])

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <Toast toasts={toasts} onDismiss={dismissToast} />
    </ToastContext.Provider>
  )
}
