import { BrowserRouter } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import { ToastProvider } from './context/ToastContext'
import AppRoutes from './routes/AppRoutes'
import InstallPwaButton from './components/layout/InstallPwaButton'

function App() {
  return (
    <BrowserRouter>
      <ToastProvider>
        <AuthProvider>
          <AppRoutes />
          <InstallPwaButton />
        </AuthProvider>
      </ToastProvider>
    </BrowserRouter>
  )
}

export default App
