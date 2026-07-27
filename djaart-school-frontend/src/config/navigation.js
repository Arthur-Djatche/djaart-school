const PARAMETRAGE_ROLES = ['super_admin', 'admin_etablissement']
const INSCRIPTION_ROLES = ['super_admin', 'admin_etablissement', 'secretaire', 'comptable']
const FINANCE_ROLES = ['super_admin', 'admin_etablissement', 'comptable']
const PEDAGOGIE_ROLES = ['super_admin', 'admin_etablissement', 'enseignant']
const BULLETINS_ROLES = ['super_admin', 'admin_etablissement', 'secretaire']

export const NAVIGATION = [
  { label: 'Tableau de bord', to: '/dashboard', roles: null },
  { label: 'Inscriptions', to: '/inscriptions', roles: INSCRIPTION_ROLES },
  { label: 'Apprenants', to: '/apprenants', roles: BULLETINS_ROLES },
  { label: 'Documents en masse', to: '/documents/masse', roles: BULLETINS_ROLES },
  {
    label: 'Paramétrage',
    roles: PARAMETRAGE_ROLES,
    children: [
      { label: 'Établissement(s)', to: '/parametrage/etablissements' },
      { label: 'Années académiques', to: '/parametrage/annees-academiques' },
      { label: 'Filières', to: '/parametrage/filieres' },
      { label: 'Classes', to: '/parametrage/classes' },
      { label: 'Matières', to: '/parametrage/matieres' },
    ],
  },
  {
    label: 'Finance',
    roles: FINANCE_ROLES,
    children: [
      { label: 'Frais de scolarité', to: '/finance/frais-scolarite', roles: PARAMETRAGE_ROLES },
      { label: 'Caisse', to: '/finance/caisse', roles: FINANCE_ROLES },
    ],
  },
  {
    label: 'Pédagogie',
    roles: [...PEDAGOGIE_ROLES, 'secretaire'],
    children: [
      { label: 'Affectations', to: '/pedagogie/affectations', roles: PARAMETRAGE_ROLES },
      { label: 'Séquences', to: '/pedagogie/sequences', roles: PARAMETRAGE_ROLES },
      { label: 'Semestres', to: '/pedagogie/semestres', roles: PARAMETRAGE_ROLES },
      { label: 'Saisie des notes', to: '/pedagogie/notes', roles: PEDAGOGIE_ROLES },
      { label: 'Saisie de la conduite', to: '/pedagogie/conduite', roles: [...PEDAGOGIE_ROLES, 'secretaire'] },
      { label: 'Bulletins', to: '/pedagogie/bulletins', roles: BULLETINS_ROLES },
      { label: 'Relevés de notes', to: '/pedagogie/releves', roles: BULLETINS_ROLES },
    ],
  },
  { label: 'Comptes utilisateurs', to: '/users', roles: ['super_admin', 'admin_etablissement'] },
]

function allowed(roles, userRoles) {
  return !roles || roles.some((role) => userRoles.includes(role))
}

export function navigationForRoles(userRoles = []) {
  return NAVIGATION.filter((item) => allowed(item.roles, userRoles))
    .map((item) => (item.children ? { ...item, children: item.children.filter((child) => allowed(child.roles, userRoles)) } : item))
    .filter((item) => !item.children || item.children.length > 0)
}
