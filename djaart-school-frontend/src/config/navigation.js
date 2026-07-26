export const NAVIGATION = [
  { label: 'Tableau de bord', to: '/dashboard', roles: null },
  { label: 'Comptes utilisateurs', to: '/users', roles: ['super_admin', 'admin_etablissement'] },
]

export function navigationForRoles(roles = []) {
  return NAVIGATION.filter((item) => !item.roles || item.roles.some((role) => roles.includes(role)))
}
