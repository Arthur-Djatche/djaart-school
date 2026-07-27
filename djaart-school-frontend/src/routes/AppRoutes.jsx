import { Navigate, Route, Routes } from 'react-router-dom'
import ForgotPasswordPage from '../features/auth/ForgotPasswordPage'
import LoginPage from '../features/auth/LoginPage'
import ResetPasswordPage from '../features/auth/ResetPasswordPage'
import DashboardPage from '../features/dashboard/DashboardPage'
import CaissePage from '../features/finance/caisse/CaissePage'
import FraisScolaritePage from '../features/finance/fraisScolarite/FraisScolaritePage'
import InscriptionsListPage from '../features/inscriptions/InscriptionsListPage'
import AnneesAcademiquesPage from '../features/parametrage/annees/AnneesAcademiquesPage'
import ClassesPage from '../features/parametrage/classes/ClassesPage'
import EtablissementsPage from '../features/parametrage/etablissements/EtablissementsPage'
import FilieresPage from '../features/parametrage/filieres/FilieresPage'
import MatieresPage from '../features/parametrage/matieres/MatieresPage'
import NiveauxPage from '../features/parametrage/niveaux/NiveauxPage'
import AffectationsPage from '../features/pedagogie/affectations/AffectationsPage'
import BulletinsPage from '../features/pedagogie/bulletins/BulletinsPage'
import SaisieNotesPage from '../features/pedagogie/notes/SaisieNotesPage'
import RelevesPage from '../features/pedagogie/releves/RelevesPage'
import SemestresPage from '../features/pedagogie/semestres/SemestresPage'
import SequencesPage from '../features/pedagogie/sequences/SequencesPage'
import UsersListPage from '../features/users/UsersListPage'
import ProtectedRoute from './ProtectedRoute'

const ADMIN_ROLES = ['super_admin', 'admin_etablissement']
const INSCRIPTION_ROLES = ['super_admin', 'admin_etablissement', 'secretaire', 'comptable']
const FINANCE_ROLES = ['super_admin', 'admin_etablissement', 'comptable']
const PEDAGOGIE_ROLES = ['super_admin', 'admin_etablissement', 'enseignant']
const BULLETINS_ROLES = ['super_admin', 'admin_etablissement', 'secretaire']

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />

      <Route element={<ProtectedRoute />}>
        <Route path="/dashboard" element={<DashboardPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={INSCRIPTION_ROLES} />}>
        <Route path="/inscriptions" element={<InscriptionsListPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} />}>
        <Route path="/users" element={<UsersListPage />} />
        <Route path="/parametrage/etablissements" element={<EtablissementsPage />} />
        <Route path="/parametrage/annees-academiques" element={<AnneesAcademiquesPage />} />
        <Route path="/parametrage/filieres" element={<FilieresPage />} />
        <Route path="/parametrage/filieres/:filiereId/niveaux" element={<NiveauxPage />} />
        <Route path="/parametrage/classes" element={<ClassesPage />} />
        <Route path="/parametrage/matieres" element={<MatieresPage />} />
        <Route path="/finance/frais-scolarite" element={<FraisScolaritePage />} />
        <Route path="/pedagogie/affectations" element={<AffectationsPage />} />
        <Route path="/pedagogie/sequences" element={<SequencesPage />} />
        <Route path="/pedagogie/semestres" element={<SemestresPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={FINANCE_ROLES} />}>
        <Route path="/finance/caisse" element={<CaissePage />} />
      </Route>

      <Route element={<ProtectedRoute roles={PEDAGOGIE_ROLES} />}>
        <Route path="/pedagogie/notes" element={<SaisieNotesPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} />}>
        <Route path="/pedagogie/bulletins" element={<BulletinsPage />} />
        <Route path="/pedagogie/releves" element={<RelevesPage />} />
      </Route>

      <Route path="/" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
