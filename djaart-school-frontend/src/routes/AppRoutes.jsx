import { Navigate, Route, Routes } from 'react-router-dom'
import ForgotPasswordPage from '../features/auth/ForgotPasswordPage'
import LoginPage from '../features/auth/LoginPage'
import ResetPasswordPage from '../features/auth/ResetPasswordPage'
import DemandesDemoPage from '../features/landing/DemandesDemoPage'
import LandingPage from '../features/landing/LandingPage'
import ApprenantFichePage from '../features/apprenants/ApprenantFichePage'
import ApprenantsListPage from '../features/apprenants/ApprenantsListPage'
import SeancePhotoPage from '../features/apprenants/seance-photo/SeancePhotoPage'
import DashboardPage from '../features/dashboard/DashboardPage'
import DocumentsMassePage from '../features/documents/DocumentsMassePage'
import CaissePage from '../features/finance/caisse/CaissePage'
import PrintRecuPage from '../features/finance/caisse/PrintRecuPage'
import FraisScolaritePage from '../features/finance/fraisScolarite/FraisScolaritePage'
import InscriptionsListPage from '../features/inscriptions/InscriptionsListPage'
import AnneesAcademiquesPage from '../features/parametrage/annees/AnneesAcademiquesPage'
import ClassesPage from '../features/parametrage/classes/ClassesPage'
import DepartementsPage from '../features/parametrage/departements/DepartementsPage'
import EtablissementsPage from '../features/parametrage/etablissements/EtablissementsPage'
import FilieresPage from '../features/parametrage/filieres/FilieresPage'
import MatieresPage from '../features/parametrage/matieres/MatieresPage'
import NiveauxPage from '../features/parametrage/niveaux/NiveauxPage'
import UnitesEnseignementPage from '../features/parametrage/unites-enseignement/UnitesEnseignementPage'
import AffectationsPage from '../features/pedagogie/affectations/AffectationsPage'
import BulletinsPage from '../features/pedagogie/bulletins/BulletinsPage'
import SaisieConduitePage from '../features/pedagogie/conduite/SaisieConduitePage'
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
      <Route path="/" element={<LandingPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />

      <Route element={<ProtectedRoute />}>
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/imprimer-recu/:recuId" element={<PrintRecuPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={INSCRIPTION_ROLES} permission="acces.inscriptions" />}>
        <Route path="/inscriptions" element={<InscriptionsListPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={['super_admin']} />}>
        <Route path="/demandes-demo" element={<DemandesDemoPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} />}>
        <Route path="/users" element={<UsersListPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.parametrage_etablissement" />}>
        <Route path="/parametrage/etablissements" element={<EtablissementsPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.parametrage_academique" />}>
        <Route path="/parametrage/annees-academiques" element={<AnneesAcademiquesPage />} />
        <Route path="/parametrage/departements" element={<DepartementsPage />} />
        <Route path="/parametrage/filieres" element={<FilieresPage />} />
        <Route path="/parametrage/filieres/:filiereId/niveaux" element={<NiveauxPage />} />
        <Route path="/parametrage/classes" element={<ClassesPage />} />
        <Route path="/parametrage/matieres" element={<MatieresPage />} />
        <Route path="/parametrage/unites-enseignement" element={<UnitesEnseignementPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.frais_scolarite" />}>
        <Route path="/finance/frais-scolarite" element={<FraisScolaritePage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.affectations" />}>
        <Route path="/pedagogie/affectations" element={<AffectationsPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.sequences" />}>
        <Route path="/pedagogie/sequences" element={<SequencesPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={ADMIN_ROLES} permission="acces.semestres" />}>
        <Route path="/pedagogie/semestres" element={<SemestresPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={FINANCE_ROLES} permission="acces.caisse" />}>
        <Route path="/finance/caisse" element={<CaissePage />} />
      </Route>

      <Route element={<ProtectedRoute roles={PEDAGOGIE_ROLES} permission="acces.notes" />}>
        <Route path="/pedagogie/notes" element={<SaisieNotesPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={[...PEDAGOGIE_ROLES, 'secretaire']} permission="acces.conduite" />}>
        <Route path="/pedagogie/conduite" element={<SaisieConduitePage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} permission="acces.bulletins" />}>
        <Route path="/pedagogie/bulletins" element={<BulletinsPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} permission="acces.releves" />}>
        <Route path="/pedagogie/releves" element={<RelevesPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} permission="acces.apprenants" />}>
        <Route path="/apprenants" element={<ApprenantsListPage />} />
        <Route path="/apprenants/:id" element={<ApprenantFichePage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} permission="acces.seance_photo" />}>
        <Route path="/apprenants/seance-photo" element={<SeancePhotoPage />} />
      </Route>

      <Route element={<ProtectedRoute roles={BULLETINS_ROLES} permission="acces.documents_masse" />}>
        <Route path="/documents/masse" element={<DocumentsMassePage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
