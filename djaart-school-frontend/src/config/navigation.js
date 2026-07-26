const PARAMETRAGE_ROLES = ['super_admin', 'admin_etablissement']

export const NAVIGATION = [
  { label: 'Tableau de bord', to: '/dashboard', roles: null },
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
    roles: PARAMETRAGE_ROLES,
    children: [
      { label: 'Frais de scolarité', to: '/finance/frais-scolarite' },
    ],
  },
  { label: 'Comptes utilisateurs', to: '/users', roles: ['super_admin', 'admin_etablissement'] },
]

export function navigationForRoles(roles = []) {
  return NAVIGATION.filter((item) => !item.roles || item.roles.some((role) => roles.includes(role)))
}
