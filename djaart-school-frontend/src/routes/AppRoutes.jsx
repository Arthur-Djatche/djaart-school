import { Navigate, Route, Routes } from 'react-router-dom'
import ForgotPasswordPage from '../features/auth/ForgotPasswordPage'
import LoginPage from '../features/auth/LoginPage'
import ResetPasswordPage from '../features/auth/ResetPasswordPage'
import DashboardPage from '../features/dashboard/DashboardPage'
import UsersListPage from '../features/users/UsersListPage'
import ProtectedRoute from './ProtectedRoute'

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />

      <Route element={<ProtectedRoute />}>
        <Route path="/dashboard" element={<DashboardPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={['super_admin', 'admin_etablissement']} />}>
        <Route path="/users" element={<UsersListPage />} />
      </Route>

      <Route path="/" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
